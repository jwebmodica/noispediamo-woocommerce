<?php
/**
 * NoiSpediamo Connector
 *
 * @package       CSPEDISCI
 * @author        Jweb
 * @license       gplv2
 * @version       1.1.10
 *
 * @wordpress-plugin
 * Plugin Name:   NoiSpediamo Connector
 * Plugin URI:    https://www.noispediamo.it
 * Description:   Invia i tuoi ordini woocommerce a Noispediamo.it tramite cspedisci-connector
 * Version:       1.1.10
 * Author:        Jweb
 * Author URI:    https://www.jwebmodica.it
 * Text Domain:   cspedisci-connector
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
define( 'CSPEDISCI_VERSION',		'1.1.10' );

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

register_activation_hook( __FILE__, 'my_plugin_create_db' );
function my_plugin_create_db() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'cspedisci_settings';
	$tablecorrieri=$wpdb->prefix . 'cspedisci_corrieri';
	
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
		INDEX (`id`)
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
    add_menu_page( 'Test Plugin Page', 'Noispediamo', 'manage_options', 'cspedisci-plugin', 'test_init' );
       add_submenu_page(
        'cspedisci-plugin',
        'Impostazioni', //page title
        'Impostazioni', //menu title
        'manage_options', //capability,
        'cpsedisci-settings',//menu slug
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
    // Get the selected limit from GET parameter, default to 10
    $orders_limit = isset($_GET['orders_limit']) ? absint($_GET['orders_limit']) : 10;

    // Validate the limit value
    $allowed_limits = array(10, 25, 50, 100);
    if (!in_array($orders_limit, $allowed_limits)) {
        $orders_limit = 10;
    }

    echo "<h1 id='soprafeedback'>JwebShip Connector</h1><p>Invia le tue spedizioni direttamente a Noispediamo con 1 click</p>";
    ?>
    <div style="margin-bottom: 20px;">
        <label for="orders_limit_select" style="font-weight: 600;">Mostra ordini: </label>
        <select id="orders_limit_select" name="orders_limit" onchange="window.location.href='?page=cspedisci-plugin&orders_limit=' + this.value;">
            <option value="10" <?php selected($orders_limit, 10); ?>>10</option>
            <option value="25" <?php selected($orders_limit, 25); ?>>25</option>
            <option value="50" <?php selected($orders_limit, 50); ?>>50</option>
            <option value="100" <?php selected($orders_limit, 100); ?>>100</option>
        </select>
    </div>
    <?php
    $query = new WC_Order_Query( array(
    'limit' => $orders_limit,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'ids',
    'status' => 'wc-processing',
) );
$orders = $query->get_orders();
   // print_r($orders);
  //  $order = wc_get_order();
  echo "<table class='cspedisci'><thead><tr><th>Id ordine</th><th>Destinatario</th><th>Indirizzo Spedizione</th><th>Informazioni Pacchi</th><th>Opzioni</th><th>Stato</th><tr></thead><tbody>";
    foreach ($orders as $idordine) {
         $order = wc_get_order($idordine);
         $ordine=$order->get_address('shipping');
         $billing_email = $order->get_billing_email();
         $billing_phone = $order->get_billing_phone();
    echo "<tr id='trordine-$idordine'><td class='rigaordine'>$idordine</td><td>"; ?>

    <div class="destinatario-info">
        <div style="margin-bottom: 5px;">
            <input type="text" class="dest-nome" placeholder="Nome completo" value="<?php echo esc_attr(trim($ordine['company'] . ' ' . $ordine['first_name'] . ' ' . $ordine['last_name'])); ?>" style="width: 100%;">
        </div>
        <div style="margin-bottom: 5px;">
            <input type="text" class="dest-email" placeholder="Email" value="<?php echo esc_attr($billing_email); ?>" style="width: 100%;">
        </div>
        <div style="margin-bottom: 5px;">
            <input type="text" class="dest-telefono" placeholder="Telefono" value="<?php echo esc_attr($billing_phone); ?>" style="width: 100%;">
        </div>
    </div>

    </td><td>

    <div class="indirizzo-info">
        <div style="margin-bottom: 5px;">
            <input type="text" class="dest-indirizzo" placeholder="Indirizzo" value="<?php echo esc_attr($ordine['address_1']); ?>" style="width: 70%; margin-right: 2%;">
            <input type="text" class="dest-civico" placeholder="N." value="" style="width: 25%;">
        </div>
        <div style="margin-bottom: 5px;">
            <input type="text" class="dest-citta" placeholder="Città" value="<?php echo esc_attr($ordine['city']); ?>" style="width: 48%; margin-right: 2%;">
            <input type="text" class="dest-cap" placeholder="CAP" value="<?php echo esc_attr($ordine['postcode']); ?>" style="width: 23%; margin-right: 2%;">
            <input type="text" class="dest-prov" placeholder="Prov" value="<?php echo esc_attr($ordine['state']); ?>" style="width: 23%;">
        </div>
        <div>
            <textarea class="dest-note" placeholder="Note per il corriere" rows="2" style="width: 100%; resize: vertical;"><?php echo esc_attr($order->get_customer_note()); ?></textarea>
        </div>
    </div>

    </td><td>"; ?>
    <input class="ordineid" name="idordine" type="hidden" value="<?php echo $idordine;?>">

    <div class="pacchi-container">
        <div class="pacco-row" data-pacco-index="0">
            <div style="margin-bottom: 5px;">
                <strong>Pacco #1</strong>
                <button type="button" class="button button-small rimuovi-pacco" style="display:none; margin-left: 10px;">Rimuovi</button>
            </div>
            <input type="text" class="peso" name="peso[]" required="required" placeholder="Peso in kg" style="width:90px; margin-right: 5px;">
            <input type="text" class="largh" name="largh[]" required="required" placeholder="Larg cm" style="width:90px; margin-right: 5px;">
            <input type="text" class="alt" name="alt[]" required="required" placeholder="Alt cm" style="width:90px; margin-right: 5px;">
            <input type="text" class="prof" name="prof[]" required="required" placeholder="Prof cm" style="width:90px;">
        </div>
    </div>

    <button type="button" class="button button-small aggiungi-pacco" style="margin-top: 10px;">+ Aggiungi Pacco</button>

    <div style="margin-top: 10px;">
        <input name="ritiro" class="ritiro my-datepicker" required="required" type="text" placeholder="Data ritiro" style="width:100%;">
    </div>

    </td><td><a class="button button-secondary invia-ordine-btn" id="invia-<?php echo $idordine;?>" href="">Invia Ordine</a></td>
    <?php
   //     print_r($order->get_address('shipping'));
    }
    echo "<td><span class=\"nordine\"></span></td></tr></tbody></table>";
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
    	$table_name = $wpdb->prefix . 'cspedisci_settings';
    	if ( isset( $_POST['email'] ) && isset( $_POST['cspedisci_settings_nonce'] ) && wp_verify_nonce( $_POST['cspedisci_settings_nonce'], 'cspedisci_save_settings' ) ){

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
        }
    	
    	
    	
    	
    	
    	$tablecorrieri=$wpdb->prefix . 'cspedisci_corrieri';
   $posts = $wpdb->get_row("SELECT * FROM $table_name WHERE id=777");
   $curriers = $wpdb->get_results("SELECT * FROM $tablecorrieri");
   
   // Echo the title of the most commented post
?>
    <div class="wrap">
    <h2>NoiSpediamo Connector - Impostazioni e Guida</h2>
    <p>Per poter iniziare ad inviare le tue spedizioni ti servirà configurare il plugin inserendo tutte le voci richieste.</p>
    <form action="" method="post">
        <?php wp_nonce_field( 'cspedisci_save_settings', 'cspedisci_settings_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">Login<br><span class="description" style="font-weight:normal">Credenziali di accesso alla tua area privata Noispediamo.it</span></th>
                <td>
                    <fieldset>
                        <label>
                        <div><input name="email" type="text" id="email" value="<?php echo (isset( $posts->email) &&  $posts->email != '') ?  $posts->email : ''; ?>"/><br>
                        <span class="description">Username: </span></div><div>
                           <input name="password" type="password" id="password" value="<?php echo (isset( $posts->password) &&  $posts->password != '') ?  $posts->password : ''; ?>"/><br><span class="description">Password: </span></div>
                        </label>
                        
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Nome Mittente</th>
                <td>
                    <fieldset>
                        <label>
                             <input style="width:300px" name="nome" type="text" id="nome" value="<?php echo (isset( $posts->nome) &&  $posts->nome != '') ?  $posts->nome : ''; ?>"/>
                        </label>
                        <br />
                            <span class="description">Verrà usato come mittente di tutti gli ordini inviati</span>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Indirizzo</th>
                <td>
                    <fieldset>
                        <label>
                        <div style="display:inline-block;">
                             <input style="width:300px" name="indirizzo" type="text" id="indirizzo" value="<?php echo (isset( $posts->indirizzo) &&  $posts->indirizzo != '') ?  $posts->indirizzo : ''; ?>"/>
                            <br />
                            <span class="description">Via/Piazza etc</span>
                        </div>
                         <div style="display:inline-block;">
                             <input style="width:100px" name="civico" type="text" id="civico" value="<?php echo (isset( $posts->civico) &&  $posts->civico != '') ?  $posts->civico : ''; ?>"/>
                            <br />
                            <span class="description">N. civico</span>
                        </div>
                        <div style="display:inline-block;">
                             <input name="citta" type="text" id="citta" value="<?php echo (isset( $posts->citta) &&  $posts->citta != '') ?  $posts->citta : ''; ?>"/>
                            <br />
                            <span class="description">Città</span>
                        </div>
                        <div style="display:inline-block;">
                             <input style="width:80px" name="cap" type="text" id="cap" value="<?php echo (isset( $posts->cap) &&  $posts->cap != '') ?  $posts->cap : ''; ?>"/>
                            <br />
                            <span class="description">CAP</span>
                        </div>
                        <div style="display:inline-block;">
                             <input style="width:50px" name="prov" type="text" id="prov" value="<?php echo (isset( $posts->prov) &&  $posts->prov != '') ?  $posts->prov : ''; ?>"/>
                            <br />
                            <span class="description">Sigla Prov</span>
                        </div>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Telefono Mittente</th>
                <td>
                    <fieldset>
                        <label>
                             <input style="" name="telefono" type="text" id="nome" value="<?php echo (isset( $posts->telefono) &&  $posts->telefono != '') ?  $posts->telefono : ''; ?>"/>
                        </label>
                        <br />
                            <span class="description">Verrà inviato al corriere per il ritiro del pacco</span>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Conto Corrente<br><span class="description" style="font-weight:normal">Se si effettuano spedizioni con contrassegno verrà usato questo conto per lo storno del contrassegno.</span></th>
                <td>
                    <fieldset>
                        <label>
                        <div><input name="contrassegno_conto" type="text" id="contrassegno_conto" value="<?php echo (isset( $posts->contrassegno_conto) &&  $posts->contrassegno_conto != '') ?  $posts->contrassegno_conto : ''; ?>"/><br>
                        <span class="description">Intestatario Conto</span></div><div>
                           <input name="contrassegno_iban" type="text" id="contrassegno_iban" value="<?php echo (isset( $posts->contrassegno_iban) &&  $posts->contrassegno_iban != '') ?  $posts->contrassegno_iban : ''; ?>"/><br><span class="description">IBAN conto corrente</span></div>
                        </label>
                        
                    </fieldset>
                </td>
            </tr>
             <tr>
                <th scope="row">Metodo di pagamento</th>
                <td>
                    <fieldset>
                        <label>
                             <select name="pagamento">
                                 <option <?php if($posts->pagamento=="Paga Dopo") echo "selected='selected'";?> value="Paga Dopo">Paga i tuoi ordini dopo (non saranno spediti se non pagati)</option>
                                 <option <?php if($posts->pagamento=="Credito") echo "selected='selected'";?> value="Credito">Usa il credito - spediti subito</option>
                             </select>
                            
                        </label>
                        <br />
                            <span class="description">Se scegli Paga dopo dovrai entrare nella tua area privata e pagare i tuoi ordini prima che possano essere spediti.</span>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Corriere</th>
                <td>
                    <fieldset>
                        <label>
                             <select name="corriere">
                                 <option <?php if($posts->corriere=="0") echo "selected='selected'";?> value="0">Corriere con prezzo più basso</option>
                              <?php  foreach ( $curriers as $corriere ) { ?>
                              <option <?php if($posts->corriere==$corriere->id) echo "selected='selected'";?>  value="<?php echo $corriere->id; ?>"><?php echo $corriere->corriere; ?> - Tempo di consegna: <?php echo $corriere->tconsegna; ?></option>
                              <?php } ?>
                             </select>  - <button id="refreshcorrieri" class="btn">Refresh lista corrieri</button>
                            
                        </label>
                        <br />
                            <span class="description">Puoi scegliere un corriere predefinito oppure il corriere con il prezzo più basso verrà scelto automaticamente.</span>
                    </fieldset>
                </td>
            </tr>
        </table>
        <input type="submit" value="Save" />
    </form>
</div>




<?php }
?>