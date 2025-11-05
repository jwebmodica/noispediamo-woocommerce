<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Cspedisci_Connector_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		Cspedisci
 * @subpackage	Classes/Cspedisci_Connector_Run
 * @author		Jweb
 * @since		1.0.0
 */
class Cspedisci_Connector_Run{

	/**
	 * Our Cspedisci_Connector_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks(){

		add_action( 'plugin_action_links_' . CSPEDISCI_PLUGIN_BASE, array( $this, 'add_plugin_action_link' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ), 20 );
		add_action( 'plugins_loaded', array( $this, 'add_wp_webhooks_integrations' ), 9 );

		// AJAX hooks
		add_action( 'wp_ajax_cspedisci_invia_ordine', array( $this, 'ajax_invia_ordine' ) );
		add_action( 'wp_ajax_cspedisci_ottieni_corrieri', array( $this, 'ajax_ottieni_corrieri' ) );

	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	* Adds action links to the plugin list table
	*
	* @access	public
	* @since	1.0.0
	*
	* @param	array	$links An array of plugin action links.
	*
	* @return	array	An array of plugin action links.
	*/
	public function add_plugin_action_link( $links ) {

		$links['our_shop'] = sprintf( '<a target="_blank" href="%s" title="Custom Link" style="font-weight:700;">%s</a>', 'https://www.noispediamo.it', __( 'Scopri NoiSpediamo', 'cpsedisci-connector' ) );

		return $links;
	}

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		wp_enqueue_style( 'cspedisci-backend-styles', CSPEDISCI_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), CSPEDISCI_VERSION, 'all' );
		wp_enqueue_script( 'cspedisci-backend-scripts', CSPEDISCI_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array('jquery'), CSPEDISCI_VERSION, false );
		wp_localize_script( 'cspedisci-backend-scripts', 'cspedisci', array(
			'plugin_name'   	=> __( CSPEDISCI_NAME, 'cspedisci-connector' ),
			'ajax_url'			=> admin_url( 'admin-ajax.php' ),
			'nonce_invia_ordine' => wp_create_nonce( 'cspedisci_invia_ordine_nonce' ),
			'nonce_ottieni_corrieri' => wp_create_nonce( 'cspedisci_ottieni_corrieri_nonce' ),
		));
	}

	/**
	 * ####################
	 * ### WP Webhooks 
	 * ####################
	 */

	/*
	 * Register dynamically all integrations
	 * The integrations are available within core/includes/integrations.
	 * A new folder is considered a new integration.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function add_wp_webhooks_integrations(){

		// Abort if WP Webhooks is not active
		if( ! function_exists('WPWHPRO') ){
			return;
		}

		$custom_integrations = array();
		$folder = CSPEDISCI_PLUGIN_DIR . 'core' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'integrations';

		try {
			$custom_integrations = WPWHPRO()->helpers->get_folders( $folder );
		} catch ( Exception $e ) {
			WPWHPRO()->helpers->log_issue( $e->getTraceAsString() );
		}

		if( ! empty( $custom_integrations ) ){
			foreach( $custom_integrations as $integration ){
				$file_path = $folder . DIRECTORY_SEPARATOR . $integration . DIRECTORY_SEPARATOR . $integration . '.php';
				WPWHPRO()->integrations->register_integration( array(
					'slug' => $integration,
					'path' => $file_path,
				) );
			}
		}
	}

	/**
	 * ####################
	 * ### AJAX Handlers
	 * ####################
	 */

	/**
	 * AJAX handler for sending order to Noispediamo API
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function ajax_invia_ordine() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cspedisci_invia_ordine_nonce' ) ) {
			wp_send_json_error( array( 'issue' => 'Verifica di sicurezza fallita.' ) );
		}

		// Check user capability
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'issue' => 'Permessi insufficienti.' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'cspedisci_settings';

		// Get settings
		$posts = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", 777 ) );

		if ( ! $posts ) {
			wp_send_json_error( array( 'issue' => 'Impostazioni non trovate. Configura il plugin.' ) );
		}

		// Validate required mittente fields
		if ( empty( $posts->nome ) || empty( $posts->indirizzo ) || empty( $posts->cap ) ||
		     empty( $posts->citta ) || empty( $posts->prov ) || empty( $posts->email ) ) {
			wp_send_json_error( array( 'issue' => 'Dati del mittente incompleti. Vai su Impostazioni e compila tutti i campi obbligatori (Nome, Indirizzo, CAP, Città, Provincia, Email).' ) );
		}

		$username = $posts->email;
		$password = $posts->password;
		$basicauth = base64_encode( $username . ':' . $password );

		// Sanitize input variables
		$idordine = isset( $_POST['idordine'] ) ? absint( $_POST['idordine'] ) : 0;
		$ritiro = isset( $_POST['ritiro'] ) ? sanitize_text_field( $_POST['ritiro'] ) : '';
		$pacchi_json = isset( $_POST['pacchi'] ) ? stripslashes( $_POST['pacchi'] ) : '';
		$destinatario_json = isset( $_POST['destinatario'] ) ? stripslashes( $_POST['destinatario'] ) : '';

		// Validate order exists
		$order = wc_get_order( $idordine );
		if ( ! $order ) {
			wp_send_json_error( array( 'issue' => 'Problemi nel ritrovare l\'ordine' ) );
		}

		// Parse and validate destination data
		$destinatario_data = json_decode( $destinatario_json, true );
		if ( empty( $destinatario_data ) || ! is_array( $destinatario_data ) ) {
			wp_send_json_error( array( 'issue' => 'Dati destinatario non validi' ) );
		}

		// Sanitize destination data
		$destinatario = array(
			'nome' => isset( $destinatario_data['nome'] ) ? sanitize_text_field( $destinatario_data['nome'] ) : '',
			'indirizzo' => isset( $destinatario_data['indirizzo'] ) ? sanitize_text_field( $destinatario_data['indirizzo'] ) : '',
			'civico' => isset( $destinatario_data['civico'] ) ? sanitize_text_field( $destinatario_data['civico'] ) : '',
			'cap' => isset( $destinatario_data['cap'] ) ? sanitize_text_field( $destinatario_data['cap'] ) : '',
			'citta' => isset( $destinatario_data['citta'] ) ? sanitize_text_field( $destinatario_data['citta'] ) : '',
			'prov' => isset( $destinatario_data['prov'] ) ? sanitize_text_field( $destinatario_data['prov'] ) : '',
			'email' => isset( $destinatario_data['email'] ) ? sanitize_email( $destinatario_data['email'] ) : '',
			'telefono' => isset( $destinatario_data['telefono'] ) ? sanitize_text_field( $destinatario_data['telefono'] ) : '',
			'note' => isset( $destinatario_data['note'] ) ? sanitize_textarea_field( $destinatario_data['note'] ) : ''
		);

		// Validate required destination fields
		if ( empty( $destinatario['nome'] ) || empty( $destinatario['indirizzo'] ) || empty( $destinatario['cap'] ) || empty( $destinatario['citta'] ) || empty( $destinatario['prov'] ) ) {
			wp_send_json_error( array( 'issue' => 'Campi obbligatori del destinatario mancanti' ) );
		}

		// Parse and validate packages
		$pacchi_data = json_decode( $pacchi_json, true );
		if ( empty( $pacchi_data ) || ! is_array( $pacchi_data ) ) {
			wp_send_json_error( array( 'issue' => 'Dati pacchi non validi' ) );
		}

		// Validate each package
		$pacchi_array = array();
		foreach ( $pacchi_data as $pacco ) {
			$peso = isset( $pacco['weight'] ) ? floatval( $pacco['weight'] ) : 0;
			$alt = isset( $pacco['height'] ) ? floatval( $pacco['height'] ) : 0;
			$largh = isset( $pacco['width'] ) ? floatval( $pacco['width'] ) : 0;
			$prof = isset( $pacco['length'] ) ? floatval( $pacco['length'] ) : 0;

			if ( $peso <= 0 || $alt <= 0 || $largh <= 0 || $prof <= 0 ) {
				wp_send_json_error( array( 'issue' => 'Specifica le dimensioni corrette per tutti i pacchi' ) );
			}

			$pacchi_array[] = array(
				"weight" => $peso,
				"height" => $alt,
				"length" => $prof,
				"width" => $largh
			);
		}

		// Validate pickup date
		if ( empty( $ritiro ) ) {
			wp_send_json_error( array( 'issue' => 'Indicare una data di ritiro valida' ) );
		}

		$date = str_replace( '/', '-', $ritiro );
		$dataritiro = date( 'Y-m-d', strtotime( $date ) );
		$domani = date( 'Y-m-d', strtotime( "+1 day" ) );
		$duesett = date( 'Y-m-d', strtotime( "+14 day" ) );

		if ( $dataritiro < $domani || $dataritiro > $duesett ) {
			wp_send_json_error( array( 'issue' => 'Non puoi prenotare un ritiro con più di 2 settimane di anticipo.' ) );
		}

		// Get order details (only for payment info)
		$tipopagamento = $order->get_payment_method_title();
		$totale = $order->get_total();

		// Build API parameters using submitted destination data
		$parametri = array(
			"mittente" => array(
				"nome" => $posts->nome,
				"indirizzo" => $posts->indirizzo,
				"civico" => $posts->civico,
				"cap" => $posts->cap,
				"citta" => $posts->citta,
				"prov" => $posts->prov,
				"email" => $posts->email,
				"telefono" => $posts->telefono,
				"note" => ""
			),
			"destinatario" => array(
				"nome" => $destinatario['nome'],
				"indirizzo" => $destinatario['indirizzo'],
				"civico" => $destinatario['civico'],
				"cap" => $destinatario['cap'],
				"citta" => $destinatario['citta'],
				"prov" => $destinatario['prov'],
				"email" => $destinatario['email'],
				"telefono" => $destinatario['telefono'],
				"note" => $destinatario['note']
			),
			"ritiro" => $ritiro,
			"corriere" => $posts->corriere,
			"pagamento" => $posts->pagamento,
			"tmerce" => "Oggetti vari",
			"api" => "1",
			"pacchi" => $pacchi_array
		);

		// Add cash on delivery if needed
		if ( $tipopagamento == "Pagamento alla consegna" ) {
			if ( empty( $posts->contrassegno_iban ) || empty( $posts->contrassegno_conto ) ) {
				wp_send_json_error( array( 'issue' => 'Iban o conto non specificato per il contrassegno. Vai su Impostazioni e fornisci i dati' ) );
			}
			$parametri["contrassegno"] = array(
				"importo" => $totale,
				"iban" => $posts->contrassegno_iban,
				"conto" => $posts->contrassegno_conto,
				"mod_rimborso" => ""
			);
		}

		// Make API call
		$response = wp_remote_post( 'https://ordini.noispediamo.it/cspedisci-api/spedizione', array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $basicauth
			),
			'body' => wp_json_encode( $parametri )
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'issue' => 'Errore di connessione: ' . $response->get_error_message() ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$risposta = json_decode( $body, true );

		if ( ! isset( $risposta ) ) {
			wp_send_json_error( array( 'issue' => 'Credenziali errate. Controlla su Impostazioni di aver inserito la tua email e password corretta.' ) );
		}

		if ( isset( $risposta['errors'] ) && $risposta['errors'] === 0 ) {
			// Update order status
			$order->update_status( 'shipped' );

			// Add order note
			$idspedisci = isset( $risposta['id'] ) ? sanitize_text_field( $risposta['id'] ) : '';
			$note = sprintf( __( 'Ordine inviato al corriere nr ordine %s', 'cspedisci-connector' ), $idspedisci );
			$order->add_order_note( $note );

			wp_send_json_success( array(
				'id' => $idspedisci,
				'idordine' => $idordine
			) );
		} else {
			wp_send_json_error( array( 'issue' => 'Credenziali errate. Controlla su Impostazioni di aver inserito la tua email e password corretta.' ) );
		}
	}

	/**
	 * AJAX handler for fetching carriers from Noispediamo API
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	void
	 */
	public function ajax_ottieni_corrieri() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cspedisci_ottieni_corrieri_nonce' ) ) {
			wp_send_json_error( array( 'issue' => 'Verifica di sicurezza fallita.' ) );
		}

		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'issue' => 'Permessi insufficienti.' ) );
		}

		global $wpdb;
		$tablecorrieri = $wpdb->prefix . 'cspedisci_corrieri';

		// Sanitize input variables
		$username = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$password = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';

		if ( empty( $username ) || empty( $password ) ) {
			wp_send_json_error( array( 'issue' => 'Email e password sono richiesti.' ) );
		}

		$basicauth = base64_encode( $username . ':' . $password );

		// Make API call
		$response = wp_remote_get( 'https://ordini.noispediamo.it/cspedisci-api/spedizione', array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $basicauth
			)
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'issue' => 'Errore di connessione: ' . $response->get_error_message() ) );
		}

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

			wp_send_json_success( array( 'message' => 'Corrieri aggiornati con successo.' ) );
		} else {
			wp_send_json_error( array( 'issue' => 'Credenziali errate o errore API.' ) );
		}
	}

}
