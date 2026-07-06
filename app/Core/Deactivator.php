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
 * Class Deactivator
 * @package WoocartBridge\App\Core
 */
class Deactivator {

	/**
	 * Static method.
	 *
	 * Method that runs when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 * @access front static
	 */
	public static function deactivate(): void {

		flush_rewrite_rules();

	}

}
