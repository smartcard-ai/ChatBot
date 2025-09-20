# Multi-stage build for optimized image size
FROM python:3.11-slim as builder

# Install system dependencies for building Python packages
RUN apt-get update && apt-get install -y \
    gcc \
    libpq-dev \
    default-libmysqlclient-dev \
    build-essential \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy requirements first for better layer caching
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir --user -r requirements.txt

# Production stage
FROM python:3.11-slim

# Install PHP and required system dependencies
RUN apt-get update && apt-get install -y \
    php-cli \
    php-mysql \
    php-pgsql \
    php-mongodb \
    curl \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Set working directory first
WORKDIR /app

# Create non-root user for security
RUN useradd --create-home --shell /bin/bash app \
    && chown -R app:app /app

# Copy Python packages from builder stage
COPY --from=builder /root/.local /home/app/.local

# Copy application files
COPY --chown=app:app . /app

# Set environment variables
ENV PATH=/home/app/.local/bin:$PATH
ENV PYTHONUNBUFFERED=1
ENV FLASK_ENV=production

# Create directory for SQLite database
RUN mkdir -p /app/data && chown app:app /app/data

# Switch to non-root user
USER app

# Expose ports
EXPOSE 8000 5001

# Health check for Flask application
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:5001/ || exit 1

# Start both PHP and Flask servers
CMD ["sh", "-c", "php -S 0.0.0.0:8000 index.php & python app.py"]
