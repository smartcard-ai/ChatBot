import json
import gspread
from google.oauth2.service_account import Credentials
from google import genai
import sqlite3
import pymysql
import psycopg2
import logging
from flask import Flask, request, jsonify
from flask_cors import CORS, cross_origin

# Configure logging
logging.basicConfig(level=logging.INFO)

app = Flask(__name__)
CORS(app, origins=["http://localhost:8000"], supports_credentials=True)

# Globals
CONFIG = {}
worksheets = []
spreadsheet = None
gc = None
gemini_client = None
db_conn = None
selected_tables = []

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
            data_source TEXT,
            sheet_id TEXT,
            selected_sheets TEXT,
            service_account_json TEXT,
            db_host TEXT,
            db_port INTEGER,
            db_name TEXT,
            db_username TEXT,
            db_password TEXT,
            selected_tables TEXT
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

# --- Set credentials and list items ---
@app.route('/set_credentials', methods=['POST'])
def set_credentials():
    global CONFIG, gc, spreadsheet, gemini_client, db_conn
    CONFIG = request.form.to_dict()
    data_source = CONFIG.get('data_source')

    gemini_client = genai.Client(api_key=CONFIG['gemini_api_key'])

    if data_source == 'google_sheets':
        try:
            service_json_str = CONFIG['service_account_json']
            logging.info(f"Service account JSON string length: {len(service_json_str)}")
            service_json = json.loads(service_json_str)
            logging.info("Service account JSON parsed successfully.")

            # Validate required keys for service account
            required_keys = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri', 'auth_provider_x509_cert_url', 'client_x509_cert_url']
            missing_keys = [key for key in required_keys if key not in service_json]
            if missing_keys:
                logging.error(f"Missing keys in service account JSON: {missing_keys}")
                return jsonify({'error': f'Invalid Service Account JSON: missing keys {missing_keys}'}), 400

            if service_json.get('type') != 'service_account':
                logging.error("Service account JSON type is not 'service_account'")
                return jsonify({'error': 'Invalid Service Account JSON: type must be service_account'}), 400

        except json.JSONDecodeError as e:
            logging.error(f"JSON decode error: {str(e)}")
            return jsonify({'error': 'Invalid Service Account JSON: not valid JSON'}), 400
        except Exception as e:
            logging.error(f"Failed to load service account JSON: {str(e)}")
            return jsonify({'error': 'Invalid Service Account JSON'}), 400

        try:
            creds = Credentials.from_service_account_info(service_json, scopes=["https://www.googleapis.com/auth/spreadsheets.readonly"])
            logging.info(f"Credentials created successfully.")
            gc = gspread.authorize(creds)
            spreadsheet = gc.open_by_key(CONFIG['sheet_id'])
            items = [ws.title for ws in spreadsheet.worksheets()]
            return jsonify({'type': 'sheets', 'items': items})
        except Exception as e:
            logging.error(f"Failed to authorize or open spreadsheet: {str(e)}")
            return jsonify({'error': 'Failed to authorize or open spreadsheet'}), 400

    elif data_source == 'mysql':
        try:
            db_conn = pymysql.connect(
                host=CONFIG['db_host'],
                port=int(CONFIG['db_port']),
                user=CONFIG['db_username'],
                password=CONFIG['db_password'],
                database=CONFIG['db_name']
            )
            cursor = db_conn.cursor()
            cursor.execute("SHOW TABLES")
            items = [row[0] for row in cursor.fetchall()]
            cursor.close()
            return jsonify({'type': 'tables', 'items': items})
        except Exception as e:
            return jsonify({'error': f'MySQL connection failed: {str(e)}'}), 400

    elif data_source == 'postgresql':
        try:
            db_conn = psycopg2.connect(
                host=CONFIG['db_host'],
                port=int(CONFIG['db_port']),
                user=CONFIG['db_username'],
                password=CONFIG['db_password'],
                database=CONFIG['db_name']
            )
            cursor = db_conn.cursor()
            cursor.execute("SELECT tablename FROM pg_tables WHERE schemaname='public'")
            items = [row[0] for row in cursor.fetchall()]
            cursor.close()
            return jsonify({'type': 'tables', 'items': items})
        except Exception as e:
            return jsonify({'error': f'PostgreSQL connection failed: {str(e)}'}), 400

    else:
        return jsonify({'error': 'Invalid data source'}), 400

# --- Set selected items ---
@app.route('/set_items', methods=['POST'])
def set_items():
    global worksheets, selected_tables, CONFIG
    data_source = CONFIG.get('data_source')
    selected = request.form.getlist('item_names')

    if data_source == 'google_sheets':
        worksheets[:] = [spreadsheet.worksheet(name) for name in selected]
        selected_tables = []
    else:
        selected_tables = selected
        worksheets = []

    return jsonify({'selected_items': selected})

# --- Chat endpoint ---
@app.route('/chat', methods=['POST'])
def chat():
    global worksheets, selected_tables, CONFIG, gemini_client, db_conn
    data_source = CONFIG.get('data_source')

    if data_source == 'google_sheets' and not worksheets:
        return jsonify({'response': 'Select at least one sheet first.'})
    elif data_source in ['mysql', 'postgresql'] and not selected_tables:
        return jsonify({'response': 'Select at least one table first.'})

    user_input = request.json.get('message')

    if data_source == 'google_sheets':
        all_data = {ws.title: ws.get_all_records() for ws in worksheets}
        data_desc = "Spreadsheet data"
    else:
        all_data = {}
        for table in selected_tables:
            cursor = db_conn.cursor()
            cursor.execute(f"SELECT * FROM {table}")
            columns = [desc[0] for desc in cursor.description]
            rows = cursor.fetchall()
            all_data[table] = [dict(zip(columns, row)) for row in rows]
            cursor.close()
        data_desc = "Database data"

    prompt = f"You are an assistant. {data_desc}: {json.dumps(all_data, indent=2)}\nUser: {user_input}\nAnswer:"

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
    try:
        # Validation: Check required fields
        required_fields = ['username', 'chatbot_id', 'chatbot_name', 'gemini_api_key', 'gemini_model']
        for field in required_fields:
            if not request.form.get(field):
                return jsonify({"success": False, "message": f"{field} is required"}), 400

        username = request.form['username']
        data_source = request.form.get('data_source')
        selected_items = request.form.getlist('selected_items')

        conn = sqlite3.connect(DB_FILE)
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM users WHERE username=?", (username,))
        row = cursor.fetchone()
        if not row:
            conn.close()
            return jsonify({"success": False, "message": "User not found"}), 400

        if data_source == 'google_sheets':
            selected_sheets = json.dumps(selected_items)
            selected_tables = None
            db_host = db_port = db_name = db_username = db_password = None
        else:
            selected_sheets = None
            selected_tables = json.dumps(selected_items)
            db_host = request.form.get('db_host')
            db_port_str = request.form.get('db_port')
            db_port = int(db_port_str) if db_port_str else None
            db_name = request.form.get('db_name')
            db_username = request.form.get('db_username')
            db_password = request.form.get('db_password')

        cursor.execute("""
            INSERT OR REPLACE INTO chatbots (id, username, chatbot_name, gemini_api_key, gemini_model, data_source, sheet_id, selected_sheets, service_account_json, db_host, db_port, db_name, db_username, db_password, selected_tables)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (
            request.form['chatbot_id'],
            username,
            request.form['chatbot_name'],
            request.form['gemini_api_key'],
            request.form['gemini_model'],
            data_source,
            request.form.get('sheet_id'),
            selected_sheets,
            request.form.get('service_account_json'),
            db_host,
            db_port,
            db_name,
            db_username,
            db_password,
            selected_tables
        ))
        conn.commit()
        conn.close()
        return jsonify({"success": True})
    except Exception as e:
        # Logging: Log exceptions
        logging.error(f"Error saving chatbot: {str(e)}")
        return jsonify({"success": False, "message": str(e)}), 500

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
