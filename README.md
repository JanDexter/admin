# Customer Management Dashboard

A modern, full-featured web application built with Laravel 11, Inertia.js, and Vue.js for comprehensive customer relationship management. This application provides a streamlined admin interface focused on customer data management and co-workspace service reservations.
w
## Preview
![Customer Management Dashboard Preview 1](public/img/Screenshot%202025-08-07%20105409.png)
![Customer Management Dashboard Preview 2](public/img/Screenshot%202025-08-07%20105413.png)

## 🚀 About This Project

This project is a Customer Management Dashboard built with the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) and other modern technologies. It provides a comprehensive interface for managing customers, tasks, services, and users.

## ✨ Features

*   **User Roles:** Admin, Staff, and Customer roles with distinct permissions.
*   **Customer Management:** CRUD operations for customer records.
*   **Task Management:** Assign and track tasks related to customers.
*   **Service Management:** Define and manage services offered.
*   **User Management:** Admin interface to manage users.
*   **Responsive UI:** Built with Tailwind CSS for a mobile-first experience.
*   **PWA Ready:** Can be installed as a Progressive Web App.

## 🛠️ Tech Stack

The application is built using the following technologies:

*   **Backend:** Laravel (PHP)
*   **Frontend:** Vue.js with Inertia.js
*   **Styling:** Tailwind CSS
*   **Database:** MySQL
*   **Development:** Vite, Docker

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

8. **Seed the database with initial users**
   ```bash
   php artisan db:seed --class=UserRoleSeeder
   ```

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

### 🔑 Default Login Credentials

After running the seeder, you can use these test accounts:

| Role | Email | Password | Description |
|------|-------|----------|-------------|
| **Admin** | `admin@admin.com` | `password` | Full system access |
| **Staff** | `staff@example.com` | `password` | Staff-level access |
| **Customer** | `customer@example.com` | `password` | Customer-level access |

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
| `remember_token` | String | Remember me token |
| `created_at`, `updated_at` | Timestamps | Record timestamps |

### Customers Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | Primary Key | Unique identifier |
| `name` | String | Customer name |
| `email` | String (Unique) | Customer email |
| `phone` | String (Nullable) | Phone number |
| `company` | String (Nullable) | Company name |
| `address` | Text (Nullable) | Customer address |
| `status` | Enum | active/inactive |
| `service_type` | String (Nullable) | Selected service type |
| `service_price` | Decimal (Nullable) | Price of selected service |
| `service_start_time` | Timestamp (Nullable) | Service start time |
| `service_end_time` | Timestamp (Nullable) | Service end time |
| `amount_paid` | Decimal | Amount paid (default: 0) |
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
| GET | `/register` | Show registration form |
| POST | `/register` | Process registration |
| GET | `/forgot-password` | Show forgot password form |
| POST | `/forgot-password` | Send reset link |

> **Note:** Customer deletion has been removed for data integrity and audit purposes.

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
        'name', 'email', 'phone', 'company', 'address', 'status',
        'service_type', 'service_price', 'service_start_time', 
        'service_end_time', 'amount_paid'
    ];

    // Type casting for better data handling
    protected $casts = [
        'service_start_time' => 'datetime',
        'service_end_time' => 'datetime',
        'service_price' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Available service types with predefined pricing
    public static function getServiceTypes()
    {
        return [
            'CONFERENCE ROOM' => 350,
            'SHARED SPACE' => 40,
            'EXCLUSIVE SPACE' => 60,
            'PRIVATE SPACE' => 50,
            'DRAFTING TABLE' => 50,
        ];
    }

    // Formatted price accessors
    public function getFormattedServicePriceAttribute()
    {
        return $this->service_price ? '₱' . number_format($this->service_price, 2) : null;
    }

    public function getFormattedAmountPaidAttribute()
    {
        return '₱' . number_format($this->amount_paid, 2);
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
