# Plugin Update Checker Setup

## Step 1: Download the Library

Download the Plugin Update Checker library and place it in this directory.

### Option A: Download from GitHub
1. Go to: https://github.com/YahnisElsts/plugin-update-checker
2. Download the latest release (v5.x recommended)
3. Extract and copy the `plugin-update-checker` folder to: `core/lib/`

### Option B: Use Composer (if available)
```bash
composer require yahnis-elsts/plugin-update-checker
```

## Step 2: File Structure

After installation, you should have:
```
core/lib/plugin-update-checker/
├── plugin-update-checker.php
├── Puc/
└── (other library files)
```

## Step 3: GitHub Repository Setup

### Create a GitHub Repository
1. Create a new repository (private or public) on GitHub
2. Example: `https://github.com/yourusername/cspedisci-connector`
3. Push your plugin code to this repository

### Create a Release
1. Go to your repository on GitHub
2. Click "Releases" → "Create a new release"
3. Create a tag matching your plugin version (e.g., `v1.1.10`)
4. Upload the plugin ZIP file as a release asset
5. Write release notes
6. Click "Publish release"

**IMPORTANT:** The ZIP file must contain the plugin folder with all files.

Example structure of ZIP:
```
cspedisci-connector-1.1.10.zip
└── cspedisci-connector/
    ├── cspedisci-connector.php
    ├── core/
    ├── readme.txt
    └── (all other plugin files)
```

## Step 4: Update the Plugin File

The plugin has already been configured to use the update checker.
Just update the GitHub repository URL in `cspedisci-connector.php`

## How Updates Work

1. Every 12 hours, WordPress checks for updates
2. The plugin connects to your GitHub repository
3. Checks for new releases (by comparing version numbers)
4. If a new version is found, it appears in WordPress Updates
5. Users can click "Update Now" just like any WordPress.org plugin

## Creating New Updates

1. Update the version in `cspedisci-connector.php` (line 8 and 32)
2. Commit and push changes to GitHub
3. Create a new release with the new version tag
4. Upload the plugin ZIP file
5. WordPress sites will automatically detect the update

## For Private Repositories

If using a private GitHub repository, you'll need to:

1. Generate a GitHub Personal Access Token
2. Add to the update checker initialization:
```php
$myUpdateChecker->setAuthentication('your-github-token');
```

## Alternative: Custom Update Server

Instead of GitHub, you can host a JSON file on your own server.

Create a JSON file at: `https://yoursite.com/plugin-updates.json`

```json
{
  "version": "1.2.0",
  "download_url": "https://yoursite.com/downloads/cspedisci-connector-1.2.0.zip",
  "requires": "5.0",
  "tested": "6.4",
  "sections": {
    "description": "Plugin description",
    "changelog": "<h3>1.2.0</h3><ul><li>New feature added</li></ul>"
  }
}
```

Then update the initialization to:
```php
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://yoursite.com/plugin-updates.json',
    __FILE__,
    'cspedisci-connector'
);
```

## Troubleshooting

### Updates not showing
- Check plugin version number in header
- Verify GitHub release tag matches version
- Check WordPress update cache: `wp_update_plugins` transient
- Enable WP_DEBUG to see error messages

### Testing Updates
- Temporarily lower your plugin version number
- Go to Dashboard → Updates
- Should see update available

## Security Notes

- For private repos, keep your access token secure
- Never commit tokens to public repositories
- Use environment variables or wp-config.php constants
