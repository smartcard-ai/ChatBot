# Multi-stage build for optimized image size
FROM python:3.11-slim AS builder

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

# Install PHP, nginx and required system dependencies
RUN apt-get update && apt-get install -y \
    php-cli \
    php-mysql \
    php-pgsql \
    php-mongodb \
    nginx \
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

# Copy nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Set environment variables
ENV PATH=/home/app/.local/bin:$PATH
ENV PYTHONUNBUFFERED=1
ENV FLASK_ENV=production

# Create directory for SQLite database
RUN mkdir -p /app/data && chown app:app /app/data

# Create nginx directories with proper permissions
RUN mkdir -p /var/run/nginx /var/lib/nginx /var/log/nginx && \
    chown -R app:app /var/run/nginx /var/lib/nginx /var/log/nginx

# Switch to non-root user
USER app

# Expose only one port (the nginx proxy port)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Start nginx, PHP and Flask servers
CMD ["sh", "-c", "php -S 127.0.0.1:8000 index.php & python app.py & nginx -g 'daemon off;'"]