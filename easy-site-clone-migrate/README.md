# Easy Site Clone & Migrate

The best easy WordPress migration and cloning plugin. Export, import, and clone sites with ease.

## Features

- **Full Site Export**: Create complete backups including database and wp-content files
- **Database Export**: Export only the database as SQL file
- **Files Export**: Export only wp-content directory (themes, plugins, uploads)
- **Full Site Import**: Restore complete site from backup
- **Database Import**: Import database only
- **Files Import**: Import wp-content files only
- **URL Replacement**: Automatically updates site URLs during import for seamless migration
- **Secure**: Protected backup directory with .htaccess access control
- **User-Friendly Interface**: Clean, intuitive admin interface

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

Alternatively:

1. Upload the `easy-site-clone-migrate` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

### Export Your Site

1. Go to **Tools → Site Clone & Migrate**
2. In the "Export Site" section, choose your export option:
   - **Export Full Site**: Creates a complete backup (recommended)
   - **Export Database Only**: Downloads just the database
   - **Export Files Only**: Downloads wp-content directory
3. Wait for the export to complete
4. Download the generated backup file
5. Store it in a safe location

### Import/Clone Your Site

⚠️ **Warning**: Importing will overwrite your current site data! Always create a backup first.

1. Go to **Tools → Site Clone & Migrate**
2. In the "Import Site" section, click "Choose File"
3. Select your previously exported ZIP file
4. Click "Upload File"
5. Once uploaded, choose your import option:
   - **Import Full Site**: Restores everything (database + files)
   - **Import Database Only**: Restores just the database
   - **Import Files Only**: Restores wp-content files
6. Confirm the warning message
7. Wait for the import to complete
8. You may need to log in again after import

## How It Works

### Export Process

1. **Database Export**: The plugin exports all WordPress database tables with proper SQL formatting
2. **File Export**: Compresses wp-content directory (themes, plugins, uploads) into ZIP
3. **Metadata**: Includes site information (URL, version, active plugins, theme) in JSON format
4. **Packaging**: Combines everything into a single ZIP file for easy download

### Import Process

1. **File Upload**: Securely uploads the backup file to a temporary directory
2. **Extraction**: Extracts the ZIP file contents
3. **Database Import**: Parses and executes SQL statements to restore database
4. **URL Replacement**: Automatically updates old site URLs to match the new domain
5. **File Restoration**: Copies wp-content files to their proper locations
6. **Cache Clear**: Flushes WordPress cache for fresh content
7. **Cleanup**: Removes temporary files

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Only administrators can use the plugin
- **Directory Protection**: Backup directory is protected with .htaccess
- **Path Validation**: Prevents directory traversal attacks
- **File Type Validation**: Only accepts ZIP and SQL files

## Technical Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- ZipArchive extension enabled
- Write permissions to wp-content/uploads directory

## File Structure

```
easy-site-clone-migrate/
├── easy-site-clone-migrate.php    # Main plugin file
├── download.php                    # Download handler
├── includes/
│   ├── class-exporter.php         # Export functionality
│   ├── class-importer.php         # Import functionality
│   └── class-admin.php            # Admin interface
├── assets/
│   ├── css/
│   │   └── admin.css              # Admin styles
│   └── js/
│       └── admin.js               # Admin JavaScript
└── README.md                       # This file
```

## Troubleshooting

### Export fails or times out
- Increase PHP max_execution_time in php.ini
- Increase memory_limit in php.ini
- Try exporting database and files separately

### Import fails
- Check file permissions on wp-content directory
- Ensure upload_max_filesize and post_max_size are sufficient
- Check error logs for specific errors

### "Permission denied" error
- Ensure you're logged in as administrator
- Check user capabilities in WordPress

### Large site issues
- For very large sites, consider exporting database and files separately
- Use FTP to transfer wp-content directory manually
- Consider using a dedicated backup solution for very large sites

## Changelog

### Version 1.0.0
- Initial release
- Full site export/import functionality
- Database export/import
- Files export/import
- Automatic URL replacement
- Secure admin interface

## Support

For support and feature requests, please visit the plugin repository or contact the developer.

## License

This plugin is licensed under GPL v2 or later.

```
Copyright (C) 2024 Your Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```
