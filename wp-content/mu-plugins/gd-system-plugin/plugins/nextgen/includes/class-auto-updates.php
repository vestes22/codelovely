<?php
/**
 * NextGen auto update overrides
 *
 * @package NextGen
 */

namespace GoDaddy\WordPress\Plugins\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Auto Updates Class.
 */
class Auto_Updates {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_filter( 'auto_update_plugin', [ $this, 'coblocks' ], 10, 2 );

		add_filter( 'auto_update_theme', [ $this, 'go' ], 10, 2 );

		add_filter( 'auto_update_translation', [ $this, 'translations' ], 10, 2 );

	}

	/**
	 * Auto update CoBlocks.
	 *
	 * @param  boolean $update Whether or not the plugin should be auto updated.
	 * @param  object  $item   Plugin object.
	 *
	 * @return boolean True when the plugin should auto update, else false.
	 */
	public function coblocks( $update, $item ) {

		return 'coblocks' === $item->slug ? true : $update;

	}

	/**
	 * Auto update Go theme.
	 *
	 * @param  boolean $update Whether or not the theme should be auto updated.
	 * @param  object  $item   Theme object.
	 *
	 * @return boolean True when the plugin should auto update, else false.
	 */
	public function go( $update, $item ) {

		return 'go' === $item->theme ? true : $update;

	}

	/**
	 * Auto update translations.
	 *
	 * @param  boolean $update Whether or not the translation should be auto updated.
	 * @param  object  $item   Theme object.
	 *
	 * @return boolean True when the plugin or theme translation should auto update, else false.
	 */
	public function translations( $update, $item ) {

		if ( 'plugin' === $item->type && 'coblocks' === $item->plugin ) {

			return true;

		}

		if ( 'theme' === $item->type && 'go' === $item->theme ) {

			return true;

		}

		return $update;

	}

}
