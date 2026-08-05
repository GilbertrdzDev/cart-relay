<?php

/**
 * This file specifies reusable WooCommerce product resolution helpers.
 *
 * @link         https://gilbertrdz.dev
 * @since        1.0.0
 *
 * @package      CartRelay
 * @subpackage   CartRelay/app/Helpers
 *
 * @author       Gilbert Rodríguez <gilbertrdz.dev@gmail.com>
 */

namespace CartRelay\App\Helpers;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductResolver
 * @package CartRelay\App\Helpers
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
	 * Finds a WooCommerce product by ID.
	 *
	 * @param int $product_id
	 *
	 * @return WC_Product|null
	 */
	public static function find_by_id( int $product_id ): ?WC_Product {
		if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * Resolves an import row into cart-ready product data.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public static function resolve_import_row( array $row ): array {
		$product_id   = absint( $row['product_id'] ?? 0 );
		$variation_id = absint( $row['variation_id'] ?? 0 );
		$quantity     = absint( $row['quantity'] ?? 0 );
		$sku          = trim( (string) ( $row['sku'] ?? '' ) );
		$errors       = [];
		$product      = null;

		if ( $variation_id > 0 ) {
			$product = self::find_by_id( $variation_id );

			if ( ! $product ) {
				$errors[] = sprintf(
					/* translators: %d: WooCommerce variation ID. */
					__( 'Variation not found for ID %d.', 'cart-relay' ),
					$variation_id
				);
			} elseif ( ! $product->is_type( 'variation' ) ) {
				$errors[] = sprintf(
					/* translators: %d: WooCommerce product ID. */
					__( 'ID %d does not match a variation.', 'cart-relay' ),
					$variation_id
				);
			} else {
				$parent_id = (int) $product->get_parent_id();

				if ( $product_id > 0 && $parent_id !== $product_id ) {
					$errors[] = sprintf(
						/* translators: 1: WooCommerce variation ID, 2: WooCommerce product ID. */
						__( 'Variation %1$d does not belong to product %2$d.', 'cart-relay' ),
						$variation_id,
						$product_id
					);
				}

				$product_id = $parent_id;
			}
		} elseif ( $product_id > 0 ) {
			$product = self::find_by_id( $product_id );

			if ( ! $product ) {
				$errors[] = sprintf(
					/* translators: %d: WooCommerce product ID. */
					__( 'Product not found for ID %d.', 'cart-relay' ),
					$product_id
				);
			} elseif ( $product->is_type( 'variation' ) ) {
				$variation_id = $product->get_id();
				$product_id   = (int) $product->get_parent_id();
			}
		} elseif ( $sku !== '' ) {
			$product = self::find_by_sku( $sku );

			if ( ! $product ) {
				$errors[] = sprintf(
					/* translators: %s: WooCommerce product SKU. */
					__( 'Product not found for SKU %s.', 'cart-relay' ),
					$sku
				);
			} elseif ( $product->is_type( 'variation' ) ) {
				$variation_id = $product->get_id();
				$product_id   = (int) $product->get_parent_id();
			} else {
				$product_id = $product->get_id();
			}
		} else {
			$errors[] = __( 'The row must include product_id, variation_id, or sku.', 'cart-relay' );
		}

		if ( $product && $variation_id === 0 && $product->is_type( 'variable' ) ) {
			$errors[] = __( 'Variable products require variation_id.', 'cart-relay' );
		}

		if ( $product ) {
			$errors = array_merge( $errors, self::validate( $product, $quantity ) );
		} elseif ( $quantity <= 0 ) {
			$errors[] = __( 'Quantity must be greater than zero.', 'cart-relay' );
		}

		return [
			'product'      => $product,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'sku'          => $sku,
			'quantity'     => $quantity,
			'errors'       => array_values( array_unique( $errors ) ),
		];
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
			$errors[] = __( 'The product does not exist.', 'cart-relay' );
		}

		if ( $product->get_status() !== 'publish' ) {
			$errors[] = __( 'The product is not published.', 'cart-relay' );
		}

		if ( ! $product->is_purchasable() ) {
			$errors[] = __( 'The product cannot be purchased.', 'cart-relay' );
		}

		if ( $quantity <= 0 ) {
			$errors[] = __( 'Quantity must be greater than zero.', 'cart-relay' );
		}

		if ( ! $product->is_in_stock() ) {
			$errors[] = __( 'The product is out of stock.', 'cart-relay' );
		} elseif ( $quantity > 0 && ! $product->has_enough_stock( $quantity ) ) {
			$errors[] = __( 'The product does not have enough stock.', 'cart-relay' );
		}

		if ( ! $product->is_type( [ 'simple', 'variation' ] ) ) {
			$errors[] = __( 'Only simple products or specific variations are supported.', 'cart-relay' );
		}

		return $errors;
	}

}
