<?php

namespace WPaaS\StockPhotos;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Ajax {

	const IMAGE_API_URL = 'https://isteam.wsimg.com/stock';

	private $api = null;

	/**
	 * Ajax constructor.
	 *
	 * @param API $api
	 */
	public function __construct( API $api ) {

		$this->api = $api;

		add_action( 'wp_ajax_wpaas_stock_photos_get',      [ $this, 'get' ] );
		add_action( 'wp_ajax_wpaas_stock_photos_download', [ $this, 'download' ] );

	}

	public function get() {

		if ( ! current_user_can( 'upload_files' ) ) {

			wp_send_json_error();

		}

		// phpcs:disable WordPress.Security.NonceVerification -- A nonce is not required here.
		$category = isset( $_POST['query']['category'] ) ? esc_attr( $_POST['query']['category'] ) : false;
		$page     = isset( $_POST['query']['paged'] ) ? absint( $_POST['query']['paged'] ) : 1;
		$per_page = isset( $_POST['query']['posts_per_page'] ) ? absint( $_POST['query']['posts_per_page'] ) : 40;
		// phpcs:enabled WordPress.Security.NonceVerification

		if ( ! $category ) {

			wp_send_json_error();

		}

		$images = $this->api->get_images_by_cat( $category );

		if ( empty( $images ) ) {

			// We still want success here for the jQuery
			// deffered object to callback correctly
			wp_send_json_success( [] );

		}

		$total       = count( $images );
		$total_pages = ceil( $total / $per_page );
		$page        = max( $page, 1 );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;

		$images = array_splice( $images, $offset, $per_page );
		$images = array_map( [ $this, 'prepare_attachement_for_js' ], $images );
		$images = array_filter( $images );

		return  wp_send_json_success( $images );

	}

	/**
	 * Download an image given an url
	 */
	public function download() {

		if ( ! isset( $_POST['filename'], $_POST['id'], $_POST['nonce'] ) ) {

			wp_send_json_error();

		}

		$filename = sanitize_file_name( $_POST['filename'] );
		$id       = sanitize_text_field( $_POST['id'] );

		check_ajax_referer( 'wpaas_stock_photo_download_' . $id, 'nonce' );

		/**
		 * Resize to max 2400 px wide 80% quality
		 * Documentation: https://github.com/asilvas/node-image-steam
		 */
		$url = esc_url_raw( sprintf( '%s/%s/:/rs=w:2400/qt=q:80', untrailingslashit( self::IMAGE_API_URL ), $filename ) );

		$import   = new Import();
		$image_id = $import->image( $url );

		if ( ! $image_id ) {

			wp_send_json_error();

		}

		$attachment = wp_prepare_attachment_for_js( $image_id );

		if ( ! $attachment ) {

			wp_send_json_error();

		}

		wp_send_json_success( $attachment );

	}

	/**
	 * Format attachement for bacbone use
	 *
	 * @param array $attachment
	 *
	 * @return mixed
	 */
	private function prepare_attachement_for_js( $attachment ) {

		if ( empty( $attachment->url ) ) {

			return false;

		}

		foreach ( [ 'preview', 'large' ] as $size ) {

			$attachment->$size = sprintf( '%s/photos/sizes/%s/%s', API::D3_ENDPOINT, $size, $attachment->id );

		}

		return [
			'id'          => $attachment->id,
			'title'       => wp_basename( $attachment->url ),
			'filename'    => wp_basename( $attachment->url ),
			'url'         => '',
			'link'        => '',
			'alt'         => '',
			'author'      => '',
			'description' => '',
			'caption'     => '',
			'name'        => '',
			'status'      => '',
			'uploadedTo'  => '',
			'date'        => '',
			'modified'    => '',
			'menuOrder'   => 0,
			'mime'        => '',
			'type'        => 'image',
			'subtype'     => '',
			'icon'        => '',
			'dateFormatted' => '',
			'nonces'      => [
				'download' => wp_create_nonce( 'wpaas_stock_photo_download_' . $attachment->id ),
			],
			'editLink'   => '',
			'meta'       => '',
			'authorName' => '',
			'sizes'      => [
				'thumbnail' => [
					'width'       => '',
					'height'      => '',
					'url'         => $attachment->preview,
				],
				'preview' => [
					'width'       => '',
					'height'      => '',
					'url'         => $attachment->large,
				],
			],
		];

	}

}
