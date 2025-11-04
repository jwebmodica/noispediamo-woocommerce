<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Cspedisci_Connector_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		CSPEDISCI
 * @subpackage	Classes/Cspedisci_Connector_Settings
 * @author		Jweb
 * @since		1.0.0
 */
class Cspedisci_Connector_Settings{

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $plugin_name;

	/**
	 * Our Cspedisci_Connector_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){

		$this->plugin_name = CSPEDISCI_NAME;
	}

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'CSPEDISCI/settings/get_plugin_name', $this->plugin_name );
	}
}
