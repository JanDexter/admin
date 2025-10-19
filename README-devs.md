# Developer Guide

## Project Overview
This is a full-stack Laravel + Vue.js application. The backend is powered by Laravel (PHP), and the frontend uses Vue.js, built with Vite. The stack also includes MySQL for data storage and Redis for caching/queueing.

## How the Stack Works
- **Laravel (Backend):** Handles API requests, authentication, business logic, and serves Blade views for server-side rendering.
- **Vue.js (Frontend):** Provides a reactive SPA experience, built and served via Vite. Communicates with Laravel via API endpoints.
- **MySQL:** Stores all persistent data (users, customers, tasks, etc.).
- **Redis:** Used for caching, session storage, and queue management.

### Key Components
- **Controllers (`app/Http/Controllers/`):** Handle incoming HTTP requests, process data, and return responses (JSON for APIs, Blade views for web pages).
- **Models (`app/Models/`):** Represent database tables and business entities. Used for querying and manipulating data.
- **Requests (`app/Http/Requests/`):** Validate and authorize incoming data for controllers.
- **Middleware (`app/Http/Middleware/`):** Filter and process requests before they reach controllers (e.g., authentication, rate limiting).
- **Views (`resources/views/`):** Blade templates for server-rendered pages. Vue components for SPA pages are in `resources/js/`.
- **Routes (`routes/web.php`, `routes/api.php`):** Define URL endpoints and map them to controllers.

## Connecting Everything
1. **Environment Setup:**
   - Configure `.env` for DB, Redis, Mail, etc.
   - Example:
     ```
     DB_HOST=localhost
     DB_DATABASE=admin_dashboard_docker
     DB_USERNAME=root
     DB_PASSWORD=12345
     REDIS_HOST=127.0.0.1
     ```
2. **Database:**
   - Create the database in MySQL.
   - Run migrations: `php artisan migrate --force`
   - Seed users: `php artisan migrate --seed`
   - By default, the database is empty. The first user to register at `/register` becomes the admin.
3. **Frontend Build:**
   - Install dependencies: `npm install`
   - Build assets: `npm run build`
4. **Running the App:**
   - Start Laravel: `php artisan serve`
   - Access at `http://localhost:8000`

## How Requests Flow
- **Web Request:**
  1. User visits a URL.
  2. Route matches in `routes/web.php`.
  3. Controller processes request, interacts with models.
  4. Returns Blade view or redirects.
- **API Request (from Vue):**
  1. Vue component makes AJAX call to Laravel API route.
  2. API route matches in `routes/api.php`.
  3. Controller processes, returns JSON.
  4. Vue updates UI based on response.

## Default Accounts
- The first user to register via the `/register` endpoint will be created as an admin. After that, registration is disabled.

> **Note:** Public registration is disabled after the first user is created. Only administrators can create new user accounts via the User Management dashboard.

## Troubleshooting
- If you see SQL or Redis connection errors, check `.env` for correct host values (`localhost` for local, `database`/`redis` for Docker).
- For asset issues, rebuild with `npm run build`.
- For session/cache issues, clear Laravel cache: `php artisan config:cache`.

## Contribution Workflow
- Create a feature branch: `git checkout -b feature/your-feature`
- Commit with verified signature if required.
- Push and open a pull request to `main`.

## Useful Commands
- Clear caches: `php artisan config:cache`, `php artisan cache:clear`, etc.
- Build frontend: `npm run build`
- Run tests: `php artisan test`

---
For questions, contact the lead developer or check the main `README.md` for more info.
