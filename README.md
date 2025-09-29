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

### Option 1: Using Docker (Recommended)

1. **Clone the repository**:

   ```bash
   git clone https://github.com/gustavocaldassouza/NicheNest
   cd NicheNest
   ```

2. **Start MySQL with Docker**:

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

## 📁 Project Structure

```
NicheNest/
├── data/
│   └── schema.sql              # Database schema and sample data
├── includes/
│   ├── auth.php                # Authentication functions
│   ├── config.php              # Database and app configuration
│   ├── functions.php           # Utility functions
│   ├── header.php              # Common header template
│   └── footer.php              # Common footer template
├── pages/
│   ├── admin.php               # Admin panel
│   ├── login.php               # User login page
│   ├── logout.php              # User logout handler
│   ├── posts.php               # Community posts page
│   ├── profile.php             # User profile page
│   └── register.php            # User registration page
├── public/
│   ├── css/
│   │   └── style.css           # Custom styles
│   ├── js/
│   │   └── script.js           # JavaScript functionality
│   ├── uploads/                # File upload directory
│   └── index.php               # Main homepage
├── router.php                  # Custom PHP router
└── README.md                   # This file
```

## 🌐 Available Routes

- **Home**: `http://localhost:8000/`
- **Login**: `http://localhost:8000/pages/login.php`
- **Register**: `http://localhost:8000/pages/register.php`
- **Posts**: `http://localhost:8000/pages/posts.php`
- **Profile**: `http://localhost:8000/pages/profile.php`
- **Admin**: `http://localhost:8000/pages/admin.php`

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

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Ensure MySQL is running
   - Check database credentials in `config.php`
   - Verify database exists and schema is imported

2. **Page Not Found Errors**:
   - Make sure you're using the router: `php -S localhost:8000 router.php`
   - Check that all files are in the correct directories

3. **Permission Errors**:
   - Ensure `public/uploads/` directory is writable
   - Check file permissions on the project directory

### Debug Mode

To enable debug mode, modify `includes/config.php`:

```php
// Add this line for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🤝 Contributing

This project is maintained by the Elite Team for LaSalle College Montreal's TechWeek 2025 event.

### Development Guidelines

1. Follow PSR-12 coding standards
2. Use meaningful variable and function names
3. Add comments for complex logic
4. Test all functionality before submitting changes

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support or questions:

- Check the troubleshooting section above
- Review the code comments for implementation details
- Contact the development team

---

**NicheNest** - Building focused communities for shared interests. 🌟
