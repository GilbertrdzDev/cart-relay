<?php

/**
 * This file specifies the admin-side plugin functionality.
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

defined( 'ABSPATH' ) || exit;

/**
 * Class PageTemplater
 * @package WoocartBridge\App\Helpers
 */
class PageTemplater {

	/**
	 * A reference to an instance of this class.
	 */
	private static PageTemplater $instance;

	/**
	 * The array of templates that this plugin tracks.
	 */
	protected array $templates;

	public function __construct() {
		$this->templates = [];
	}

	/**
	 * Returns an instance of this class.
	 */
	public static function getInstance(): PageTemplater {

		if ( null == self::$instance ) {
			self::$instance = new PageTemplater();
		}

		return self::$instance;

	}

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	public function run(): void {

		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {

			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				[
					$this,
					'register_project_templates',
				]
			);

		} else {

			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', [
					$this,
					'add_new_template',
				]
			);

		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			[
				$this,
				'register_project_templates',
			]
		);

		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			[
				$this,
				'view_project_template',
			]
		);

		// Add your templates to this array.
//		$this->templates = [
//			'templates/template-blank.php' => 'Blank template',
//		];

	}

	/**
	 * @param array $templates
	 *
	 * @return $this
	 */
	public function addTemplates( array $templates ): PageTemplater {

		$valid_templates = [];

		foreach ( $templates as $template => $label ) {
			$template = (string) $template;

			if ( $this->is_valid_template_key( $template ) ) {
				$valid_templates[ $template ] = $label;
			}
		}

		$this->templates = array_merge( $valid_templates, $this->templates );

		return $this;

	}

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 * @param array $posts_templates
	 *
	 * @return array
	 */
	public function add_new_template( array $posts_templates ): array {

		$posts_templates = array_merge( $posts_templates, $this->templates );

		return $posts_templates;

	}

	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 *
	 * @param array $atts
	 *
	 * @return array
	 */
	public function register_project_templates( array $atts ): array {

		// Create the key used for the themes cache
		$cache_key = 'woocart-bridge_page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = [];
		}

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key, 'themes' );

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;

	}

	/**
	 * Checks if the template is assigned to the page
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function view_project_template( string $template ): string {

		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		$customTemplate = get_post_meta(
			$post->ID, '_wp_page_template', true
		);

		if ( ! is_string( $customTemplate ) || ! isset( $this->templates[ $customTemplate ] ) ) {
			return $template;
		}

		$file = $this->resolve_template_path( $customTemplate );

		if ( $file !== null ) {
			return $file;
		}

		// Return template
		return $template;

	}

	private function is_valid_template_key( string $template ): bool {
		$template = str_replace( '\\', '/', $template );

		return $template !== ''
			&& ! str_contains( $template, "\0" )
			&& ! str_starts_with( $template, '/' )
			&& ! preg_match( '#^[A-Za-z]:/#', $template )
			&& ! preg_match( '#(^|/)\.\.(/|$)#', $template )
			&& str_starts_with( $template, 'templates/' )
			&& str_ends_with( $template, '.php' );
	}

	private function resolve_template_path( string $template ): ?string {
		if ( ! $this->is_valid_template_key( $template ) ) {
			return null;
		}

		$base_path = realpath( WOOCART_BRIDGE_DIR_PATH . 'templates' );
		$file      = realpath( WOOCART_BRIDGE_DIR_PATH . str_replace( '\\', '/', $template ) );

		if (
			$base_path === false
			|| $file === false
			|| ! is_file( $file )
			|| ! str_starts_with( $file, $base_path . DIRECTORY_SEPARATOR )
		) {
			return null;
		}

		return $file;
	}

}
