<?php
/**
 * NextGen Publish Guide
 *
 * @since 1.0.0
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Publish_Guide
 *
 * @package NextGen
 * @author  GoDaddy
 */
class Publish_Guide {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'register_settings' ], 11 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_scripts' ] );

		add_filter( 'pre_set_theme_mod_custom_logo', [ $this, 'sync_site_logo_to_theme_mod' ] );
		add_filter( 'theme_mod_custom_logo', [ $this, 'override_custom_logo_theme_mod' ] );
		add_action( 'rest_api_init', [ $this, 'register_site_logo_setting' ], 10 );

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
		$asset_filepath = GD_NEXTGEN_PLUGIN_DIR . '/build/publishGuide.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : $default_asset_file;

		wp_enqueue_script(
			'nextgen-publish-guide',
			GD_NEXTGEN_PLUGIN_URL . 'build/publishGuide.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true // Enqueue script in the footer.
		);

		wp_set_script_translations( 'nextgen-publish-guide', 'nextgen', GD_NEXTGEN_PLUGIN_DIR . '/languages' );

		wp_enqueue_style(
			'nextgen-publish-guide',
			GD_NEXTGEN_PLUGIN_URL . 'build/style-publishGuide.css',
			[],
			$asset_file['version']
		);

		wp_localize_script(
			'nextgen-publish-guide',
			'nextgenPublishGuideDefaults',
			[
				'userId' => get_current_user_id(),
			]
		);

		wp_localize_script(
			'nextgen-publish-guide',
			'nextgenLinks',
			(array) apply_filters(
				'nextgen_admin_links',
				[
					'admin' => get_admin_url(),
				]
			)
		);

	}

	/**
	 * Overrides the custom logo with a site logo, if the option is set.
	 *
	 * @param string $custom_logo The custom logo set by a theme.
	 *
	 * @return string The site logo if set.
	 */
	public function override_custom_logo_theme_mod( $custom_logo ) {
		$sitelogo = get_option( 'sitelogo' );
		return false === $sitelogo ? $custom_logo : $sitelogo;
	}

	/**
	 * Syncs the site logo with the theme modified logo.
	 *
	 * @param string $custom_logo The custom logo set by a theme.
	 *
	 * @return string The custom logo.
	 */
	public function sync_site_logo_to_theme_mod( $custom_logo ) {
		if ( $custom_logo ) {
			update_option( 'sitelogo', $custom_logo );
		}
		return $custom_logo;
	}

	/**
	 * Register a core site setting for a site logo
	 */
	public function register_site_logo_setting() {
		register_setting(
			'general',
			'sitelogo',
			[
				'show_in_rest' => [
					'name' => 'sitelogo',
				],
				'type'         => 'string',
				'description'  => __( 'Site logo.', 'gd-nextgen' ),
			]
		);
	}

	/**
	 * Register core site settings for Publish Guide
	 */
	public function register_settings() {

		register_setting(
			'nextgen_publish_guide_active',
			'nextgen_publish_guide_active',
			[
				'show_in_rest'      => true,
				'default'           => true,
				'type'              => 'boolean',
				'description'       => __( 'Display Publish Guide.', 'nextgen' ),
				'sanitize_callback' => null,
			]
		);

	}
}
