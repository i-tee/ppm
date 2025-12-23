# Docker Development Setup

## Quick Start
1. Clone repository
2. `cp .env.example .env`
3. `cp vite.config.js vite.config.local.js`
4. `docker compose up -d --build`

## Access
- Laravel: http://localhost:8080
- Vite: http://localhost:5173
- MySQL: localhost:3307 (user: laravel_user, pass: secret)

## Commands
- Start: `docker compose up -d`
- Stop: `docker compose stop`
- Rebuild: `docker compose up -d --build`
- Logs: `docker compose logs -f [service]`
