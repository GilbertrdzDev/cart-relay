<?php

/**
 * This file specifies reusable WooCommerce product resolution helpers.
 *
 * @link         https://gilbertrdz.dev
 * @since        1.0.0
 *
 * @package      WoocartBridge
 * @subpackage   WoocartBridge/app/Helpers
 *
 * @author       Gilbert Rodríguez <gilbertrdz.dev@gmail.com>
 */

namespace WoocartBridge\App\Helpers;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductResolver
 * @package WoocartBridge\App\Helpers
 */
class ProductResolver {

	/**
	 * Finds a WooCommerce product by SKU.
	 *
	 * @param string $sku
	 *
	 * @return WC_Product|null
	 */
	public static function find_by_sku( string $sku ): ?WC_Product {
		$sku = trim( $sku );

		if ( $sku === '' || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return null;
		}

		$product_id = wc_get_product_id_by_sku( $sku );

		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * Validates whether a product can be imported into the cart.
	 *
	 * @param WC_Product $product
	 * @param int        $quantity
	 *
	 * @return array
	 */
	public static function validate( WC_Product $product, int $quantity ): array {
		$errors = [];

		if ( $product->get_id() <= 0 ) {
			$errors[] = 'Product does not exist.';
		}

		if ( $product->get_status() !== 'publish' ) {
			$errors[] = 'Product is not published.';
		}

		if ( ! $product->is_purchasable() ) {
			$errors[] = 'Product is not purchasable.';
		}

		if ( $quantity <= 0 ) {
			$errors[] = 'Quantity must be greater than zero.';
		}

		if ( ! $product->is_in_stock() ) {
			$errors[] = 'Product is out of stock.';
		} elseif ( $quantity > 0 && ! $product->has_enough_stock( $quantity ) ) {
			$errors[] = 'Product does not have enough stock.';
		}

		if ( ! $product->is_type( 'simple' ) ) {
			$errors[] = 'Only simple products are supported.';
		}

		return $errors;
	}

}
