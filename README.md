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

Run the Flask backend server on port **5001**:

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

---

## Docker Setup

This project includes comprehensive Docker support for easy deployment and development.

### Quick Start with Docker

1. **Clone and navigate to the project**:
   ```bash
   git clone <repository-url>
   cd ChatBot
   ```

2. **Start the application**:
   ```bash
   # Using docker-compose (recommended)
   docker-compose up -d

   # Or using make
   make up
   ```

3. **Access the application**:
   - Frontend: http://localhost:8000
   - Backend API: http://localhost:5001

### Docker Commands

#### Development
```bash
# Start development environment
make dev

# View logs
make logs

# Open shell in container
make shell

# Stop development environment
make down
```

#### Production
```bash
# Build production image
make build-prod

# Start production environment
make prod

# Scale application
make scale
```

#### Maintenance
```bash
# Clean up containers and images
make clean

# Backup database
make backup

# Check status
make status
```

### Manual Docker Commands

```bash
# Build the image
docker build -t chatbot:latest .

# Run the container
docker run -d \
  --name chatbot \
  -p 8000:8000 \
  -p 5001:5001 \
  -v $(pwd)/data:/app/data \
  chatbot:latest

## To build and run the docker

docker build -t chatbot:latest .
docker run -d --name chatbot -p 8080:8080 chatbot:latest

# Check running containers
docker ps

# Check all containers (including stopped)
docker ps -a

# View container logs
docker logs -f chatbot

# Execute commands in running container
docker exec -it chatbot bash

# Stop container
docker stop chatbot

# Start stopped container
docker start chatbot

# Remove container
docker rm chatbot
```

### Production Deployment

1. **Build for production**:
   ```bash
   docker buildx build --platform linux/amd64 -t shivamnishad/chatbot:latest .
   ```

2. **Push to registry**:
   ```bash
   docker push shivamnishad/chatbot:latest
   ```

3. **Run in production**:
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

### Environment Variables

Create a `.env` file based on `.env.example`:

```bash
cp .env.example .env
```

Key variables:
- `GEMINI_API_KEY`: Your Google Gemini API key
- `GEMINI_MODEL`: AI model to use (default: gemini-2.0-flash)
- `FLASK_ENV`: Set to 'production' for production deployment

### Database Persistence

The SQLite database is automatically persisted in the `data/` directory. For production, consider using PostgreSQL:

```bash
# Uncomment PostgreSQL service in docker-compose.prod.yml
# Update DATABASE_URL in .env file
```

### Troubleshooting

1. **Port conflicts**: Change ports in docker-compose.yml if needed
2. **Permission issues**: Ensure data directory has proper permissions
3. **Database errors**: Check database file permissions and location
4. **Memory issues**: Adjust resource limits in docker-compose.prod.yml
