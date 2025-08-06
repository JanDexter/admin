# Developer Guide

## Setup Instructions
1. Clone the repository.
2. Install dependencies:
   - `npm install`
   - `composer install`
3. Configure `.env`:
   - Set DB credentials (`root`/`12345`), DB name (`admin_dashboard_docker`), and other environment variables as needed.
4. Create the database `admin_dashboard_docker` in MySQL.
5. Run migrations and seeders:
   - `php artisan migrate --force`
   - `php artisan db:seed --class=UserRoleSeeder`
6. Build frontend assets:
   - `npm run build`
7. Start the server:
   - `php artisan serve`

## Default Accounts
- Admin: `admin@admin.com` / `password`
- Staff: `staff@example.com` / `password`
- Customer: `customer@example.com` / `password`

## Troubleshooting
- If you see SQL or Redis connection errors, check `.env` for correct host values (`localhost` for local, `database`/`redis` for Docker).
- For asset issues, rebuild with `npm run build`.
- For session/cache issues, clear Laravel cache: `php artisan config:cache`.

## Contribution
- Follow PSR standards for PHP code.
- Use feature branches for new work.
- Submit pull requests with clear descriptions.

---
For questions, contact the lead developer or check the main `README.md` for more info.
