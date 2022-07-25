<?php
/**
 * NextGen Site Content
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Site_Content
 *
 * @package NextGen
 * @author  GoDaddy
 */
class Site_Content {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );
		add_action( 'init', [ $this, 'register_settings' ], 11 );

		add_filter( 'wp_insert_post_empty_content', [ $this, 'allow_empty_post_content' ], 10, 2 );

	}

	/**
	 * Enqueue the scripts and styles.
	 */
	public function enqueue_scripts() {

		$default_asset_file = [
			'dependencies' => [],
			'version'      => GD_NEXTGEN_VERSION,
		];

		// Editor Script.
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/siteContent.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-site-content',
			GD_NEXTGEN_PLUGIN_URL . 'build/siteContent.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-site-content', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		wp_enqueue_style(
			'nextgen-site-content',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-siteContent.css',
			[],
			$asset_file['version']
		);

	}

	/**
	 * Filters whether the post should be considered "empty".
	 *
	 * @see wp_insert_post()
	 *
	 * @param bool  $maybe_empty Whether the post should be considered "empty".
	 * @param array $postarr     Array of post data.
	 */
	public function allow_empty_post_content( $maybe_empty, $postarr ) {
		$post_type = empty( $postarr['post_type'] ) ? 'post' : $postarr['post_type'];

		return in_array( $post_type, [ 'post', 'page' ], true )
			? false // Allow empty title, content, and excerpt.
			: $maybe_empty;
	}

	/**
	 * Retreive the available post types
	 */
	private function get_page_nav_post_types() {

		$post_types = get_post_types();
		$white_list = [
			'page' => 'pages',
			// 'post'    => 'posts', // @codingStandardsIgnoreLine
			// 'product' => 'products', // @codingStandardsIgnoreLine
		];

		foreach ( $post_types as $post_type_slug ) {

			if ( ! array_key_exists( $post_type_slug, $white_list ) ) {
				unset( $post_types[ $post_type_slug ] );
				continue;
			}

			$post_type_obj = get_post_type_object( $post_type_slug );

			$post_types[ $white_list[ $post_type_slug ] ] = $post_type_obj->label;
			unset( $post_types[ $post_type_slug ] );
		}

		ksort( $post_types );

		return $post_types;

	}

	/**
	 * Register core site settings for Site Content
	 */
	public function register_settings() {
		register_setting(
			'reading',
			'show_on_front',
			array(
				'show_in_rest' => true,
				'type'         => 'string',
				'description'  => __( 'What to show on the front page', 'gutenberg' ),
			)
		);

		register_setting(
			'reading',
			'page_on_front',
			array(
				'show_in_rest' => true,
				'type'         => 'number',
				'description'  => __( 'The ID of the page that should be displayed on the front page', 'gutenberg' ),
			)
		);

		register_setting(
			'reading',
			'page_for_posts',
			array(
				'show_in_rest' => true,
				'type'         => 'number',
				'description'  => __( 'The ID of the page that display the posts', 'gutenberg' ),
			)
		);
	}
}
