# SECURITY CONFIGURATION DOCUMENTATION

## Configuration Files Protection

### Main .htaccess (Root)
- Denies direct access to `config.php`, `db_config.php`, `session_check.php`
- Blocks access to backup files (.bak, .backup, .old, .tmp, .swp)
- Prevents directory listing
- Protects .htaccess itself

### uploads/id_pictures/.htaccess
- Disables PHP execution in upload directory
- Only allows image files (.jpg, .jpeg, .png, .gif)
- Prevents execution of uploaded malicious files

## Database Credentials

### Current Setup
All database credentials are centralized in `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'homesync');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Production Recommendation
For production environments, use environment variables:

1. Update `config.php` to use getenv():
```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'homesync');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

2. Set environment variables in Apache vhost:
```apache
SetEnv DB_HOST "localhost"
SetEnv DB_NAME "homesync_prod"
SetEnv DB_USER "homesync_user"
SetEnv DB_PASS "secure_password_here"
```

Or use a `.env` file with a library like `vlucas/phpdotenv`.

## Session Management

### Implemented Security Features
1. **Session Timeout** - 10 minutes (600 seconds) defined in `SESSION_TIMEOUT`
2. **Session Regeneration** - ID regenerated every 5 minutes to prevent fixation
3. **Logout Functionality** - `logout.php` destroys session properly
4. **Auto-check** - Session validated on every page load via `session_check.php`

### Session Security in Files
- `session_check.php` - Validates timeout and regenerates session ID
- `logout.php` - Properly destroys session
- All protected pages use `requireLogin()` function

## File Upload Security

### Validation Implemented in `tenants.php`
1. **File Type Validation** - Only image MIME types allowed (image/jpeg, image/png, image/gif)
2. **Extension Validation** - Only .jpg, .jpeg, .png, .gif extensions allowed
3. **Size Limit** - Maximum 5MB per file
4. **Secure Filenames** - Generated using `uniqid('id_', true)` to prevent path traversal
5. **Directory Permissions** - Set to 0755 (not 0777) to prevent execution
6. **PHP Execution Blocked** - .htaccess in uploads directory prevents PHP execution

### Upload Directory Structure
```
uploads/
└── id_pictures/
    ├── .htaccess (blocks PHP execution)
    └── [uploaded images]
```

## No Sensitive Files in Web Root

### Verified Clean
- ✅ No .env files
- ✅ No .bak backup files
- ✅ No .log files exposed
- ✅ No .git directory (if using version control, add to .htaccess)

### Recommended .htaccess Addition (if using Git)
```apache
<DirectoryMatch "^\.|\/\.">
    Order allow,deny
    Deny from all
</DirectoryMatch>
```

## Security Checklist

- [x] Config files protected from direct web access
- [x] Database credentials centralized in config.php
- [x] Session timeout implemented (10 minutes)
- [x] Logout functionality exists
- [x] Session regeneration prevents fixation
- [x] File upload validation (type, size, extension)
- [x] Upload directory secured (no PHP execution)
- [x] No backup/log files in web root
- [x] Directory listing disabled
- [x] All SQL queries use prepared statements

## Additional Recommendations

### For Production
1. Enable HTTPS and set session cookies to secure-only
2. Add CSP (Content Security Policy) headers
3. Implement rate limiting for login attempts
4. Add CSRF tokens to forms
5. Enable error logging to files (not browser display)
6. Use environment variables for all secrets
7. Regular security audits and updates
