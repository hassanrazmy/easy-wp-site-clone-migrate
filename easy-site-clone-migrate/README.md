# Easy Site Clone & Migrate Pro

A professional WordPress migration and cloning plugin designed for complete, safe, and reliable site transfers.

## Features

### ✅ Complete Site Export
- **All WordPress Core Files** (wp-admin, wp-includes)
- **All Plugins & Themes** 
- **All Uploads & Media Files**
- **Complete Database** with all tables
- **.htaccess** and configuration files
- **Excludes wp-config.php** for security (uses target server's DB settings)

### ✅ Safe Import Process
- **Pre-import backup** creation for rollback capability
- **Transaction-safe database imports** with COMMIT/ROLLBACK support
- **Serialized data handling** for widgets, options, and custom fields
- **Automatic URL replacement** when migrating to different domains
- **Preserves target wp-config.php** (database credentials from destination server)

### ✅ Advanced Technical Features
- **Chunked processing** (1000 rows per batch) prevents timeout on large sites
- **Extended execution time** management with set_time_limit()
- **Proper SQL parsing** handles strings, quotes, and special characters
- **Recursive directory copying** preserves file structure
- **ZIP compression** for efficient storage and transfer
- **Secure temp directory** with .htaccess protection

### ✅ Security & Safety
- **Nonce verification** on all AJAX requests
- **Capability checks** (manage_options required)
- **Protected temp directory** denies direct access
- **No wp-config.php export** prevents credential leakage
- **Confirmation dialogs** before destructive operations

## Installation

1. Download the plugin folder `easy-site-clone-migrate`
2. Upload to `/wp-content/plugins/` directory
3. Activate through WordPress Admin → Plugins
4. Navigate to **Tools → Site Clone & Migrate**

## Usage

### Export a Site

1. Go to **Tools → Site Clone & Migrate**
2. Click the **Export Site** tab
3. Click **Start Export**
4. Wait for the process to complete
5. Download the generated ZIP file automatically

### Import a Site

⚠️ **WARNING**: This will completely replace your current site!

1. Go to **Tools → Site Clone & Migrate**
2. Click the **Import Site** tab
3. Drag & drop or select your backup ZIP file
4. Click **Start Import**
5. Confirm the warning dialog
6. Wait for completion (site will reload automatically)

## How wp-config.php Handling Works

### During Export:
- wp-config.php is **explicitly excluded** from the backup
- This prevents database credentials from being transferred
- The backup remains secure and portable

### During Import:
- The existing wp-config.php on the **destination server is preserved**
- All imported content uses the **destination's database credentials**
- No manual configuration needed after import
- Automatically adapts to new server environment

This approach ensures:
- ✅ Security (no credential leakage)
- ✅ Portability (works on any server)
- ✅ Convenience (no manual setup)
- ✅ Safety (doesn't break database connection)

## Technical Specifications

| Feature | Implementation |
|---------|---------------|
| Database Export | Chunked SELECT with 1000 row batches |
| SQL Import | Transaction-based with error handling |
| File Copying | Recursive with exclusion patterns |
| Compression | PHP ZipArchive extension |
| URL Replacement | Both raw text and serialized data |
| Progress Tracking | Real-time log polling via AJAX |
| Timeout Prevention | set_time_limit() extension |
| Security | Nonce + Capability checks |

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- ZipArchive extension enabled
- Write permissions to wp-content/uploads
- Database CREATE, DROP, INSERT privileges

## Troubleshooting

### Export/Import Times Out
- Large sites may take several minutes
- Check server's max_execution_time setting
- Try during low-traffic periods

### "ZipArchive not available" Error
- Enable ZipArchive extension in php.ini
- Contact your hosting provider

### Permission Denied Errors
- Ensure wp-content/uploads is writable
- Check folder permissions (755 recommended)

### Database Import Fails
- Verify database user has sufficient privileges
- Check max_allowed_packet in MySQL config
- Review operation logs for specific errors

## Changelog

### Version 2.0.0
- ✅ Complete WordPress file export (all directories)
- ✅ Proper serialized data URL replacement
- ✅ Chunked processing for large sites
- ✅ Transaction-safe database imports
- ✅ Pre-import backup creation
- ✅ Better error handling and logging
- ✅ wp-config.php exclusion for security
- ✅ Modern responsive UI
- ✅ Real-time progress tracking

## Support

For issues and feature requests, please contact support.

## License

GPL v2 or later

---

**Note**: Always test migrations on a staging environment first before production use.
