import json
import gspread
from google.oauth2.service_account import Credentials
from google import genai
import sqlite3
from flask import Flask, request, jsonify
from flask_cors import CORS, cross_origin

app = Flask(__name__)
CORS(app, origins=["http://localhost:8000"], supports_credentials=True)

# Globals
CONFIG = {}
worksheets = []
spreadsheet = None
gc = None
gemini_client = None

DB_FILE = "chatbots.db"

# --- Initialize database ---
def init_db():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS users (
            username TEXT PRIMARY KEY,
            password TEXT
        )
    """)
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS chatbots (
            id TEXT PRIMARY KEY,
            username TEXT,
            chatbot_name TEXT,
            gemini_api_key TEXT,
            gemini_model TEXT,
            sheet_id TEXT,
            selected_sheets TEXT,
            service_account_json TEXT
        )
    """)
    conn.commit()
    conn.close()

init_db()

# --- Signup ---
@app.route('/signup', methods=['POST'])
def signup():
    data = request.json
    username = data.get('username')
    password = data.get('password')
    if not username or not password:
        return jsonify({"success": False, "message": "Username and password required"}), 400

    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users WHERE username=?", (username,))
    if cursor.fetchone():
        conn.close()
        return jsonify({"success": False, "message": "User already exists"}), 400

    cursor.execute("INSERT INTO users (username, password) VALUES (?, ?)", (username, password))
    conn.commit()
    conn.close()
    return jsonify({"success": True})

# --- Login ---
@app.route('/login', methods=['POST'])
def login():
    data = request.json
    username = data.get('username')
    password = data.get('password')
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users WHERE username=? AND password=?", (username, password))
    if cursor.fetchone():
        conn.close()
        return jsonify({"success": True})
    conn.close()
    return jsonify({"success": False, "message": "Invalid credentials"}), 400

# --- Set credentials and list sheets ---
@app.route('/set_credentials', methods=['POST'])
def set_credentials():
    global CONFIG, gc, spreadsheet, gemini_client
    CONFIG = request.form.to_dict()

    try:
        service_json = json.loads(CONFIG['service_account_json'])
    except Exception as e:
        return jsonify({'error': 'Invalid Service Account JSON'}), 400

    creds = Credentials.from_service_account_info(service_json, scopes=["https://www.googleapis.com/auth/spreadsheets.readonly"])
    gc = gspread.authorize(creds)
    spreadsheet = gc.open_by_key(CONFIG['sheet_id'])
    gemini_client = genai.Client(api_key=CONFIG['gemini_api_key'])
    sheet_names = [ws.title for ws in spreadsheet.worksheets()]
    return jsonify({'sheets': sheet_names})

# --- Set selected sheets ---
@app.route('/set_sheet', methods=['POST'])
def set_sheet():
    global worksheets
    selected = request.form.getlist('sheet_names')
    worksheets[:] = [spreadsheet.worksheet(name) for name in selected]
    return jsonify({'selected_sheets': selected})

# --- Chat endpoint ---
@app.route('/chat', methods=['POST'])
def chat():
    global worksheets, CONFIG, gemini_client
    if not worksheets:
        return jsonify({'response': 'Select at least one sheet first.'})

    user_input = request.json.get('message')
    all_data = {ws.title: ws.get_all_records() for ws in worksheets}
    prompt = f"You are an assistant. Spreadsheet data: {json.dumps(all_data, indent=2)}\nUser: {user_input}\nAnswer:"

    response = gemini_client.models.generate_content(
        model=CONFIG['gemini_model'],
        contents=prompt
    )

    bot_reply = "Sorry, no response."
    if response.candidates and response.candidates[0].content.parts:
        bot_reply = response.candidates[0].content.parts[0].text

    return jsonify({'response': bot_reply})

# --- Save chatbot ---
@app.route('/save_chatbot', methods=['POST'])
def save_chatbot():
    selected_sheets = request.form.getlist('selected_sheets')
    username = request.form['username']
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users WHERE username=?", (username,))
    row = cursor.fetchone()
    if not row:
        conn.close()
        return jsonify({"success": False, "message": "User not found"}), 400
    cursor.execute("""
        INSERT OR REPLACE INTO chatbots (id, username, chatbot_name, gemini_api_key, gemini_model, sheet_id, selected_sheets, service_account_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    """, (
        request.form['chatbot_id'],
        username,
        request.form['chatbot_name'],
        request.form['gemini_api_key'],
        request.form['gemini_model'],
        request.form['sheet_id'],
        json.dumps(selected_sheets),
        request.form['service_account_json']
    ))
    conn.commit()
    conn.close()
    return jsonify({"success": True})

# --- List saved chatbots ---
@app.route('/list_chatbots', methods=['GET'])
def list_chatbots():
    username = request.args.get('username')
    if not username:
        return jsonify({"error": "Username required"}), 400
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM chatbots WHERE username=?", (username,))
    rows = cursor.fetchall()
    conn.close()
    return jsonify([dict(row) for row in rows])

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=8080)
