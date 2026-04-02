# Docker Setup for job_test_thunderbite

This repository includes Docker support via `docker-compose.yml` for local development of the Laravel application.

## Requirements

- Docker Engine (latest stable)
- Docker Compose (v2 recommended)
- Git

## Included Docker services

- `app`: PHP app container (builds from `Dockerfile`)
- `web`: Nginx container exposing HTTP
- `db`: MySQL 8.0 database container
- Volumes:
  - `dbdata` (MySQL data persistence)
  - `vendor` (PHP dependencies persistence)

## Quick start

From repository root:

```bash
# build + start all services
docker compose up -d --build

# verify running containers
docker compose ps
```

## Access application

Open browser at:

- `http://localhost:9000`

## Tail logs

```bash
docker compose logs -f
```

## Shell into app container

```bash
docker compose exec app sh
```

## Common Laravel commands

Inside app container:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

## Database details (from `docker-compose.yml`)

- Host: `db`
- Port: `3306`
- Name: `laravel`
- User: `laravel`
- Password: `secret`
- Root password: `secret`

## Port mapping

- host 9000 -> container 80 (Nginx)
- host 3306 -> container 3306 (MySQL)

## Shut down

```bash
docker compose down
```

Include volume removal if needed:

```bash
docker compose down -v
```

## Troubleshooting

- If `vendor` is not available, run:
  - `docker compose exec app composer install`
- If database not ready, wait and retry commands that connect to `db`. Use `docker compose logs db`.
- If permission errors appear, ensure current user has rights to project folder (for bind mounts).

## Notes

- Volume mounts mount host source into the container; local code changes are reflected instantly.
- For production, adjust `APP_ENV`, `APP_DEBUG`, `APP_KEY` and secret credentials in `.env` or via build-time configuration; do not use these defaults in production.
