<?php
/**
 * Plugin Update Checker Integration
 *
 * Handles automatic updates from GitHub releases
 *
 * @package CSPEDISCI
 * @since 1.1.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Path to the Plugin Update Checker library
$update_checker_path = CSPEDISCI_PLUGIN_DIR . 'core/lib/plugin-update-checker/plugin-update-checker.php';

// Check if the Plugin Update Checker library is installed
if ( ! file_exists( $update_checker_path ) ) {
	// Library not found - show admin notice
	add_action( 'admin_notices', 'cspedisci_update_checker_missing_notice' );
	return;
}

// Load the Plugin Update Checker library
require_once $update_checker_path;

// Import the factory class (required for version 5.x)
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize the update checker
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/jwebmodica/noispediamo-woocommerce/',  // Your GitHub repository URL
	CSPEDISCI_PLUGIN_FILE,                                      // Full path to the main plugin file
	'cspedisci-connector'                                       // Plugin slug
);

// Set the branch to check for updates (default: master or main)
$myUpdateChecker->setBranch('main');

// Optional: Set the release asset filter
// This ensures it downloads the correct ZIP file from releases
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

/**
 * For PRIVATE repositories only:
 * Uncomment and add your GitHub Personal Access Token
 */
// $myUpdateChecker->setAuthentication('YOUR-GITHUB-TOKEN-HERE');

/**
 * Optional: Add custom header for GitHub repository info
 * This allows WordPress to show "View details" link
 */
add_filter('puc_request_info_result-cspedisci-connector', 'cspedisci_add_plugin_info', 10, 2);
function cspedisci_add_plugin_info($pluginInfo, $result) {
	if (isset($result->body)) {
		$body = json_decode($result->body);
		if (isset($body->tag_name)) {
			$pluginInfo->version = ltrim($body->tag_name, 'v');
		}
	}
	return $pluginInfo;
}

/**
 * Admin notice if Plugin Update Checker library is not installed
 */
function cspedisci_update_checker_missing_notice() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php echo esc_html( CSPEDISCI_NAME ); ?>:</strong>
			<?php _e( 'Per abilitare gli aggiornamenti automatici, è necessario installare la libreria Plugin Update Checker.', 'cspedisci-connector' ); ?>
		</p>
		<p>
			<?php printf(
				__( 'Consulta la documentazione: %s', 'cspedisci-connector' ),
				'<code>core/lib/UPDATE_CHECKER_SETUP.md</code>'
			); ?>
		</p>
	</div>
	<?php
}
