# NicheNest

**Micro-Community Platform for Focused Groups**

A modern PHP-based community platform designed for creating focused, interest-based groups where like-minded individuals can connect, share ideas, and build meaningful relationships.

## 🚀 Features

- **User Authentication**: Secure registration and login system
- **Community Posts**: Create and share posts with the community
- **User Profiles**: Customizable user profiles with bio and avatar support
- **Admin Panel**: Comprehensive admin interface for community management
- **Responsive Design**: Modern, mobile-friendly interface using Bootstrap 5
- **Real-time Interactions**: Like, reply, and engage with community content
- **Session Management**: Secure session handling with proper logout functionality

## 🛠️ Technology Stack

- **Backend**: PHP 8.4+
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5.3
- **Icons**: Bootstrap Icons
- **Server**: PHP Built-in Development Server

## 📋 Prerequisites

Before running NicheNest, ensure you have the following installed:

- **PHP 8.4+** with PDO MySQL extension
- **MySQL 8.0+** or **Docker** (for containerized MySQL)
- **Web Browser** (Chrome, Firefox, Safari, Edge)

## 🚀 Quick Start

### Option 1: Using Docker Compose (Recommended)

1. **Clone the repository**:

   ```bash
   git clone https://github.com/gustavocaldassouza/NicheNest
   cd NicheNest
   ```

2. **Start all services with Docker Compose**:

   ```bash
   # Copy environment file
   cp env.example .env
   
   # Start services
   docker-compose up -d
   ```

3. **Access the application**:
   - **Application**: <http://localhost:8080>
   - **phpMyAdmin**: <http://localhost:8081>

### Option 2: Using Docker (Manual Setup)

1. **Start MySQL with Docker**:

   ```bash
   docker run --name nichenest-mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=nichenest -p 3306:3306 -d mysql:8.0
   ```

3. **Import the database schema**:

   ```bash
   docker exec -i nichenest-mysql mysql -uroot -proot nichenest < data/schema.sql
   ```

4. **Start the PHP development server**:

   ```bash
   php -S localhost:8000 router.php
   ```

5. **Open your browser** and navigate to:

   ```
   http://localhost:8000
   ```

### Option 2: Local MySQL Installation

1. **Set up MySQL** and create a database named `nichenest`
2. **Import the schema**:

   ```bash
   mysql -u root -p nichenest < data/schema.sql
   ```

3. **Update database credentials** in `includes/config.php` if needed
4. **Start the server**:

   ```bash
   php -S localhost:8000 router.php
   ```

## 🔧 Configuration

### Database Configuration

Edit `includes/config.php` to match your database setup:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'nichenest');
define('DB_USER', 'root');
define('DB_PASS', 'root');
```

### Application Settings

```php
define('APP_NAME', 'NicheNest');
define('APP_URL', 'http://localhost:8000');
define('UPLOAD_PATH', 'uploads/');
```

## 👥 Default Accounts

The application comes with pre-configured test accounts:

### Admin Account

- **Email**: `admin@nichenest.local`
- **Password**: `admin123`
- **Role**: Administrator

### Test Users

- **Email**: `john@example.com`
- **Password**: `admin123`
- **Display Name**: John Doe

- **Email**: `jane@example.com`
- **Password**: `admin123`
- **Display Name**: Jane Smith

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output escaping
- **Session Security**: Secure session management
- **File Upload Security**: Restricted file types and validation

## 🐳 Docker & Jenkins Deployment

### Docker Setup

NicheNest is fully containerized for easy deployment:

```bash
# Development
docker-compose up -d

# Production
docker-compose -f docker-compose.prod.yml up -d
```

## 🎨 Customization

### Styling

- Edit `public/css/style.css` for custom styles
- Bootstrap 5 classes are used throughout for consistent design

### JavaScript

- Custom functionality in `public/js/script.js`
- Includes form validation, dynamic interactions, and UI enhancements

### Templates

- Header and footer templates in `includes/` directory
- Consistent navigation and layout across all pages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**NicheNest** - Building focused communities for shared interests. 🌟
