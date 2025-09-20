#!/bin/bash

# ChatBot Docker Startup Script
# This script helps you get started with the ChatBot application

set -e

echo "🤖 ChatBot Docker Setup"
echo "======================"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    echo "   Visit: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    echo "   Visit: https://docs.docker.com/compose/install/"
    exit 1
fi

# Create data directory if it doesn't exist
mkdir -p data

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.example .env
    echo "⚠️  Please edit .env file and add your API keys before running the application."
    echo "   Required: GEMINI_API_KEY"
    read -p "Press Enter to continue after editing .env file..."
fi

echo "🔨 Building Docker image..."
docker-compose build

echo "🚀 Starting ChatBot application..."
docker-compose up -d

echo "⏳ Waiting for services to start..."
sleep 10

echo "✅ ChatBot is running!"
echo ""
echo "🌐 Access the application:"
echo "   Frontend: http://localhost:8000"
echo "   Backend API: http://localhost:5001"
echo ""
echo "📊 Check status:"
echo "   docker-compose ps"
echo ""
echo "📜 View logs:"
echo "   docker-compose logs -f"
echo ""
echo "🛑 To stop:"
echo "   docker-compose down"
echo ""
echo "🔧 For development with live reload:"
echo "   make dev"
