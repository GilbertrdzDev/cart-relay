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
				$errors[] = sprintf( 'Variación no encontrada para el ID %d.', $variation_id );
			} elseif ( ! $product->is_type( 'variation' ) ) {
				$errors[] = sprintf( 'El ID %d no corresponde a una variación.', $variation_id );
			} else {
				$parent_id = (int) $product->get_parent_id();

				if ( $product_id > 0 && $parent_id !== $product_id ) {
					$errors[] = sprintf( 'La variación %d no pertenece al producto %d.', $variation_id, $product_id );
				}

				$product_id = $parent_id;
			}
		} elseif ( $product_id > 0 ) {
			$product = self::find_by_id( $product_id );

			if ( ! $product ) {
				$errors[] = sprintf( 'Producto no encontrado para el ID %d.', $product_id );
			} elseif ( $product->is_type( 'variation' ) ) {
				$variation_id = $product->get_id();
				$product_id   = (int) $product->get_parent_id();
			}
		} elseif ( $sku !== '' ) {
			$product = self::find_by_sku( $sku );

			if ( ! $product ) {
				$errors[] = sprintf( 'Producto no encontrado para el SKU %s.', $sku );
			} elseif ( $product->is_type( 'variation' ) ) {
				$variation_id = $product->get_id();
				$product_id   = (int) $product->get_parent_id();
			} else {
				$product_id = $product->get_id();
			}
		} else {
			$errors[] = 'La fila debe incluir product_id, variation_id o sku.';
		}

		if ( $product && $variation_id === 0 && $product->is_type( 'variable' ) ) {
			$errors[] = 'El producto variable requiere variation_id.';
		}

		if ( $product ) {
			$errors = array_merge( $errors, self::validate( $product, $quantity ) );
		} elseif ( $quantity <= 0 ) {
			$errors[] = 'La cantidad debe ser mayor que cero.';
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
			$errors[] = 'El producto no existe.';
		}

		if ( $product->get_status() !== 'publish' ) {
			$errors[] = 'El producto no está publicado.';
		}

		if ( ! $product->is_purchasable() ) {
			$errors[] = 'El producto no se puede comprar.';
		}

		if ( $quantity <= 0 ) {
			$errors[] = 'La cantidad debe ser mayor que cero.';
		}

		if ( ! $product->is_in_stock() ) {
			$errors[] = 'El producto no tiene stock.';
		} elseif ( $quantity > 0 && ! $product->has_enough_stock( $quantity ) ) {
			$errors[] = 'El producto no tiene stock suficiente.';
		}

		if ( ! $product->is_type( [ 'simple', 'variation' ] ) ) {
			$errors[] = 'Solo se soportan productos simples o variaciones específicas.';
		}

		return $errors;
	}

}
