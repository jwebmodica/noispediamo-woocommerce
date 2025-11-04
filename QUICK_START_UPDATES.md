# Quick Start Guide: Custom Plugin Updates

This plugin is configured to receive automatic updates from GitHub releases (or a custom server).

## Quick Setup (5 minutes)

### Step 1: Download Plugin Update Checker Library

```bash
cd core/lib/
wget https://github.com/YahnisElsts/plugin-update-checker/archive/refs/tags/v5.3.zip
unzip v5.3.zip
mv plugin-update-checker-5.3 plugin-update-checker
rm v5.3.zip
```

Or manually download from: https://github.com/YahnisElsts/plugin-update-checker/releases

### Step 2: Create GitHub Repository

1. Go to https://github.com/new
2. Create a repository (e.g., `cspedisci-connector`)
3. Keep it private or public as needed
4. Note your repository URL

### Step 3: Update Configuration

Edit `core/includes/update-checker.php` and replace:
```php
'https://github.com/YOUR-USERNAME/YOUR-REPOSITORY/'
```

With your actual repository:
```php
'https://github.com/jwebmodica/cspedisci-connector/'
```

Also update in `cspedisci-connector.php` line 21:
```php
* GitHub Plugin URI: jwebmodica/cspedisci-connector
```

### Step 4: Push Code to GitHub

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git
git push -u origin main
```

### Step 5: Create First Release

1. Go to your repository on GitHub
2. Click "Releases" → "Create a new release"
3. Tag version: `v1.1.10`
4. Release title: `Version 1.1.10`
5. Description: Write changelog
6. **Important:** Upload the plugin as a ZIP file
   - Create ZIP of the plugin folder
   - Name it: `cspedisci-connector-1.1.10.zip`
   - Upload as release asset
7. Click "Publish release"

### Step 6: Test

On a WordPress site with this plugin installed:
1. Go to Dashboard → Updates
2. Check for updates
3. Should see "Cspedisci Connector" update available

## Creating Future Updates

### When you want to release version 1.2.0:

1. **Update version in code:**
   - Line 8 in `cspedisci-connector.php`: `@version 1.2.0`
   - Line 14 in `cspedisci-connector.php`: `Version: 1.2.0`
   - Line 32 in `cspedisci-connector.php`: `CSPEDISCI_VERSION', '1.2.0'`

2. **Commit and push:**
   ```bash
   git add .
   git commit -m "Version 1.2.0 - Added new features"
   git push
   ```

3. **Create GitHub Release:**
   - Tag: `v1.2.0`
   - Upload new ZIP file
   - Publish release

4. **WordPress sites automatically check for updates every 12 hours**

## For Private Repositories

If your repository is private, you need a GitHub Personal Access Token:

1. Go to: https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Give it a name: "Plugin Updates"
4. Select scope: `repo` (Full control of private repositories)
5. Generate and copy the token

Add to `core/includes/update-checker.php`:
```php
$myUpdateChecker->setAuthentication('ghp_your_token_here');
```

**Security:** Never commit this token. Use wp-config.php:
```php
// In wp-config.php
define('CSPEDISCI_GITHUB_TOKEN', 'ghp_your_token_here');

// In update-checker.php
if (defined('CSPEDISCI_GITHUB_TOKEN')) {
    $myUpdateChecker->setAuthentication(CSPEDISCI_GITHUB_TOKEN);
}
```

## Alternative: Custom Server (No GitHub)

If you don't want to use GitHub, you can host updates on your own server.

Create a JSON file at: `https://yourdomain.com/updates/cspedisci-connector.json`

```json
{
  "version": "1.2.0",
  "download_url": "https://yourdomain.com/downloads/cspedisci-connector-1.2.0.zip",
  "requires": "5.0",
  "tested": "6.4",
  "requires_php": "7.0",
  "last_updated": "2024-01-15",
  "sections": {
    "description": "Plugin description here",
    "changelog": "<h3>1.2.0</h3><ul><li>New feature added</li><li>Bug fixes</li></ul>"
  }
}
```

Update `core/includes/update-checker.php`:
```php
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://yourdomain.com/updates/cspedisci-connector.json',
    CSPEDISCI_PLUGIN_FILE,
    'cspedisci-connector'
);
```

## Troubleshooting

### Updates not appearing?
- Check version number in plugin header
- Verify GitHub release tag format: `v1.2.0` (with 'v')
- Clear WordPress transients: Delete `wp_update_plugins` transient
- Enable WP_DEBUG to see errors

### Manual check for updates:
```php
// In WordPress admin, add this temporarily
delete_site_transient('update_plugins');
wp_update_plugins();
```

### Check what version GitHub sees:
Visit: `https://api.github.com/repos/YOUR-USERNAME/YOUR-REPOSITORY/releases/latest`

## Benefits

✓ No need for WordPress.org submission
✓ Control over update timing
✓ Works with private plugins
✓ Professional update experience
✓ Can roll back if needed
✓ Works exactly like WordPress.org updates

## Support

For detailed documentation, see: `core/lib/UPDATE_CHECKER_SETUP.md`

Plugin Update Checker library: https://github.com/YahnisElsts/plugin-update-checker
