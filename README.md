# CO-Z Reservation System

A modern, full-featured web application built with Laravel 11, Inertia.js, and Vue.js for comprehensive reservation system. This application provides a streamlined admin interface and customer interface focused on co-workspace service reservations.

## 🏁 Final Stack & Modules

### Backend
- **Laravel 11** (PHP 8.2+)
- **Database:** MySQL 8.0+ (default), SQLite (testing), Redis (cache/queue/session)
- **Composer Packages:**
  - `laravel/framework` (core)
  - `inertiajs/inertia-laravel` (SPA bridge)
  - `laravel/sanctum` (API authentication)
  - `laravel/socialite` (OAuth, Google login)
  - `maatwebsite/excel` (Excel import/export)
  - `pragmarx/google2fa-laravel` (2FA)
  - `tightenco/ziggy` (route helper)
  - `laravel/tinker` (REPL)
  - **Dev:** `laravel/breeze` (auth scaffolding), `laravel/pint` (code style), `phpunit/phpunit` (testing), `mockery/mockery`, `nunomaduro/collision`, `fakerphp/faker`, `laravel/sail`, `laravel/pail`

### Frontend
- **Vue.js 3** (SPA)
- **Inertia.js** (Laravel-Vue bridge)
- **Vite** (build tool)
- **Tailwind CSS** (styling)
- **FullCalendar** (calendar integration)
- **PWA:** Service Worker, Manifest, Push Notifications, Offline Support

### PHP Modules Required
- `pdo_mysql` (MySQL)
- `mbstring` (multibyte string)
- `exif` (image handling)
- `pcntl` (process control)
- `bcmath` (arithmetic)
- `gd` (image processing)

### Feature Modules
- **User Management:** Admin, Staff, Customer roles; Google OAuth; 2FA
- **Customer Management:** CRUD, company/contact standardization, points system
- **Space Management:** Real-time status, dynamic pricing, bulk operations
- **Reservation System:** Calendar, history, cost calculation
- **Service Management:** Service types, auto-assignment, Excel import/export
- **Dashboard:** Statistics, recent activity, space overview
- **PWA:** Installable, offline, push notifications
- **Security:** Single admin registration, role-based access, public registration lock

---

*See below for full details on each module and technology. For troubleshooting, see the dedicated section. For developer setup, see `README-devs.md`.*


## 🚀 About This Project

This project is a Reservation System. 

## ✨ Features

### Core Management
*   **User Roles:** Admin role with ability to create and manage other users.
*   **Customer Management:** CRUD operations for customer records with standardized company name handling.
*   **Service Management:** Define and manage services offered with automatic pricing.
*   **User Management:** Admin-only interface to manage users with enhanced filtering.

### Space & Reservation Management
*   **Space Management:** Real-time space allocation with visual status indicators.
*   **Reservation System:** Complete reservation tracking with history and cost calculation.
*   **Calendar View:** FullCalendar integration showing all space occupancy with detailed hover tooltips.
*   **Space Overview:** Color-coded grid view of all spaces with availability status.
*   **Dynamic Pricing:** Configurable hourly rates and discount systems per space type.

### User Experience
*   **Real-time Updates:** Countdown timers on occupied spaces showing time until release.
*   **Smart State Management:** Enhanced tab switching without data loss.
*   **Responsive UI:** Built with Tailwind CSS for a mobile-first experience.
*   **PWA Ready:** Can be installed as a Progressive Web App.
*   **Secure Access:** Public registration is automatically disabled after the first admin user is created.
*   **Google Sign-In:** Allow users to register and log in using their Google account.
*   **Two-Factor Authentication (2FA):** Enhanced security with time-based one-time passwords (TOTP).

## 🆕 Recent Updates (September 2025)

### Space Management & Reservations
- **Complete Reservation System**: Added full reservation tracking with automatic cost calculation
- **Calendar Integration**: Implemented FullCalendar with detailed hover tooltips and enhanced view options  
- **Space Overview Dashboard**: Color-coded grid showing real-time space availability
- **Dynamic Countdown Timers**: Visual countdown on occupied spaces showing time until release
- **Points & Rewards System**: Customer loyalty points based on spending history

### Data Consistency & Bug Fixes  
- **Standardized Data Fields**: Fixed `company` vs `company_name` inconsistencies across the system
- **Enhanced State Management**: Resolved user disappearing bug during tab switching
- **Service Auto-Assignment**: Automatic service price calculation during customer creation
- **Improved User Experience**: Removed auto-updates on focus loss, requiring explicit save actions

### UI/UX Improvements
- **Responsive Calendar Views**: Wider dropdown buttons and improved mobile responsiveness
- **Enhanced Tooltips**: Rich hover details showing space, customer, timing, and cost information
- **Modern Dashboard Design**: Consistent styling across all management interfaces
- **Real-time Updates**: Page refreshes ensure calendar and dashboard data accuracy

## 🛠️ Tech Stack

The application is built using the following technologies:

*   **Backend:** Laravel 11 (PHP 8.2+)
*   **Frontend:** Vue.js 3 with Inertia.js
*   **Calendar:** FullCalendar with Vue3 integration
*   **Styling:** Tailwind CSS
*   **Database:** MySQL 8.0+
*   **Development:** Vite, Node.js 18+

### Why this stack?

#### Pros:
*   **Rapid Development:** Laravel's rich ecosystem and conventions, combined with Vite's fast asset bundling, significantly speed up development time.
*   **Modern Frontend without API Hassle:** Inertia.js allows for the creation of a modern, single-page application experience using server-side routing and controllers, avoiding the complexity of building and maintaining a separate API.
*   **Scalability:** Laravel is highly scalable, and using a robust database like MySQL ensures the application can handle growth.
*   **Consistent UI:** Tailwind CSS provides a utility-first approach that makes it easy to build complex, responsive, and consistent user interfaces.
*   **Containerization:** Docker provides a consistent and isolated development environment, simplifying setup and deployment.

#### Cons:
*   **Learning Curve:** For developers new to Laravel or Vue.js, there can be a learning curve.
*   **Monolithic Structure:** While Inertia.js bridges the gap, the application is more tightly coupled than a separate frontend and backend API, which could be a drawback for projects requiring native mobile clients.
*   **State Management:** For highly complex and interactive pages, managing state in Vue.js might become more challenging compared to more opinionated frameworks.

## 📋 Prerequisites

Before running this application, ensure you have the following installed:

- **PHP** 8.2 or higher with required extensions
- **Composer** for PHP dependency management
- **Node.js** 18+ and npm for frontend dependencies
- **MySQL** 8.0 or higher for database storage
- **Git** for version control (recommended)

## 🚀 Installation

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd admin
   ```

2. **Install backend dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   
   Create a MySQL database named `admin_dashboard` and configure your `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=admin_dashboard
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Create the First Admin User**
   
   Once the application is running, navigate to `/register` in your browser to create the first user. This user will automatically be assigned the `admin` role. After this initial registration, the `/register` route will be disabled.

### 🎯 Running the Application

#### Development Environment

Start the Laravel development server:
```bash
php artisan serve
```

For real-time asset compilation during development:
```bash
npm run dev
```

**Access the application:** `http://localhost:8000`

## 🔧 Development

### Code Standards
This project follows **PSR-12** coding standards. Maintain code quality:

```bash
# Check code style
./vendor/bin/phpcs

# Fix code style automatically
./vendor/bin/phpcbf
```

## 🚨 Troubleshooting

### Common Issues and Solutions

#### Calendar Not Loading
**Issue**: Calendar page shows blank or loading forever
```bash
# Solution: Ensure both servers are running
php artisan serve     # Terminal 1
npm run dev          # Terminal 2

# Check if FullCalendar dependencies are installed
npm ls @fullcalendar/vue3
```

#### Frontend Assets Not Compiling  
**Issue**: CSS/JS changes not reflecting, build errors
```bash
# Clear cache and rebuild
npm run build
php artisan config:clear
php artisan view:clear

# Check for dependency conflicts
npm install --legacy-peer-deps
```

#### Database Connection Errors
**Issue**: Migration or seeding failures
```bash
# Verify database configuration
php artisan config:clear
php artisan migrate:status

# Reset database if needed
php artisan migrate:fresh --seed
```

#### User Disappearing After Tab Switch
**Issue**: Newly created users vanish when switching browser tabs
**Solution**: Fixed in UserManagement/Index.vue - filters now properly sync with URL state

#### Space Status Not Updating
**Issue**: Space assignments not reflected in calendar
**Solution**: Space assignment now forces page reload (`preserveState: false`) to ensure data consistency

#### Permission Denied Errors
**Issue**: Cannot create/edit resources
```bash
# Check Laravel permissions
php artisan cache:clear
php artisan route:clear

# Verify user roles in database
php artisan tinker
>>> User::with('role')->get()
```

### Development Tips

#### Debugging Frontend Issues
```bash
# Enable Vue devtools in browser
npm install --save-dev @vue/devtools

# Check browser console for errors
# Inspect Network tab for failed requests
```

#### Database Debugging
```bash
# Enable query logging in .env
DB_LOG_QUERIES=true

# View recent queries
tail -f storage/logs/laravel.log
```

#### Performance Issues
```bash
# Optimize for development
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Monitor with Telescope (if installed)
php artisan telescope:install
```

### Getting Help

1. **Check Laravel Logs**: `storage/logs/laravel.log`
2. **Browser Console**: Developer tools for frontend errors  
3. **Network Tab**: Monitor API requests and responses
4. **Vue Devtools**: Install browser extension for component debugging

### Development Workflow

```bash
# Daily development startup
git pull origin main
composer install
npm install
php artisan migrate
npm run dev &           # Background process
php artisan serve       # Foreground process
```

## 🔧 Development Setup

### Required Services
Both backend and frontend development servers need to be running:

```bash
# Terminal 1: Laravel backend server
php artisan serve
# Serves on http://127.0.0.1:8000

# Terminal 2: Vite frontend development server  
npm run dev
# Serves assets on http://localhost:5173
```

### 🔑 Creating the First Admin User

Public registration is enabled only when there are no users in the database. To create your administrator account:

1.  Start your application servers (`php artisan serve` and `npm run dev`).
2.  Navigate to `http://localhost:8000/register` in your browser.
3.  Fill out the registration form.

This first user will be automatically granted `admin` privileges. After this account is created, the registration page will be disabled to prevent public sign-ups. Subsequent users must be created by an admin via the **User Management** dashboard.

#### Production Deployment

For production-ready assets:
```bash
npm run build
```

## 🗄 Database Schema

### Users Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `name` | String | User's full name |
| `email` | String (Unique) | User's email address |
| `email_verified_at` | Timestamp | Email verification time |
| `password` | String (Hashed) | Encrypted password |
| `role` | Enum | customer/staff/admin |
| `is_active` | Boolean | User active status |
| `google_id` | String (Nullable) | Google OAuth user ID |
| `avatar` | String (Nullable) | URL to user's Google avatar |
| `two_factor_secret` | Text (Nullable) | Encrypted secret for 2FA |
| `two_factor_recovery_codes` | Text (Nullable) | Encrypted recovery codes for 2FA |
| `two_factor_confirmed_at` | Timestamp (Nullable) | When 2FA was confirmed |
| `remember_token` | String | Remember me token |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### Customers Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `name` | String | Customer name |
| `email` | String (Unique) | Customer email |
| `phone` | String (Nullable) | Phone number |
| `company_name` | String (Nullable) | Company name (standardized) |
| `contact_person` | String (Nullable) | Primary contact person |
| `address` | Text (Nullable) | Customer address |
| `status` | Enum | active/inactive |
| `amount_paid` | Decimal | Amount paid (default: 0) |
| `space_type_id` | Foreign Key (Nullable) | Assigned space type |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### Reservations Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `user_id` | Foreign Key | Reference to users table |
| `customer_id` | Foreign Key | Reference to customers table |
| `space_id` | Foreign Key | Reference to spaces table |
| `start_time` | Timestamp | Reservation start time |
| `end_time` | Timestamp (Nullable) | Reservation end time |
| `hourly_rate` | Decimal | Rate charged per hour |
| `total_cost` | Decimal (Nullable) | Total calculated cost |
| `status` | Enum | active/completed/cancelled |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### Spaces Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `name` | String | Space name/identifier |
| `space_type_id` | Foreign Key | Reference to space_types table |
| `status` | Enum | available/occupied/maintenance |
| `current_customer_id` | Foreign Key (Nullable) | Currently assigned customer |
| `occupied_from` | Timestamp (Nullable) | Occupation start time |
| `occupied_until` | Timestamp (Nullable) | Planned occupation end time |
| `hourly_rate` | Decimal (Nullable) | Space-specific hourly rate |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### Space Types Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `name` | String | Space type name |
| `default_price` | Decimal | Default hourly rate |
| `hourly_rate` | Decimal (Nullable) | Current hourly rate |
| `default_discount_hours` | Integer (Nullable) | Hours before discount applies |
| `default_discount_percentage` | Decimal (Nullable) | Discount percentage |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### 👤 User Roles & Permissions
| Role | Description | Permissions |
|------|-------------|-------------|
| **ADMIN** | System Administrator | Full system access, user management, all CRUD operations |
| **STAFF** | Staff Member | Customer management, service management, view reports |
| **CUSTOMER** | Customer User | View own profile, access customer portal |

### 🏢 Available Co-workspace Services
| Service Type | Price | Description |
|--------------|-------|-------------|
| **CONFERENCE ROOM** | ₱350 | Fully equipped meeting room |
| **SHARED SPACE** | ₱40 | Open collaborative workspace |
| **EXCLUSIVE SPACE** | ₱60 | Private dedicated workspace |
| **PRIVATE SPACE** | ₱50 | Individual quiet workspace |
| **DRAFTING TABLE** | ₱50 | Specialized technical workspace |

## 🗺️ Application Routes & Navigation

### Main Dashboard Routes
- `/dashboard` - Main dashboard with space overview and statistics
- `/calendar` - FullCalendar view showing all reservations and space occupancy
- `/space-management` - Real-time space allocation and pricing management
- `/customers` - Customer CRUD operations and service assignment
- `/user-management` - Admin-only user management with filtering

### Key Features by Route

#### Dashboard (`/dashboard`)
- **Spaces Overview**: Color-coded grid showing all spaces and availability
- **Quick Statistics**: Total customers, active reservations, revenue metrics
- **Recent Activity**: Latest reservations and customer actions

#### Calendar (`/calendar`) 
- **FullCalendar Integration**: Month/week/day views with reservation details
- **Interactive Tooltips**: Hover for space, customer, timing, and cost details
- **Responsive Design**: Optimized for desktop and mobile viewing

#### Space Management (`/space-management`)
- **Real-time Status**: Live updates of space availability with countdown timers
- **Dynamic Pricing**: Configure hourly rates and discount systems
- **Quick Assignment**: Modal-based customer assignment with search
- **Bulk Operations**: Edit pricing across multiple space types

#### Customer Management (`/customers`)
- **Advanced Filtering**: Search by company, contact, status
- **Reservation History**: Complete spending history and points tracking
- **Standardized Data**: Consistent company name and contact information

#### User Management (`/user-management`) - Admin Only
- **Role-based Access**: Admin, Staff, and Customer role management
- **Enhanced Filtering**: Persistent search and role filtering
- **Activity Tracking**: Login history and user statistics
- **Batch Operations**: Activate/deactivate multiple users

## 🛣 API Routes

### Web Routes (Authenticated Users)

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/dashboard` | `dashboard` | Customer management dashboard |
| GET | `/customers` | `customers.index` | List all customers |
| GET | `/customers/create` | `customers.create` | Show create customer form |
| POST | `/customers` | `customers.store` | Store new customer |
| GET | `/customers/{customer}` | `customers.show` | Show customer details |
| GET | `/customers/{customer}/edit` | `customers.edit` | Show edit customer form |
| PUT/PATCH | `/customers/{customer}` | `customers.update` | Update customer |

### User Management Routes (Admin Only)

| Method | URI | Route Name | Description |
|--------|-----|------------|-------------|
| GET | `/user-management` | `user-management.index` | List all users |
| GET | `/user-management/create` | `user-management.create` | Show create user form |
| POST | `/user-management` | `user-management.store` | Store new user |
| GET | `/user-management/{user}` | `user-management.show` | Show user details |
| GET | `/user-management/{user}/edit` | `user-management.edit` | Show edit user form |
| PUT/PATCH | `/user-management/{user}` | `user-management.update` | Update user |
| PATCH | `/user-management/{user}/toggle-status` | `user-management.toggle-status` | Activate/Deactivate user |

### Authentication Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/login` | Show login form |
| POST | `/login` | Process login |
| POST | `/logout` | Logout user |
| GET | `/auth/google` | Redirect to Google for authentication |
| GET | `/auth/google/callback` | Handle Google OAuth callback |

> **Note:** Public registration is automatically disabled after the first user is created. Only administrators can create new user accounts via the User Management dashboard.

## 🏗 Models and Architecture

### Customer Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'website',
        'status',
        'notes',
        'amount_paid',
        'space_type_id',
    ];

    // Type casting for better data handling
    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Formatted price accessors
    public function getFormattedAmountPaidAttribute()
    {
        return $this->amount_paid ? '₱' . number_format($this->amount_paid, 2) : '₱0.00';
    }
}
```

## 🧪 Testing

### Run Test Suite
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run tests with detailed output
php artisan test --verbose
```

## 📱 Progressive Web App (PWA)

This application is a fully-featured Progressive Web App providing native app-like experience across all devices.

### 🚀 Installation Experience
- **Web App Manifest**: Complete metadata for seamless app installation
- **Smart Install Prompts**: Automatic installation prompts for supported browsers
- **Quick Access Shortcuts**: Direct shortcuts to dashboard and customer management
- **Cross-Platform Support**: Works on desktop, tablet, and mobile devices

### 🔄 Offline Capabilities
- **Service Worker**: Advanced caching for offline functionality
- **Background Sync**: Automatic synchronization when connection is restored
- **Offline Fallback**: Custom offline page with helpful information
- **Smart Caching Strategy**: Network-first with intelligent cache fallback

### ✨ Enhanced Features
- **Push Notifications**: Real-time notifications (user permission required)
- **Native App Experience**: Runs in standalone mode without browser UI
- **Connection Status**: Visual indicators for online/offline status
- **Auto-Updates**: Seamless service worker updates for new versions

### 📲 Installation Guide

#### Desktop Installation (Chrome/Edge)
1. Navigate to the application in your browser
2. Look for the install icon in the address bar
3. Click "Install" when the prompt appears
4. Find the app in your applications menu

#### Mobile Installation

**Android (Chrome)**
1. Open the app in Chrome
2. Tap the menu (⋮) and select "Add to Home Screen"
3. Confirm installation
4. Access from your home screen

**iOS (Safari)**
1. Open the app in Safari
2. Tap the share button (□↗)
3. Select "Add to Home Screen"
4. Confirm and access from home screen

#### Manual Installation
If automatic prompts don't appear:
1. Look for the "Install App" button in the dashboard header
2. Click to trigger the installation prompt
3. Follow your browser's specific installation steps

### 🛠 PWA Development Technical Details

#### Service Worker (`/public/sw.js`)
- **Resource Caching**: Intelligent caching for offline support
- **Background Sync**: Queues offline actions for later synchronization
- **Push Notifications**: Handles push notification events
- **Cache Management**: Automatic cache updates and cleanup

#### Web App Manifest (`/public/manifest.json`)
- **App Metadata**: Name, description, and branding information
- **Display Configuration**: Standalone mode and theme colors
- **Icon Management**: Multiple icon sizes for different devices
- **Shortcuts**: Quick access to key application features

#### PWA Composable (`/resources/js/composables/usePWA.js`)
- **Installation Control**: Programmatic installation management
- **Connection Monitoring**: Real-time online/offline detection
- **Notification API**: Push notification management
- **Background Sync**: Utilities for offline data synchronization

## ⚙️ Configuration

### Environment Variables

Essential configuration in your `.env` file:

```env
# Application
APP_NAME="Customer Management Dashboard"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_dashboard
DB_USERNAME=root
DB_PASSWORD=

# Google OAuth Credentials
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback

# Session & Cache (Development)
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database

# Authentication
AUTH_GUARD=web
```

### Performance Configuration

For development, the application uses file-based caching and sessions. For production environments, consider:

- **Redis** for session and cache storage
- **Database** queue driver for background jobs
- **CDN** for static asset delivery
- **Environment-specific** cache configurations

## 🔧 Development

### Code Standards
This project follows **PSR-12** coding standards. Maintain code quality:

```bash
# Check code style
./vendor/bin/phpcs

# Fix code style automatically
./vendor/bin/phpcbf
```

### Asset Management
```bash
# Development with hot reloading
npm run dev

# Production build (optimized)
npm run build

# Watch for changes
npm run watch
```

### Database Management
```bash
# Fresh migration (resets database)
php artisan migrate:fresh

# Rollback migrations
php artisan migrate:rollback

# Create new migration
php artisan make:migration create_example_table
```

## 🔧 Troubleshooting

### Common Issues & Solutions

#### 1. **Database Connection Errors**
```bash
# Verify MySQL is running
sudo service mysql start  # Linux
brew services start mysql # macOS

# Check database exists
mysql -u root -p
CREATE DATABASE admin_dashboard;

# Verify credentials in .env
DB_CONNECTION=mysql
DB_DATABASE=admin_dashboard
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### 2. **Permission Errors**
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 3. **Asset Build Issues**
```bash
# Clear npm cache and rebuild
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
npm run build
```

#### 4. **Migration Problems**
```bash
# Reset and run fresh migrations
php artisan migrate:fresh
php artisan migrate:status
```

#### 5. **Cache Issues**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 🆘 Getting Help

- **Laravel Documentation**: [laravel.com/docs](https://laravel.com/docs)
- **Vue.js Guide**: [vuejs.org/guide](https://vuejs.org/guide/)
- **Inertia.js Docs**: [inertiajs.com](https://inertiajs.com)
- **Tailwind CSS**: [tailwindcss.com/docs](https://tailwindcss.com/docs)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests to ensure nothing breaks
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Changelog (Recent)

### 2025-08-26
- Disabled public registration & email verification.
- Enforced unique email constraint with test coverage.
- Implemented space & slot management (types, occupancy, pricing, discount logic, assignment & release).
- Unified Dashboard & Space Management card/grid styling.
- Removed Task Tracker feature (routes, nav links, controller logic, Vue pages). `Task` model & migration retained temporarily; relationships removed; scheduled for full removal after data audit.
- Removed tasks_count usage in customer listings and related eager load.
- Cleaned CSS: removed forced scrollbar and navbar 100vw hacks; adopted natural scroll with `scrollbar-gutter: stable` and hidden horizontal overflow.
- Improved pricing controls responsiveness in Space Management.
- Simplified customer assignment flow (assign button per space opens customer modal; no pre-selection step required).

### Pending / Next Steps
- Decide on permanent removal of `tasks` table & migration; create follow-up migration to drop table & model.
- Enhance customer assignment modal: search/filter & optional inline create.
- Further accessibility & ARIA refinements for modals.
