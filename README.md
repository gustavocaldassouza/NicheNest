# NicheNest

**Micro-Community Platform for Focused Groups**

A modern PHP-based community platform designed for creating focused, interest-based groups where like-minded individuals can connect, share ideas, and build meaningful relationships.

## 🚀 Features

- **User Authentication**: Secure registration and login system
- **Community Posts**: Create and share posts with the community
- **User Profiles**: Customizable user profiles with bio and avatar support
- **Admin Panel**: Comprehensive admin interface for community management
  - **Role-Based Moderation**: Admin-only access to moderation tools
  - **Post Management**: Flag and delete inappropriate posts
  - **User Management**: Suspend and reactivate user accounts
  - **Moderation Logs**: Track all moderation actions with timestamps
  - **System Logs**: View and manage application logs with admin interface
- **Comprehensive Logging**: Multi-level logging system for debugging and monitoring
  - Authentication events (login, logout, failed attempts)
  - User actions (registration, posts, groups)
  - Moderation actions with full audit trail
  - Security events and database errors
  - Automatic log rotation and cleanup
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

### Deploy to Heroku (Production)

The fastest way to get NicheNest running in production:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

For detailed Heroku deployment instructions, see [HEROKU_DEPLOYMENT.md](HEROKU_DEPLOYMENT.md).

### Local Development

#### Option 1: Using Docker MySQL (Recommended)

1. **Build and run MySQL with Docker**:

   ```bash
   # Build the custom MySQL image
   docker build -f Dockerfile.mysql -t nichenest-mysql .
   
   # Run the MySQL container
   docker run -d --name nichenest-mysql -p 3306:3306 nichenest-mysql
   ```

2. **Start the PHP development server**:

   ```bash
   php -S localhost:8000 router.php
   ```

3. **Open your browser** and navigate to:

   ```
   http://localhost:8000
   ```

#### Option 2: Using Standard Docker MySQL

1. **Start MySQL with Docker**:

   ```bash
   docker run --name nichenest-mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=nichenest -p 3306:3306 -d mysql:8.0
   ```

2. **Import the database schema**:

   ```bash
   docker exec -i nichenest-mysql mysql -uroot -proot nichenest < data/schema.sql
   ```

3. **Start the PHP development server**:

   ```bash
   php -S localhost:8000 router.php
   ```

4. **Open your browser** and navigate to:

   ```
   http://localhost:8000
   ```

#### Option 3: Local MySQL Installation

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

## 🐳 Docker Management

### Building the MySQL Image

```bash
# Build the custom MySQL image
docker build -f Dockerfile.mysql -t nichenest-mysql .
```

### Running the Container

```bash
# Run the MySQL container
docker run -d --name nichenest-mysql -p 3306:3306 nichenest-mysql
```

### Container Management

```bash
# Check container status
docker ps

# View container logs
docker logs nichenest-mysql

# Stop the container
docker stop nichenest-mysql

# Remove the container
docker rm nichenest-mysql

# Connect to MySQL
docker exec -it nichenest-mysql mysql -u nichenest -pnichenest123 nichenest
```

### Database Connection Details

- **Host**: localhost
- **Port**: 3306
- **Database**: nichenest
- **Username**: nichenest
- **Password**: nichenest123
- **Root Password**: root

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

### Database Migration

If you're upgrading from an older version, run the migration script to add moderation features:

```bash
# Using Docker
docker exec -i nichenest-mysql mysql -uroot -proot nichenest < data/migration_moderation.sql

# Using local MySQL
mysql -u root -p nichenest < data/migration_moderation.sql
```

This migration adds:
- `flagged` column to posts table for marking inappropriate content
- `moderation_logs` table for tracking all moderation actions

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output escaping
- **Session Security**: Secure session management
- **File Upload Security**: Restricted file types and validation

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

## 👮 Admin & Moderation

### Admin Panel Features

The admin panel (`/pages/admin.php`) provides comprehensive moderation tools:

#### Dashboard Statistics
- Total users, posts, and replies
- Number of flagged posts requiring review

#### User Management
- View all registered users with their roles and status
- Suspend or reactivate user accounts
- Prevent suspended users from posting or accessing certain features

#### Post Management
- View all posts with flagged status indicators
- **Flag Posts**: Mark inappropriate or suspicious content for review
- **Unflag Posts**: Remove flags from reviewed content
- **Delete Posts**: Permanently remove posts and their replies
- Visual indicators: Flagged posts appear with warning badges and highlighted rows

#### Moderation Logs
- Complete audit trail of all moderation actions
- Tracks: moderator identity, action type, target, and timestamp
- Actions logged: flag/unflag posts, delete posts, suspend/activate users

### Access Control

Only users with `role = 'admin'` in the database can access the admin panel. The default admin credentials are:
- **Username**: admin
- **Password**: admin123

**Important**: Change the default admin password immediately after first login!

## 📊 Logging System

NicheNest includes a comprehensive logging system for monitoring and debugging:

### Features

- **Multi-level Logging**: DEBUG, INFO, WARNING, ERROR, CRITICAL
- **Automatic Rotation**: Log files rotate when they reach size limit
- **Security Protected**: Logs directory protected from web access
- **Admin Interface**: View and manage logs at `/pages/logs.php`
- **Structured Logging**: All logs include context data in JSON format

### What Gets Logged

- **Authentication**: Login/logout attempts, successes, and failures
- **User Actions**: Registration, post creation, group operations
- **Moderation**: All admin actions with full audit trail
- **Security Events**: Suspicious activity, unauthorized access attempts
- **Database Errors**: Query failures and connection issues
- **System Errors**: Application errors and exceptions

### Configuration

Configure logging via environment variables (see `.env.example`):

```bash
LOG_ENABLED=true              # Enable/disable logging
LOG_LEVEL=INFO                # Minimum log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
LOG_DIRECTORY=logs            # Directory for log files
LOG_MAX_FILE_SIZE=5242880     # Max file size before rotation (5MB)
LOG_MAX_FILES=5               # Number of rotated files to keep
```

### Viewing Logs

1. Access the admin panel at `/pages/admin.php`
2. Click "View System Logs" button
3. Select a log file to view its contents
4. Use the cleanup feature to remove old logs

For detailed logging documentation, see [LOGGING.md](LOGGING.md).

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**NicheNest** - Building focused communities for shared interests. 🌟
