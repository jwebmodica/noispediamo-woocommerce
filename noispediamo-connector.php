<?php
/**
 * NoiSpediamo Connector
 *
 * @package       CSPEDISCI
 * @author        Jweb
 * @license       gplv2
 * @version       2.0.3
 *
 * @wordpress-plugin
 * Plugin Name:   NoiSpediamo Connector
 * Plugin URI:    https://www.noispediamo.it
 * Description:   Invia i tuoi ordini woocommerce a Noispediamo.it tramite noispediamo-connector
 * Version:       2.0.3
 * Author:        Jweb
 * Author URI:    https://www.jwebmodica.it
 * Text Domain:   noispediamo-connector
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: YOUR-USERNAME/YOUR-REPOSITORY
 * GitHub Branch:     main
 * Requires PHP:      7.0
 * Requires at least: 5.0
 * Tested up to:      6.4
 *
 * You should have received a copy of the GNU General Public License
 * along with NoiSpediamo Connector. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'CSPEDISCI_NAME',			'NoiSpediamo Connector' );

// Plugin version
define( 'CSPEDISCI_VERSION',		'2.0.3' );

// Plugin Root File
define( 'CSPEDISCI_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'CSPEDISCI_PLUGIN_BASE',	plugin_basename( CSPEDISCI_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'CSPEDISCI_PLUGIN_DIR',	plugin_dir_path( CSPEDISCI_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'CSPEDISCI_PLUGIN_URL',	plugin_dir_url( CSPEDISCI_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once CSPEDISCI_PLUGIN_DIR . 'core/class-cspedisci-connector.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Jweb
 * @since   1.0.0
 * @return  object|cspedisci_Connector
 */
function CSPEDISCI() {
	return Cspedisci_Connector::instance();
}

CSPEDISCI();

/**
 * Initialize Plugin Update Checker
 * Checks for updates from GitHub releases
 */
require_once CSPEDISCI_PLUGIN_DIR . 'core/includes/update-checker.php';

/**
 * Check and create tables if they don't exist
 * This runs on every admin page load to ensure tables are always present
 */
add_action( 'admin_init', 'noispediamo_check_tables' );
function noispediamo_check_tables() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'noispediamo_settings';
	$tablecorrieri = $wpdb->prefix . 'noispediamo_corrieri';

	// Check if tables exist
	$settings_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	$corrieri_exists = $wpdb->get_var("SHOW TABLES LIKE '$tablecorrieri'") === $tablecorrieri;

	// If tables don't exist, create them
	if (!$settings_exists || !$corrieri_exists) {
		my_plugin_create_db();
	}
}

register_activation_hook( __FILE__, 'my_plugin_create_db' );
function my_plugin_create_db() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'noispediamo_settings';
	$tablecorrieri=$wpdb->prefix . 'noispediamo_corrieri';

	// Migration: Rename old tables if they exist
	$old_settings_table = $wpdb->prefix . 'cspedisci_settings';
	$old_corrieri_table = $wpdb->prefix . 'cspedisci_corrieri';

	// Check if old tables exist and new ones don't
	$old_settings_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_settings_table'") === $old_settings_table;
	$new_settings_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

	if ($old_settings_exists && !$new_settings_exists) {
		$wpdb->query("RENAME TABLE `$old_settings_table` TO `$table_name`");
	}

	$old_corrieri_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_corrieri_table'") === $old_corrieri_table;
	$new_corrieri_exists = $wpdb->get_var("SHOW TABLES LIKE '$tablecorrieri'") === $tablecorrieri;

	if ($old_corrieri_exists && !$new_corrieri_exists) {
		$wpdb->query("RENAME TABLE `$old_corrieri_table` TO `$tablecorrieri`");
	}

	// Ensure PRIMARY KEY exists on corrieri table (for both migrated and existing tables)
	if ($new_corrieri_exists || $old_corrieri_exists) {
		// Check if PRIMARY KEY already exists
		$pk_exists = $wpdb->get_var("SHOW KEYS FROM `$tablecorrieri` WHERE Key_name = 'PRIMARY'");

		if (!$pk_exists) {
			// No PRIMARY KEY exists, add it
			$wpdb->query("ALTER TABLE `$tablecorrieri` ADD PRIMARY KEY (`id`)");
		}
	}

	// Create settings table
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		`id` MEDIUMINT NOT NULL AUTO_INCREMENT,
		`nome` VARCHAR(200) NULL,
		`indirizzo` VARCHAR(250) NULL,
		`civico` VARCHAR(50) NULL,
		`cap` VARCHAR(20) NULL,
		`citta` VARCHAR(100) NULL,
		`prov` VARCHAR(100) NULL,
		`email` VARCHAR(200) NULL,
		`telefono` VARCHAR(50) NULL,
		`contrassegno_conto` VARCHAR(200) NULL,
		`contrassegno_iban` VARCHAR(50) NULL,
		`pagamento` VARCHAR(100) NOT NULL DEFAULT 'Paga Dopo',
		`corriere` MEDIUMINT NOT NULL DEFAULT '0',
		`password` VARCHAR(200) NULL,
		PRIMARY KEY (`id`)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Create couriers table
	$sql = "CREATE TABLE IF NOT EXISTS $tablecorrieri (
		`id` INT NOT NULL,
		`corriere` VARCHAR(200) NOT NULL,
		`tconsegna` VARCHAR(10) NOT NULL,
		PRIMARY KEY (`id`)
	) $charset_collate;";

	dbDelta( $sql );

	// Insert default settings row if not exists
	$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table_name (id) VALUES (%d)", 777 ) );

	// Insert default carriers
	$default_carriers = array(
		array( 'id' => 2, 'corriere' => 'BRT corriere espresso consegna stimata eccezione Isole e Calabria', 'tconsegna' => '1' ),
		array( 'id' => 3, 'corriere' => 'TNT consegna stimata eccezione Isole e Calabria', 'tconsegna' => '1' ),
		array( 'id' => 5, 'corriere' => 'SDA gruppo poste italiane consegna stimata eccezione Isole e Calabria', 'tconsegna' => '1' )
	);

	foreach ( $default_carriers as $carrier ) {
		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO $tablecorrieri (id, corriere, tconsegna) VALUES (%d, %s, %s)",
			$carrier['id'],
			$carrier['corriere'],
			$carrier['tconsegna']
		) );
	}
}


add_action('admin_menu', 'test_plugin_setup_menu');
 
add_action( 'admin_enqueue_scripts', 'khn_datepicker_css_and_js' );

function khn_datepicker_css_and_js() {
    wp_enqueue_script( 'jquery-ui-datepicker' );
}



function test_plugin_setup_menu(){
    add_menu_page( 'NoiSpediamo Connector', 'NoiSpediamo', 'manage_options', 'cspedisci-plugin', 'test_init', 'dashicons-products', 56 );
       add_submenu_page(
        'cspedisci-plugin',
        'Impostazioni', //page title
        'Impostazioni', //menu title
        'manage_options', //capability,
        'noispediamo-settings',//menu slug
        'cspedisci_settings' //callback function
    );
}

// se non esiste aggiungo stato spedito
function register_shipped_order_status() {
    register_post_status( 'wc-shipped', array(
        'label'                     => 'Spedito',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Spedito <span class="count">(%s)</span>', 'Spedito <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_shipped_order_status' );


add_filter( 'wc_order_statuses', 'custom_order_status');
function custom_order_status( $order_statuses ) {
    $order_statuses['wc-shipped'] = _x( 'Spedito', 'Order status', 'woocommerce' ); 
    return $order_statuses;
}


 
function test_init(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'noispediamo_settings';

    // Check if settings are configured
    $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", 777));

    // Validate required mittente fields
    $settings_configured = false;
    if ($settings &&
        !empty($settings->nome) &&
        !empty($settings->indirizzo) &&
        !empty($settings->cap) &&
        !empty($settings->citta) &&
        !empty($settings->prov) &&
        !empty($settings->email)) {
        $settings_configured = true;
    }

    // Get the selected limit from GET parameter, default to 10
    $orders_limit = isset($_GET['orders_limit']) ? absint($_GET['orders_limit']) : 10;

    // Validate the limit value
    $allowed_limits = array(10, 25, 50, 100);
    if (!in_array($orders_limit, $allowed_limits)) {
        $orders_limit = 10;
    }
    ?>

    <!-- Page Header -->
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <span class="dashicons dashicons-products" style="font-size: 32px; width: 32px; height: 32px;"></span>
            NoiSpediamo Connector
        </h1>

        <?php
        // Show warning if settings are not configured
        if (!$settings_configured) {
            echo '<div class="notice notice-error is-dismissible" style="padding: 15px; margin: 0 0 20px 0; border-left: 4px solid #dc3232; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
            echo '<h3 style="margin-top: 0;">⚠️ Configurazione Richiesta</h3>';
            echo '<p><strong>Prima di poter inviare spedizioni, devi configurare i dati del mittente nelle impostazioni del plugin.</strong></p>';
            echo '<p style="margin-bottom: 0;">Vai su <a href="?page=noispediamo-settings" class="button button-primary">Impostazioni NoiSpediamo</a> e compila tutti i campi obbligatori (Nome, Indirizzo, CAP, Città, Provincia, Email).</p>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Filter Section -->
    <div style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 15px;">
            <label for="orders_limit_select" style="font-weight: 600; margin-right: 10px;">Mostra</label>
            <select id="orders_limit_select" name="orders_limit" onchange="window.location.href='?page=cspedisci-plugin&orders_limit=' + this.value;" style="padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="10" <?php selected($orders_limit, 10); ?>>10</option>
                <option value="25" <?php selected($orders_limit, 25); ?>>25</option>
                <option value="50" <?php selected($orders_limit, 50); ?>>50</option>
                <option value="100" <?php selected($orders_limit, 100); ?>>100</option>
            </select>
            <span style="margin-left: 5px;">ordini da spedire per pagina</span>
        </div>

        <div style="border-top: 1px solid #eee; padding-top: 15px;">
            <h3 style="margin-top: 0; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                🔍 Filtri Ricerca
            </h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Data ordine</label>
                    <input type="date" id="filter_date" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    <small style="font-size: 11px; color: #999;">Seleziona la data degli ordini</small>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">ID Ordine</label>
                    <input type="text" id="filter_order_id" placeholder="12345" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    <small style="font-size: 11px; color: #999;">Cerca per numero ordine</small>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="button" class="button button-primary" onclick="applyFilters()" style="padding: 6px 15px;">Applica Filtri</button>
                <button type="button" class="button" onclick="clearFilters()" style="padding: 6px 15px;">Cancella Filtri</button>
            </div>
        </div>
    </div>

    <?php
    // Get corrieri from database
    $tablecorrieri = $wpdb->prefix . 'noispediamo_corrieri';
    $corrieri = $wpdb->get_results("SELECT * FROM $tablecorrieri");

    // Get default corriere from settings
    $default_corriere = $settings && !empty($settings->corriere) ? $settings->corriere : '';

    $query = new WC_Order_Query( array(
    'limit' => $orders_limit,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'ids',
    'status' => 'wc-processing',
) );
$orders = $query->get_orders();

echo '<div class="orders-container">';

if (empty($orders)) {
    echo '<div style="background: white; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
    echo '<p style="font-size: 16px; color: #666;">📦 Nessun ordine da spedire</p>';
    echo '</div>';
} else {
    echo '<div style="margin-bottom: 15px; font-weight: 600;">🚚 Ordini da Spedire (' . count($orders) . ')</div>';
    echo '<div style="font-size: 13px; color: #666; margin-bottom: 20px;">Compila i dettagli di spedizione per gli ordini non evasi.</div>';
}

foreach ($orders as $idordine) {
    $order = wc_get_order($idordine);
    $ordine = $order->get_address('shipping');
    $billing_email = $order->get_billing_email();
    $billing_phone = $order->get_billing_phone();
    $order_date = $order->get_date_created()->format('d/m/Y');
    $order_total = $order->get_total();

    // Calculate next available business day (skip weekends)
    $default_pickup_date = new DateTime('tomorrow');
    while ($default_pickup_date->format('N') >= 6) { // 6 = Saturday, 7 = Sunday
        $default_pickup_date->modify('+1 day');
    }
    $default_pickup_formatted = $default_pickup_date->format('d/m/Y');
    ?>

    <!-- Order Card -->
    <div id='order-card-<?php echo $idordine; ?>' class="order-card" data-order-id="<?php echo $idordine; ?>" data-order-date="<?php echo $order_date; ?>" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">

        <!-- Order Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f5f5f5;">
            <div>
                <span style="font-size: 14px; color: #666;">📦</span>
                <strong style="font-size: 15px; margin-left: 5px;">Ordine #<?php echo $idordine; ?></strong>
            </div>
            <div style="text-align: right; font-size: 13px; color: #666;">
                <?php echo $order_date; ?>
            </div>
        </div>

        <!-- Customer Info -->
        <div style="margin-bottom: 15px;">
            <div style="font-weight: 600; font-size: 14px; margin-bottom: 5px;">
                <?php echo esc_html(trim($ordine['company'] . ' ' . $ordine['first_name'] . ' ' . $ordine['last_name'])); ?> • <?php echo wc_price($order_total); ?>
            </div>
        </div>

        <!-- Shipping Address -->
        <div style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 14px;">📍</span>
                    <strong style="font-size: 13px;">Indirizzo di spedizione:</strong>
                </div>
                <button type="button" class="button button-small toggle-edit-address" style="font-size: 11px; padding: 4px 10px;">Modifica</button>
            </div>

            <!-- Read-only address display -->
            <div class="address-display" style="font-size: 13px; color: #666; line-height: 1.6;">
                <div style="margin-bottom: 3px;"><strong><?php echo esc_html(trim($ordine['first_name'] . ' ' . $ordine['last_name'])); ?></strong></div>
                <div><?php echo esc_html($ordine['address_1'] . ', ' . $ordine['city'] . ', ' . $ordine['state'] . ' ' . $ordine['postcode'] . ' • Tel: ' . $billing_phone); ?></div>
            </div>

            <!-- Editable address form (hidden by default) -->
            <div class="address-edit-form" style="display: none; background: #f7f9fc; border-radius: 6px; padding: 15px; margin-top: 10px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Nome</label>
                        <input type="text" class="edit-nome" value="<?php echo esc_attr($ordine['first_name']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Cognome</label>
                        <input type="text" class="edit-cognome" value="<?php echo esc_attr($ordine['last_name']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Via</label>
                        <input type="text" class="edit-indirizzo" value="<?php echo esc_attr($ordine['address_1']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Civico/Interno</label>
                        <input type="text" class="edit-civico" value="" placeholder="52" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Città</label>
                        <input type="text" class="edit-citta" value="<?php echo esc_attr($ordine['city']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Provincia</label>
                        <input type="text" class="edit-prov" value="<?php echo esc_attr($ordine['state']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">CAP</label>
                        <input type="text" class="edit-cap" value="<?php echo esc_attr($ordine['postcode']); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Telefono</label>
                    <input type="text" class="edit-telefono" value="<?php echo esc_attr($billing_phone); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Email</label>
                    <input type="text" class="edit-email" value="<?php echo esc_attr($billing_email); ?>" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                </div>

                <div>
                    <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Note (opzionale)</label>
                    <textarea class="edit-note" rows="2" placeholder="Es: solo giorni feriali, citofono non funzionante..." style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; resize: vertical;"><?php echo esc_attr($order->get_customer_note()); ?></textarea>
                    <small style="font-size: 10px; color: #999;">Max 100 caratteri</small>
                </div>

                <div style="margin-top: 10px; text-align: right;">
                    <button type="button" class="button button-small toggle-edit-address" style="font-size: 11px; padding: 4px 10px;">Chiudi</button>
                </div>
            </div>
        </div>

        <!-- Packages Section -->
        <input class="ordineid rigaordine" name="idordine" type="hidden" value="<?php echo $idordine;?>">

        <div style="background: #f7f9fc; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 12px;">
                <span style="font-size: 14px;">📦</span>
                <strong style="font-size: 13px;">Pacchi da spedire (1)</strong>
            </div>

            <div class="pacchi-container">
                <div class="pacco-row" data-pacco-index="0" style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-size: 12px; font-weight: 600;">Pacco 1 di 1</div>
                        <button type="button" class="button button-small rimuovi-pacco" style="display:none; font-size: 11px; padding: 2px 8px;">Rimuovi</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Peso (kg)</label>
                            <input type="text" class="peso" name="peso[]" placeholder="9.0" required="required" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Largh (cm)</label>
                            <input type="text" class="largh" name="largh[]" placeholder="30" required="required" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Alt (cm)</label>
                            <input type="text" class="alt" name="alt[]" placeholder="22" required="required" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #666; margin-bottom: 4px;">Prof (cm)</label>
                            <input type="text" class="prof" name="prof[]" placeholder="40" required="required" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="button button-small aggiungi-pacco" style="font-size: 11px; padding: 4px 12px; margin-top: 5px;">+ Aggiungi Pacco</button>
        </div>

        <!-- Shipping Options -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px;">Data ritiro</label>
                <input name="ritiro" class="ritiro my-datepicker" required="required" type="text" value="<?php echo esc_attr($default_pickup_formatted); ?>" placeholder="gg/mm/aaaa" style="width:100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 5px;">Corriere</label>
                <select name="corriere" class="corriere-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    <?php if (empty($corrieri)): ?>
                        <option value="">Nessun corriere disponibile</option>
                    <?php else: ?>
                        <option value="0" <?php selected(0, $default_corriere); ?>>Corriere con prezzo più basso</option>
                        <?php foreach ($corrieri as $corriere): ?>
                            <option value="<?php echo esc_attr($corriere->id); ?>" <?php selected($corriere->id, $default_corriere); ?>>
                                <?php echo esc_html($corriere->corriere . ' (' . $corriere->tconsegna . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Action Button -->
        <div style="text-align: right;">
            <?php if ($settings_configured): ?>
                <button type="button" class="button button-primary invia-ordine-btn" id="invia-<?php echo $idordine;?>" style="padding: 8px 20px; font-size: 13px;">Invia</button>
            <?php else: ?>
                <button type="button" class="button" style="opacity: 0.5; cursor: not-allowed; padding: 8px 20px; font-size: 13px;" title="Configura prima le impostazioni del mittente" onclick="return false;">Invia</button>
            <?php endif; ?>
        </div>

        <div class="nordine"></div>
    </div>
    <!-- End Order Card -->

    <?php
}

echo '</div><!-- End orders-container -->';
}

 
/*function cspedisci_settings(){
    echo "<h1>JwebShip Connector - Impostazioni e Guida</h1>
    <p>Per poter iniziare ad inviare le tue spedizioni ti servirà configurare il plugin inserendo tutte le voci richieste. <br>Inoltre dovrai comunicare a JwebShip l'indirizzo IP del tuo server</p>";
    register_setting('wpse61431_settings', 'wpse61431_settings', 'wpse61431_settings_validate');
    
}
*/


//The markup for your plugin settings page
function cspedisci_settings(){
    	global $wpdb;
    	$table_name = $wpdb->prefix . 'noispediamo_settings';
    	if ( isset( $_POST['email'] ) && isset( $_POST['cspedisci_settings_nonce'] ) && wp_verify_nonce( $_POST['cspedisci_settings_nonce'], 'cspedisci_save_settings' ) ){

            // Ensure the default row exists before updating
            $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table_name (id) VALUES (%d)", 777 ) );

            $wpdb->update(
                $table_name,
                array(
                    'email' => sanitize_email( $_POST['email'] ),
                    'password' => sanitize_text_field( $_POST['password'] ),
                    'nome' => sanitize_text_field( $_POST['nome'] ),
                    'indirizzo' => sanitize_text_field( $_POST['indirizzo'] ),
                    'cap' => sanitize_text_field( $_POST['cap'] ),
                    'citta' => sanitize_text_field( $_POST['citta'] ),
                    'civico' => sanitize_text_field( $_POST['civico'] ),
                    'prov' => sanitize_text_field( $_POST['prov'] ),
                    'telefono' => sanitize_text_field( $_POST['telefono'] ),
                    'contrassegno_conto' => sanitize_text_field( $_POST['contrassegno_conto'] ),
                    'contrassegno_iban' => sanitize_text_field( $_POST['contrassegno_iban'] ),
                    'pagamento' => sanitize_text_field( $_POST['pagamento'] ),
                    'corriere' => absint( $_POST['corriere'] ),
                ),
                array('id' => 777),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
                array('%d')
            );

            // Automatically refresh corrieri list after saving settings
            $username = sanitize_email( $_POST['email'] );
            $password = sanitize_text_field( $_POST['password'] );

            if ( !empty( $username ) && !empty( $password ) ) {
                $tablecorrieri = $wpdb->prefix . 'noispediamo_corrieri';
                $basicauth = base64_encode( $username . ':' . $password );

                // Make API call to get corrieri
                $response = wp_remote_get( 'https://ordini.noispediamo.it/cspedisci-api/spedizione', array(
                    'timeout' => 30,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Basic ' . $basicauth
                    )
                ) );

                if ( ! is_wp_error( $response ) ) {
                    $body = wp_remote_retrieve_body( $response );
                    $risposta = json_decode( $body, true );

                    if ( isset( $risposta['errors'] ) && $risposta['errors'] == 0 ) {
                        // Truncate existing carriers
                        $wpdb->query( "TRUNCATE TABLE $tablecorrieri" );

                        // Insert new carriers
                        if ( isset( $risposta['corrieri'] ) && is_array( $risposta['corrieri'] ) ) {
                            foreach ( $risposta['corrieri'] as $corriere ) {
                                $wpdb->insert(
                                    $tablecorrieri,
                                    array(
                                        'id' => absint( $corriere['id_corriere'] ),
                                        'corriere' => sanitize_text_field( $corriere['nome'] ),
                                        'tconsegna' => sanitize_text_field( $corriere['tconsegna'] ) . ' gg'
                                    ),
                                    array( '%d', '%s', '%s' )
                                );
                            }
                        }
                    }
                }
            }

            // Show success message
            echo '<div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p><strong>Impostazioni salvate con successo!</strong></p></div>';
        }

    	$tablecorrieri=$wpdb->prefix . 'noispediamo_corrieri';
   $posts = $wpdb->get_row("SELECT * FROM $table_name WHERE id=777");
   $curriers = $wpdb->get_results("SELECT * FROM $tablecorrieri");
   
   // Echo the title of the most commented post
?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 32px; width: 32px; height: 32px;"></span>
            NoiSpediamo Connector - Impostazioni
        </h1>
        <p style="font-size: 14px; color: #666; margin-bottom: 30px;">Per poter iniziare ad inviare le tue spedizioni ti servirà configurare il plugin inserendo tutte le voci richieste.</p>

    <form action="" method="post" style="max-width: 900px;">
        <?php wp_nonce_field( 'cspedisci_save_settings', 'cspedisci_settings_nonce' ); ?>

        <!-- Login Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-admin-users" style="font-size: 20px; color: #0073aa;"></span>
                Login
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Credenziali di accesso alla tua area privata Noispediamo.it</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Username</label>
                    <input name="email" type="text" id="email" value="<?php echo (isset( $posts->email) &&  $posts->email != '') ?  $posts->email : ''; ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Password</label>
                    <input name="password" type="password" id="password" value="<?php echo (isset( $posts->password) &&  $posts->password != '') ?  $posts->password : ''; ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
            </div>
        </div>

        <!-- Sender Information Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-location" style="font-size: 20px; color: #0073aa;"></span>
                Nome Mittente
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Verrà usato come mittente di tutti gli ordini inviati</p>

            <div>
                <input name="nome" type="text" id="nome" value="<?php echo (isset( $posts->nome) &&  $posts->nome != '') ?  $posts->nome : ''; ?>" placeholder="Es: Carmelo Test" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
            </div>
        </div>

        <!-- Address Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-admin-home" style="font-size: 20px; color: #0073aa;"></span>
                Indirizzo
            </h3>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Via/Piazza</label>
                    <input name="indirizzo" type="text" id="indirizzo" value="<?php echo (isset( $posts->indirizzo) &&  $posts->indirizzo != '') ?  $posts->indirizzo : ''; ?>" placeholder="via del provolone" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">N. civico</label>
                    <input name="civico" type="text" id="civico" value="<?php echo (isset( $posts->civico) &&  $posts->civico != '') ?  $posts->civico : ''; ?>" placeholder="123" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Città</label>
                    <input name="citta" type="text" id="citta" value="<?php echo (isset( $posts->citta) &&  $posts->citta != '') ?  $posts->citta : ''; ?>" placeholder="Modica" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">CAP</label>
                    <input name="cap" type="text" id="cap" value="<?php echo (isset( $posts->cap) &&  $posts->cap != '') ?  $posts->cap : ''; ?>" placeholder="97015" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Sigla Prov</label>
                    <input name="prov" type="text" id="prov" value="<?php echo (isset( $posts->prov) &&  $posts->prov != '') ?  $posts->prov : ''; ?>" placeholder="RG" maxlength="2" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px; text-transform: uppercase;"/>
                </div>
            </div>
        </div>

        <!-- Phone Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-phone" style="font-size: 20px; color: #0073aa;"></span>
                Telefono Mittente
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Verrà inviato al corriere per il ritiro del pacco</p>

            <div>
                <input name="telefono" type="text" id="telefono" value="<?php echo (isset( $posts->telefono) &&  $posts->telefono != '') ?  $posts->telefono : ''; ?>" placeholder="3334545345" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
            </div>
        </div>

        <!-- Bank Account Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-money-alt" style="font-size: 20px; color: #0073aa;"></span>
                Conto Corrente
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Se si effettuano spedizioni con contrassegno verrà usato questo conto per lo storno del contrassegno.</p>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Intestatario Conto</label>
                <input name="contrassegno_conto" type="text" id="contrassegno_conto" value="<?php echo (isset( $posts->contrassegno_conto) &&  $posts->contrassegno_conto != '') ?  $posts->contrassegno_conto : ''; ?>" placeholder="Carmelo Test" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">IBAN conto corrente</label>
                <input name="contrassegno_iban" type="text" id="contrassegno_iban" value="<?php echo (isset( $posts->contrassegno_iban) &&  $posts->contrassegno_iban != '') ?  $posts->contrassegno_iban : ''; ?>" placeholder="IT12345" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;"/>
            </div>
        </div>

        <!-- Payment Method Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-cart" style="font-size: 20px; color: #0073aa;"></span>
                Metodo di pagamento
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Se scegli Paga dopo dovrai entrare nella tua area privata e pagare i tuoi ordini prima che possano essere spediti.</p>

            <div>
                <select name="pagamento" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;">
                    <option <?php if($posts->pagamento=="Paga Dopo") echo "selected='selected'";?> value="Paga Dopo">Paga i tuoi ordini dopo (non saranno spediti se non pagati)</option>
                    <option <?php if($posts->pagamento=="Credito") echo "selected='selected'";?> value="Credito">Usa il credito - spediti subito</option>
                </select>
            </div>
        </div>

        <!-- Carrier Section -->
        <div style="background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-products" style="font-size: 20px; color: #0073aa;"></span>
                Corriere
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Puoi scegliere un corriere predefinito oppure il corriere con il prezzo più basso verrà scelto automaticamente.</p>

            <div style="display: flex; gap: 10px; align-items: center;">
                <select name="corriere" style="flex: 1; padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 14px;">
                    <option <?php if($posts->corriere=="0") echo "selected='selected'";?> value="0">Corriere con prezzo più basso</option>
                    <?php foreach ( $curriers as $corriere ) { ?>
                        <option <?php if($posts->corriere==$corriere->id) echo "selected='selected'";?> value="<?php echo $corriere->id; ?>">
                            <?php echo $corriere->corriere; ?> - Tempo di consegna: <?php echo $corriere->tconsegna; ?>
                        </option>
                    <?php } ?>
                </select>
                <button type="button" id="refreshcorrieri" class="button" style="white-space: nowrap;">Refresh lista corrieri</button>
            </div>
        </div>

        <!-- Submit Button -->
        <div style="text-align: right;">
            <input type="submit" value="Salva Impostazioni" class="button button-primary button-hero" style="font-size: 16px; padding: 12px 30px;"/>
        </div>
    </form>
</div>




<?php }
?>