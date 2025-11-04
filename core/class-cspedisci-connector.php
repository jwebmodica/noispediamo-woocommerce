<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Cspedisci_Connector' ) ) :

	/**
	 * Main Cspedisci_Connector Class.
	 *
	 * @package		Jwebship
	 * @subpackage	Classes/Cspedisci_Connector
	 * @since		1.0.0
	 * @author		Jweb
	 */
	final class Cspedisci_Connector {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Cspedisci_Connector
		 */
		private static $instance;

		/**
		 * Cspedisci helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Cspedisci_Connector_Helpers
		 */
		public $helpers;

		/**
		 * Cspedisci settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Cspedisci_Connector_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'cspedisci-connector' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'cspedisci-connector' ), '1.0.0' );
		}

		/**
		 * Main Cspedisci_Connector Instance.
		 *
		 * Insures that only one instance of Cspedisci_Connector exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Cspedisci_Connector	The one true Cspedisci_Connector
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Cspedisci_Connector ) ) {
				self::$instance					= new Cspedisci_Connector;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Cspedisci_Connector_Helpers();
				self::$instance->settings		= new Cspedisci_Connector_Settings();

				//Fire the plugin logic
				new Cspedisci_Connector_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'Cspedisci/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once CSPEDISCI_PLUGIN_DIR . 'core/includes/classes/class-cspedisci-connector-helpers.php';
			require_once CSPEDISCI_PLUGIN_DIR . 'core/includes/classes/class-cspedisci-connector-settings.php';

			require_once CSPEDISCI_PLUGIN_DIR . 'core/includes/classes/class-cspedisci-connector-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'cspedisci-connector', FALSE, dirname( plugin_basename( CSPEDISCI_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.