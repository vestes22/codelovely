<?php

namespace WPaaS\Admin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Block_Count {

	/**
	 * Meta key name.
	 *
	 * @var string
	 */
	const META_KEY = 'gd_system_blocks_used';

	/**
	 * Sitewide option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gd_system_blocks_used';

	/**
	 * Sitewide blocks used.
	 * Key: block name, Value: number of times used across the site.
	 *
	 * @var array
	 */
	private $blocks = [];

	/**
	 * Post types to monitor.
	 *
	 * @var array
	 */
	private $post_types = [ 'post', 'page' ];

	/**
	 * Post status to monitor.
	 *
	 * @var array
	 */
	private $post_statuses = [ 'publish' ];

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'save_post', [ $this, 'store_used_post_blocks' ], 10, 2 );

		add_action( 'delete_post', [ $this, 'store_sitewide_used_blocks' ], 10, 2 );

	}

	/**
	 * Store all blocks used on a post as `wpaas_blocks_used` post_meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function store_used_post_blocks( $post_id, $post ) {

		if (
			! in_array( $post->post_type, $this->post_types, true ) ||
			! in_array( $post->post_status, $this->post_statuses, true ) ||
			! has_blocks( $post )
		) {

			delete_post_meta( $post_id, self::META_KEY );

			$this->store_sitewide_used_blocks( $post_id, $post );

			return;

		}

		update_post_meta( $post_id, self::META_KEY, json_encode( $this->get_all_blocks( parse_blocks( $post->post_content ) ) ) );

		$this->store_sitewide_used_blocks( $post_id, $post );

	}

	/**
	 * Return all blocks, including innerBlocks.
	 *
	 * @param array Array of blocks. (Return from parse_blocks)
	 *
	 * @return array Alphabetically sorted array and count of all blocks used in the post content.
	 */
	public function get_all_blocks( $blocks ) {

		foreach ( $blocks as $block ) {

			$this->blocks[] = $block['blockName'];

			if ( ! empty( $block['innerBlocks'] ) ) {

				$this->get_all_blocks( $block['innerBlocks'] );

			}

		}

		ksort( $this->blocks, SORT_STRING );

		return array_count_values( $this->blocks );

	}

	/**
	 * Store all blocks used across the site in a `wpaas_blocks_used` global option.
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function store_sitewide_used_blocks( $post_id, $post ) {

		global $wpdb;

		$post_status_placeholders = implode( ',', array_fill( 0, count( $this->post_statuses ), '%s' ) );
		$post_type_placeholders   = implode( ',', array_fill( 0, count( $this->post_types ), '%s' ) );

		$post_blocks = $wpdb->get_col(
			$wpdb->prepare(
				"
					SELECT pm.meta_value
					FROM {$wpdb->postmeta} AS pm
					JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
					WHERE pm.meta_key = %s
					AND p.post_status IN ( {$post_status_placeholders} )
					AND p.post_type IN ( {$post_type_placeholders} )
				",
				array_merge( [ self::META_KEY ], $this->post_statuses, $this->post_types )
			)
		);

		if ( empty( $post_blocks ) ) {

			delete_option( self::OPTION_NAME );

			return;

		}

		$combined = [];

		foreach ( array_map( 'json_decode', $post_blocks ) as $blocks ) {

			foreach ( $blocks as $name => $count ) {

				$combined[ $name ] = ( isset( $combined[ $name ] ) ? $combined[ $name ] : 0 ) + $count;

			}

		}

		ksort( $combined, SORT_STRING );

		update_option( self::OPTION_NAME, json_encode( $combined ), false );

	}

}
