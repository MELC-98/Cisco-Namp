# Cisco Network Automation & Management Platform (NAMP)

A centralized web platform for managing and automating Cisco network infrastructure — routers and switches — from a single professional dashboard.

## Architecture

```
Browser → Vue.js 3 SPA → Laravel 11 REST API → FastAPI Automation Service → Cisco Devices (SSH)
```

| Service     | Technology                | Port | Purpose                          |
|-------------|---------------------------|------|----------------------------------|
| Frontend    | Vue.js 3, Vite, Tailwind  | 80   | Admin dashboard SPA              |
| Backend     | Laravel 11, PHP 8.3       | 8000 | REST API, auth, business logic   |
| Automation  | Python 3.12, FastAPI      | 8001 | SSH automation via Netmiko       |
| Database    | PostgreSQL 16             | 5432 | Persistent storage (internal)    |
| Cache/Queue | Redis 7                   | 6379 | Sessions, queues (internal)      |

## Prerequisites

- Docker & Docker Compose
- Git

## Quick Start

```bash
# Clone the repository
git clone <repo-url> cisco-namp
cd cisco-namp

# Create environment file
cp .env.example .env
# Edit .env with your settings (especially passwords and APP_KEY)

# Build and start all services
docker compose up --build -d

# Run database migrations and seeders
docker compose exec backend php artisan migrate --seed

# Generate application key (if not set)
docker compose exec backend php artisan key:generate

# Access the application
# Frontend: http://localhost
# API:      http://localhost:8000/api
```

## Default Admin Account

Set in `.env`:
```
ADMIN_EMAIL=admin@cisco-namp.local
ADMIN_PASSWORD=<your-secure-password>
```

## Development

```bash
# Frontend development (hot reload)
cd frontend && npm run dev

# Backend logs
docker compose logs -f backend

# Run Laravel tests
docker compose exec backend php artisan test

# Run FastAPI tests
docker compose exec automation pytest
```

## Project Structure

```
cisco-namp/
├── frontend/       # Vue.js 3 SPA
├── backend/        # Laravel 11 REST API
├── automation/     # Python FastAPI service
├── docker-compose.yml
├── .env.example
└── README.md
```

## Security Notes

- No public registration — admin creates all accounts
- Device credentials are encrypted at rest (AES-256-CBC)
- FastAPI is not exposed publicly — internal Docker network only
- All actions are audit-logged
- Only approved read-only commands in MVP
- Configuration changes require preview + approval

## License

Proprietary — Internal Enterprise Use Only
