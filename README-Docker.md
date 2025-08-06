# Docker Setup for Odds Application

## Quick Start

1. Copy the environment file and customize:
```bash
cp .env.docker .env.docker.local
# Edit .env.docker.local with your secure passwords
```

2. Build and run with docker-compose:
```bash
docker-compose --env-file .env.docker.local up --build
```

3. Access the application at http://localhost:8080

4. Run database migrations:
```bash
docker-compose --env-file .env.docker.local exec app php artisan migrate
```

## Manual Docker Build

```bash
# Build the image
docker build -t odds-app .

# Run with external MySQL
docker run -p 8080:80 \
  -e DB_HOST=your-mysql-host \
  -e DB_DATABASE=odds \
  -e DB_USERNAME=your-username \
  -e DB_PASSWORD=your-password \
  odds-app
```

## Configuration

The Docker setup includes:
- PHP 8.1 with Apache web server
- All required PHP extensions for Laravel
- Automatic cron jobs for odds updates and email sending
- Health checks for container monitoring
- MySQL database service (via docker-compose)

## Environment Variables

Key environment variables that can be configured:
- `DB_HOST`: Database host (default: mysql)
- `DB_DATABASE`: Database name (default: odds)
- `DB_USERNAME`: Database username (default: odds_user)
- `DB_PASSWORD`: Database password (default: odds_password)
- `APP_URL`: Application URL (default: http://localhost)

## Scheduled Tasks

The container automatically runs the following Laravel commands:
- `php artisan command:update-odds` - Every 5 minutes
- `php artisan command:send-auth-mail` - Every minute

## Troubleshooting

If you encounter issues:
1. Check container logs: `docker-compose logs app`
2. Check database connectivity: `docker-compose exec app php artisan migrate:status`
3. Verify cron jobs: `docker-compose exec app crontab -l`
