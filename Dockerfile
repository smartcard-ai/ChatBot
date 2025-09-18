# # # Base image with Python and PHP

# # Use Python with slim base
# FROM python:3.10-slim

# # Install PHP
# RUN apt-get update && apt-get install -y php-cli && rm -rf /var/lib/apt/lists/*

# # Set working directory
# WORKDIR /app

# # Copy project files
# COPY . /app

# # Install Python dependencies
# RUN pip install --no-cache-dir -r requirements.txt

# # Expose ports for PHP (8000) and Flask (5000)
# EXPOSE 8000 5000

# # Start both PHP and Flask
# CMD php -S 0.0.0.0:8000 index.php & python app.py

# Base image with Python and PHP
FROM python:3.10-slim

# Install PHP + PostgreSQL dependencies
RUN apt-get update && apt-get install -y \
    php-cli \
    gcc \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy project files
COPY . /app

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Expose ports
EXPOSE 8000 5001

# Start both PHP server (frontend) and Flask app (backend)
CMD ["sh", "-c", "php -S 0.0.0.0:8000 index.php & python app.py"]
