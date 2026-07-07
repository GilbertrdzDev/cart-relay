<?php

namespace WoocartBridge\App\Core;

use InvalidArgumentException;
use Kucrut\Vite;


defined( 'ABSPATH' ) || exit;

class AssetManager {

	private array $frontend_styles = [];
	private array $frontend_scripts = [];
	private array $frontend_modules = [];
	private array $frontend_vite_assets = [];
	private array $admin_styles = [];
	private array $admin_scripts = [];
	private array $admin_modules = [];
	private array $admin_vite_assets = [];

	public function __construct(
		private Loader $loader,
		private string $base_path,
		private string $base_url,
		private string $dist_path = 'dist'
	) {}

	public function style(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null,
		string $media = 'all'
	): void {
		$this->frontend_styles[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
			'media'        => $media,
		];
	}

	public function script(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null,
		bool|array $args = true
	): void {
		$this->frontend_scripts[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
			'args'         => $args,
		];
	}

	public function module(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null
	): void {
		$this->frontend_modules[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
		];
	}

	public function admin_style(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null,
		string $media = 'all',
		string|array|null $screens = null
	): void {
		$this->admin_styles[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
			'media'        => $media,
			'screens'      => $screens,
		];
	}

	public function admin_script(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null,
		bool|array $args = true,
		string|array|null $screens = null
	): void {
		$this->admin_scripts[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
			'args'         => $args,
			'screens'      => $screens,
		];
	}

	public function admin_module(
		string $handle,
		string $path,
		array $dependencies = [],
		string|bool|null $version = null,
		string|array|null $screens = null
	): void {
		$this->admin_modules[] = [
			'handle'       => $this->normalize_handle( $handle ),
			'path'         => $this->normalize_asset_path( $path ),
			'dependencies' => $dependencies,
			'version'      => $version,
			'screens'      => $screens,
		];
	}

	public function vite(
		string $context,
		string $handle,
		string $entry,
		array $options = [],
		array|null $localize = null
	): void {
		$asset = [
			'handle'   => $this->normalize_handle( $handle ),
			'entry'    => $this->normalize_relative_path( $entry ),
			'options'  => $options,
			'localize' => $localize,
		];

		if ( $context === 'admin' ) {
			$this->admin_vite_assets[] = $asset;
			return;
		}

		$this->frontend_vite_assets[] = $asset;
	}

	public function frontend_vite(
		string $handle,
		string $entry,
		array $options = [],
		array|null $localize = null
	): void {
		$this->vite( 'frontend', $handle, $entry, $options, $localize );
	}

	public function admin_vite(
		string $handle,
		string $entry,
		array $options = [],
		array|null $localize = null
	): void {
		$this->vite( 'admin', $handle, $entry, $options, $localize );
	}

	public function init(): void {
		$this->loader->add_action( 'wp_enqueue_scripts', [ $this, 'dispatch_frontend_assets' ] );
		$this->loader->add_action( 'admin_enqueue_scripts', [ $this, 'dispatch_admin_assets' ] );
	}

	public function dispatch_frontend_assets(): void {
		$this->dispatch_styles( $this->frontend_styles );
		$this->dispatch_scripts( $this->frontend_scripts );
		$this->dispatch_modules( $this->frontend_modules );
		$this->dispatch_vite_assets( $this->frontend_vite_assets );
	}

	public function dispatch_admin_assets(): void {
		$this->dispatch_styles( $this->admin_styles, true );
		$this->dispatch_scripts( $this->admin_scripts, true );
		$this->dispatch_modules( $this->admin_modules, true );
		$this->dispatch_vite_assets( $this->admin_vite_assets, true );
	}

	private function dispatch_styles( array $styles, bool $admin = false ): void {
		foreach ( $styles as $style ) {
			if ( $admin && ! $this->should_enqueue_for_screen( $style['screens'] ?? null ) ) {
				continue;
			}

			wp_enqueue_style(
				$style['handle'],
				$this->build_asset_url( $style['path'] ),
				$style['dependencies'],
				$this->resolve_version( $style['version'], $style['path'] ),
				$style['media']
			);
		}
	}

	private function dispatch_scripts( array $scripts, bool $admin = false ): void {
		foreach ( $scripts as $script ) {
			if ( $admin && ! $this->should_enqueue_for_screen( $script['screens'] ?? null ) ) {
				continue;
			}

			wp_enqueue_script(
				$script['handle'],
				$this->build_asset_url( $script['path'] ),
				$script['dependencies'],
				$this->resolve_version( $script['version'], $script['path'] ),
				$script['args']
			);
		}
	}

	private function dispatch_modules( array $modules, bool $admin = false ): void {
		foreach ( $modules as $module ) {
			if ( $admin && ! $this->should_enqueue_for_screen( $module['screens'] ?? null ) ) {
				continue;
			}

			if ( function_exists( 'wp_enqueue_script_module' ) ) {
				wp_enqueue_script_module(
					$module['handle'],
					$this->build_asset_url( $module['path'] ),
					$module['dependencies'],
					$this->resolve_version( $module['version'], $module['path'] )
				);
			}
		}
	}

	private function dispatch_vite_assets( array $assets, bool $admin = false ): void {
		$dispatched = [];

		foreach ( $assets as $asset ) {
			if ( $admin && ! $this->should_enqueue_for_screen( $asset['options']['screens'] ?? null ) ) {
				continue;
			}

			if ( ! $this->should_enqueue_for_condition( $asset['options']['condition'] ?? null ) ) {
				continue;
			}

			$asset_key = $asset['handle'] . '|' . $asset['entry'];

			if ( isset( $dispatched[ $asset_key ] ) ) {
				continue;
			}

			$dispatched[ $asset_key ] = true;
			$text_domain = (string) ( $asset['options']['text-domain'] ?? '' );

			$options = array_merge(
				[
					'handle'    => $asset['handle'],
					'in-footer' => true,
				],
				$asset['options']
			);
			unset( $options['screens'], $options['condition'], $options['text-domain'] );

			Vite\enqueue_asset(
				$this->trailingslash( $this->base_path ) . trim( $this->dist_path, '/\\' ),
				$asset['entry'],
				$options
			);

			if ( $asset['localize'] ) {
				wp_localize_script(
					$asset['handle'],
					$asset['localize']['object_name'],
					$asset['localize']['data']
				);
			}

			if ( $text_domain !== '' && function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations(
					$asset['handle'],
					$text_domain,
					$this->trailingslash( $this->base_path ) . 'languages'
				);
			}
		}
	}

	private function build_asset_url( string $path ): string {
		return $this->is_relative_path( $path )
			? esc_url_raw( rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' ) )
			: esc_url_raw( $path );
	}

	private function resolve_version( string|bool|null $version, string $path ): string|false|null {
		if ( $version !== true ) {
			return $version;
		}

		if ( ! $this->is_relative_path( $path ) ) {
			return null;
		}

		$full_path = $this->trailingslash( $this->base_path ) . ltrim( $path, '/\\' );

		return file_exists( $full_path ) ? (string) filemtime( $full_path ) : null;
	}

	private function is_relative_path( string $path ): bool {
		return ! preg_match( '#^(https?:)?//#i', $path );
	}

	private function normalize_handle( string $handle ): string {
		$normalized = sanitize_key( $handle );

		if ( $normalized === '' ) {
			throw new InvalidArgumentException( 'Asset handle cannot be empty.' );
		}

		return $normalized;
	}

	private function normalize_asset_path( string $path ): string {
		$path = trim( $path );

		if ( $path === '' ) {
			throw new InvalidArgumentException( 'Asset path cannot be empty.' );
		}

		if ( ! $this->is_relative_path( $path ) ) {
			$url = esc_url_raw( $path );

			if ( $url === '' ) {
				throw new InvalidArgumentException( "Invalid asset URL {$path}." );
			}

			return $url;
		}

		return $this->normalize_relative_path( $path );
	}

	private function normalize_relative_path( string $path ): string {
		$normalized = str_replace( '\\', '/', trim( $path ) );

		if (
			$normalized === ''
			|| str_contains( $normalized, "\0" )
			|| str_starts_with( $normalized, '/' )
			|| preg_match( '#^[A-Za-z]:/#', $normalized )
			|| preg_match( '#(^|/)\.\.(/|$)#', $normalized )
		) {
			throw new InvalidArgumentException( "Invalid relative asset path {$path}." );
		}

		return $normalized;
	}

	private function should_enqueue_for_screen( string|array|null $screens ): bool {
		if ( ! is_admin() || empty( $screens ) ) {
			return true;
		}

		$screen      = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id   = (string) $screen?->id;
		$screens_list = is_array( $screens ) ? $screens : [ $screens ];

		return in_array( $screen_id, $screens_list, true );
	}

	private function should_enqueue_for_condition( callable|bool|null $condition ): bool {
		if ( $condition === null ) {
			return true;
		}

		if ( is_bool( $condition ) ) {
			return $condition;
		}

		return (bool) call_user_func( $condition );
	}

	private function trailingslash( string $path ): string {
		return rtrim( $path, '/\\' ) . DIRECTORY_SEPARATOR;
	}

}
