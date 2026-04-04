# Environment Configuration Setup

This WHMS system now uses environment variables for configuration management.

## Quick Setup

1. **Copy the example file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** with your specific configuration values.

3. **Ensure proper permissions:**
   ```bash
   chmod 644 .env
   ```

## Configuration Sections

### Database
- `DB_HOST` - Database server hostname
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASSWORD` - Database password
- `DB_CHARSET` - Database character set
- `DB_TIMEZONE` - Database timezone

### Application
- `APP_NAME` - Application name
- `APP_ENV` - Environment (development/production)
- `APP_DEBUG` - Enable/disable debug mode
- `APP_URL` - Base URL of the application
- `APP_TIMEZONE` - Application timezone

### APIs
- `GOOGLE_MAPS_API_KEY` - Google Maps API key
- `DELHIVERY_API_KEY` - Delhivery courier API key
- `SHIPROCKET_EMAIL/SHIPROCKET_PASSWORD` - Shiprocket credentials

### Security
- `JWT_SECRET` - JWT signing secret
- `ENCRYPTION_KEY` - Application encryption key

### File Uploads
- `UPLOAD_MAX_SIZE` - Maximum upload file size
- `IMAGE_MAX_SIZE_KB` - Maximum image size after compression
- `IMAGE_COMPRESSION_QUALITY` - JPEG compression quality (0-100)

## Environment Helper Functions

The system provides helper functions in `config/env.php`:

- `env($key, $default)` - Get environment variable
- `envBool($key, $default)` - Get boolean environment variable
- `envInt($key, $default)` - Get integer environment variable
- `envArray($key, $default)` - Get comma-separated values as array

## Usage in Code

```php
// Get database host
$dbHost = env('DB_HOST', 'localhost');

// Get boolean setting
$debugMode = envBool('APP_DEBUG', false);

// Get integer setting
$pageSize = envInt('DEFAULT_PAGE_SIZE', 50);

// Get array setting
$allowedOrigins = envArray('ALLOWED_ORIGINS', []);
```

## Security Notes

- **Never commit `.env` to version control**
- Add `.env` to your `.gitignore` file
- Keep production environment variables secure
- Use different values for development and production

## Migration from Hardcoded Values

The system has been updated to use environment variables for:

- Database connection parameters
- Google Maps API key
- Application settings
- File upload configurations
- API endpoints and credentials

Hardcoded values have been replaced with environment variable calls with sensible defaults.

## Troubleshooting

1. **Environment variables not loading:**
   - Check that `.env` file exists in project root
   - Verify file permissions
   - Check that `config/env.php` is being included

2. **Database connection issues:**
   - Verify database credentials in `.env`
   - Check that database server is running
   - Ensure database name exists

3. **API key issues:**
   - Verify API keys are correctly set in `.env`
   - Check for extra spaces or quotes around values

## Development vs Production

### Development (.env)
```
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
```

### Production (.env)
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=production-db-server
```

## Adding New Configuration

1. Add the variable to `.env.example`
2. Add the variable to your local `.env`
3. Use `env('VARIABLE_NAME', 'default_value')` in your code
