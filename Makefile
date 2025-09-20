.PHONY: help build up down logs clean test dev prod

# Default target
help:
	@echo "ChatBot Docker Commands:"
	@echo "  make build    - Build the Docker image"
	@echo "  make up       - Start the development environment"
	@echo "  make down     - Stop the development environment"
	@echo "  make logs     - View container logs"
	@echo "  make clean    - Remove containers, images, and volumes"
	@echo "  make test     - Run tests in container"
	@echo "  make dev      - Start development environment with live reload"
	@echo "  make prod     - Start production environment"
	@echo "  make shell    - Open shell in running container"
	@echo "  make db-shell - Open database shell"

# Build the Docker image
build:
	docker build -t chatbot:latest .

# Start development environment
up:
	docker-compose up -d

# Start development environment with logs
dev:
	docker-compose up

# Start production environment
prod:
	docker-compose -f docker-compose.prod.yml up -d

# Stop development environment
down:
	docker-compose down

# Stop production environment
stop:
	docker-compose -f docker-compose.prod.yml down

# View logs
logs:
	docker-compose logs -f

# View specific service logs
logs-flask:
	docker-compose logs -f chatbot

# Open shell in container
shell:
	docker-compose exec chatbot bash

# Clean up containers, images, and volumes
clean:
	docker-compose down -v --rmi all
	docker system prune -f

# Run tests
test:
	docker-compose exec chatbot python -m pytest

# Build for production
build-prod:
	docker buildx build --platform linux/amd64 -t shivamnishad/chatbot:latest .

# Push to registry
push:
	docker push shivamnishad/chatbot:latest

# Pull latest image
pull:
	docker pull shivamnishad/chatbot:latest

# Show container status
status:
	docker-compose ps

# Restart services
restart:
	docker-compose restart

# Scale services (for production)
scale:
	docker-compose up -d --scale chatbot=3

# Backup database
backup:
	docker-compose exec chatbot cp data/chatbots.db data/chatbots.db.backup

# Restore database
restore:
	docker-compose exec chatbot cp data/chatbots.db.backup data/chatbots.db
