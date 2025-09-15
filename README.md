# ChatBot Project

This project is a chatbot application that integrates Google Sheets data with Google's Gemini AI to provide conversational responses based on spreadsheet content. It consists of a Flask backend API and a PHP frontend interface.

---

## Prerequisites

- **Python** 3.7 or higher  
- **PHP** 7.4 or higher  
- **Google Cloud** service account with access to Google Sheets API  
- **Gemini API key** from Google AI Generative Language  

---

## Installation

1. **Clone the repository** or download the project files.

2. **Create and activate a virtual environment**:

   ### macOS / Linux
   ```bash
   python3 -m venv venv
   source venv/bin/activate
   ```

   ### Windows (PowerShell)
   ```powershell
   python -m venv venv
   .\venv\Scripts\Activate
   ```

   > 💡 *You should now see `(venv)` at the start of your terminal prompt.*

3. **Install Python dependencies** inside the activated environment:
   ```bash
   pip install -r requirements.txt
   ```

---

## Configuration

1. Obtain a **Google Cloud service account JSON key** with permissions to read Google Sheets.

2. Get your **Gemini API key** from Google AI Generative Language.

3. Prepare your Google Spreadsheet and note its **Spreadsheet ID**.

---

## Running the Project

### Start the Flask Backend

Run the Flask backend server on port **8080**:

```bash
python app.py
```

### Start the PHP Frontend Server

Run the PHP built-in server on port **8000**:

```bash
php -S localhost:8000 index.php
```

---

## Usage

1. Open your browser and navigate to `http://localhost:8000`.

2. Sign up or log in with a username and password.

3. Fill in the chatbot configuration form:
   - Chatbot Name  
   - Generate or enter a Chatbot ID  
   - Gemini API Key  
   - Gemini Model (default: `gemini-2.0-flash`)  
   - Google Spreadsheet ID  
   - Paste the Service Account JSON key

4. Click **Connect** to authorize and list available sheets.

5. Select one or more sheets to load.

6. Click **Load to Chat** to start chatting with the bot based on your spreadsheet data.

7. Save your chatbot configuration for later use if desired.

---

## Database

The project uses a **SQLite database** (`chatbots.db`) to store user credentials and chatbot configurations.

---

## Notes

- Ensure your Google service account has **read access** to the specified spreadsheet.  
- The Flask backend runs on port **8080** and the PHP frontend on port **8000**—make sure these ports are free.  
- The chatbot uses **Gemini AI** to generate responses based on spreadsheet data.
