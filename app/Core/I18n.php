<?php

/**
 * This file specifies the admin-side plugin functionality.
 *
 * @link         https://gilbertrdz.dev
 * @since        1.0.0
 *
 * @package      WoocartBridge
 * @subpackage   WoocartBridge/app/Core
 *
 * @author       Gilbert Rodríguez <gilbertrdz.dev@gmail.com>
 */

namespace WoocartBridge\App\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class I18n
 * @package WoocartBridge\App\Core
 */
class I18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $domain The domain identifier for this plugin.
	 */
	private string $domain;

	/**
	 * Loads the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function loadPluginTextdomain(): void {

		$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

		load_textdomain( $this->domain, WOOCART_BRIDGE_DIR_PATH . 'languages/woocart-bridge-' . $locale . '.mo' );

		load_plugin_textdomain(
			$this->domain,
			false,
			WOOCART_BRIDGE_DIR_PATH . '/languages/'
		);

	}

	/**
	 * Set the domain to the specified domain.
	 *
	 * @param string $domain The domain that represents the locale for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function setDomain( string $domain ) {
		$this->domain = $domain;
	}

}
