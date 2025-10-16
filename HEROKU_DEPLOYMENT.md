# Heroku Deployment Guide

This guide will help you deploy NicheNest to Heroku.

## Prerequisites

1. A [Heroku account](https://signup.heroku.com/)
2. [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli) installed
3. Git installed and configured

## Quick Deploy

Click the button below to deploy NicheNest to Heroku in one click:

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

## Manual Deployment

### 1. Login to Heroku

```bash
heroku login
```

### 2. Create a New Heroku App

```bash
heroku create your-app-name
```

Replace `your-app-name` with your desired app name, or omit it to let Heroku generate one for you.

### 3. Add ClearDB MySQL Add-on

NicheNest requires a MySQL database. Add the ClearDB add-on:

```bash
heroku addons:create cleardb:ignite
```

The `ignite` plan is free and provides 5MB of storage. For production use, consider upgrading to a larger plan:

```bash
# For more storage options
heroku addons:create cleardb:punch  # 1GB - $9.99/month
heroku addons:create cleardb:drift  # 5GB - $49.99/month
```

### 4. Get Database Credentials

Heroku automatically sets the `CLEARDB_DATABASE_URL` environment variable. Verify it's set:

```bash
heroku config:get CLEARDB_DATABASE_URL
```

### 5. Set Application URL

Set the APP_URL environment variable to your Heroku app URL:

```bash
heroku config:set APP_URL=https://your-app-name.herokuapp.com
```

### 6. Deploy Your Code

```bash
git push heroku main
```

If you're on a different branch:

```bash
git push heroku your-branch:main
```

### 7. Import Database Schema

After deployment, import the database schema:

```bash
# Get database credentials
heroku config:get CLEARDB_DATABASE_URL

# The URL format is: mysql://username:password@hostname/database?reconnect=true
# Extract the credentials and use them with mysql client or use heroku run

# Option 1: Using heroku run with MySQL client
heroku run bash
# Then in the Heroku shell:
mysql -h hostname -u username -p database_name < data/schema.sql
exit

# Option 2: Use a local MySQL client with the ClearDB credentials
mysql -h hostname -u username -p database_name < data/schema.sql
```

**Note:** Replace `hostname`, `username`, and `database_name` with values from your CLEARDB_DATABASE_URL.

### 8. Create Admin User

After importing the schema, you need to create an admin user. You can do this by:

1. Register a new user through the web interface
2. Connect to the database and update the user's role:

```bash
# Connect to ClearDB database
heroku run bash
mysql -h hostname -u username -p database_name

# In MySQL shell:
UPDATE users SET role = 'admin' WHERE username = 'your_username';
exit
```

### 9. Open Your App

```bash
heroku open
```

## Environment Variables

NicheNest uses the following environment variables on Heroku:

- `CLEARDB_DATABASE_URL` - Automatically set by the ClearDB add-on
- `APP_NAME` - Application name (default: NicheNest)
- `APP_URL` - Your Heroku app URL (required)
- `UPLOAD_PATH` - Upload directory path (default: uploads/)

Set additional environment variables:

```bash
heroku config:set APP_NAME="My Community"
heroku config:set UPLOAD_PATH="uploads/"
```

## Viewing Logs

Monitor your application logs:

```bash
heroku logs --tail
```

## File Uploads on Heroku

⚠️ **Important:** Heroku's filesystem is ephemeral. Uploaded files will be lost when the dyno restarts (at least once per day).

For production use, you should integrate a cloud storage service like:
- Amazon S3
- Cloudinary
- Google Cloud Storage

Consider implementing one of these solutions for persistent file storage.

## Scaling Your App

### Horizontal Scaling (More Dynos)

```bash
heroku ps:scale web=2
```

### Vertical Scaling (Larger Dyno)

```bash
heroku ps:type hobby    # $7/month
heroku ps:type standard # From $25/month
```

## Database Management

### View Database Info

```bash
heroku addons:info cleardb
```

### Access Database

```bash
heroku config:get CLEARDB_DATABASE_URL
# Use the credentials to connect with any MySQL client
```

### Backup Database

```bash
# Connect to database and export
mysqldump -h hostname -u username -p database_name > backup.sql
```

### Upgrade Database Plan

```bash
heroku addons:upgrade cleardb:punch
```

## Troubleshooting

### App Crashes

Check the logs:

```bash
heroku logs --tail
```

### Database Connection Issues

Verify ClearDB is configured:

```bash
heroku addons
heroku config:get CLEARDB_DATABASE_URL
```

### PHP Version Issues

The app requires PHP 8.0 or higher. Check your composer.json file to ensure the correct version is specified.

### Missing Extensions

If you need additional PHP extensions, add them to composer.json:

```json
{
  "require": {
    "php": "^8.0",
    "ext-pdo": "*",
    "ext-pdo_mysql": "*",
    "ext-gd": "*"
  }
}
```

## Maintenance Mode

Enable maintenance mode:

```bash
heroku maintenance:on
```

Disable maintenance mode:

```bash
heroku maintenance:off
```

## Continuous Deployment

### GitHub Integration

1. Go to your app dashboard on Heroku
2. Navigate to the "Deploy" tab
3. Connect your GitHub repository
4. Enable automatic deploys from your main branch

### Manual Deploy from Branch

```bash
git push heroku branch-name:main
```

## Cost Optimization

### Free Tier

- 1 free web dyno (sleeps after 30 minutes of inactivity)
- ClearDB Ignite (5MB database) - Free
- Total: $0/month

### Production Ready

- Hobby dyno (never sleeps) - $7/month
- ClearDB Punch (1GB database) - $9.99/month
- Total: ~$17/month

## Security Best Practices

1. **Change Default Admin Password:** After deployment, immediately change the default admin credentials (admin/admin123)

2. **Use Environment Variables:** Never commit sensitive data to your repository

3. **Enable HTTPS:** Heroku provides free SSL certificates for all apps

4. **Keep Dependencies Updated:** Regularly update your PHP dependencies

## Additional Resources

- [Heroku PHP Documentation](https://devcenter.heroku.com/categories/php-support)
- [ClearDB Documentation](https://devcenter.heroku.com/articles/cleardb)
- [Heroku CLI Commands](https://devcenter.heroku.com/articles/heroku-cli-commands)

## Support

For issues specific to:
- **NicheNest:** Open an issue on [GitHub](https://github.com/gustavocaldassouza/NicheNest/issues)
- **Heroku Platform:** Check [Heroku Help](https://help.heroku.com/)
- **ClearDB:** Visit [ClearDB Support](https://www.cleardb.com/support)
