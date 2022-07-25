<?php

namespace WPaaS\Admin;

/**
 * List Table API: Recommended_Plugins_List_Table class
 *
 * @package WordPress
 * @subpackage Administration
 * @since 3.1.0
 */
/**
 * Core class used to implement displaying plugins to install in a list table.
 *
 * @since 3.1.0
 * @access private
 *
 * @see WP_List_Table
 */
class Recommended_Plugins_List_Table extends \WP_List_Table {

	private $args;

	public $items;

	private $recommended_plugins;

	/**
	 * Class constructor
	 *
	 * @param array $recommended_plugins Recommended plugins data retrieved from Github.
	 */
	public function __construct( $recommended_plugins ) {

		parent::__construct( [ 'ajax' => false ] );

		$this->args = [
			'page'     => $this->get_pagenum(),
			'per_page' => 12,
			'locale'   => get_user_locale(),
		];

		$this->recommended_plugins = $recommended_plugins;

		$this->prepare_items();

	}

	/**
	 * @global string $tab
	 * @global int    $paged
	 * @global string $type
	 * @global string $term
	 */
	public function prepare_items() {

		$plugins = $this->get_recommended_plugins();

		$this->set_pagination_args(
			[
				'total_items' => count( $plugins->plugins ),
				'per_page'    => $this->args['per_page'],
			]
		);

		$plugins->plugins = array_chunk( $plugins->plugins, $this->args['per_page'] );
		$this->items      = $plugins->plugins[ $this->args['page'] - 1 ];

	}

	/**
	 * Query the WordPress.org API for recommended plugin data.
	 *
	 * @return object Recommended plugins data
	 */
	private function get_recommended_plugins() {

		$recommended_plugins = new \stdClass();

		foreach ( $this->recommended_plugins as $plugin_slug ) {

			$plugin_info_cache = get_transient( "wpaas_recommended_plugin_${plugin_slug}" );

			if ( WP_DEBUG || false === $plugin_info_cache ) {

				$plugin_info_cache = plugins_api(
					'plugin_information',
					[
						'slug'   => $plugin_slug,
						'fields' => [
							'compatibility'     => false,
							'contributors'      => false,
							'description'       => false,
							'icons'             => true,
							'last_updated'      => false,
							'requires'          => false,
							'rating'            => false,
							'ratings'           => false,
							'screenshots'       => false,
							'sections'          => false,
							'tags'              => false,
							'short_description' => true,
							'tested'            => false,
							'versions'          => false,
						],
					]
				);

				if ( is_wp_error( $plugin_info_cache ) ) {

					continue;

				}

				set_transient( "wpaas_recommended_plugin_${plugin_slug}", $plugin_info_cache, DAY_IN_SECONDS );

			}

			$recommended_plugins->plugins[] = $plugin_info_cache;

		}

		return $recommended_plugins;

	}


	/**
	 * @return array
	 */
	public function get_columns() {

		return [];

	}

	/**
	 * Displays the plugin install table.
	 *
	 * Overrides the parent display() method to provide a different container.
	 *
	 * @since 4.0.0
	 */
	public function display() {

		$singular  = $this->_args['singular'];
		$data_attr = '';

		if ( $singular ) {

			$data_attr = " data-wp-lists='list:$singular'";

		}

		$this->display_tablenav( 'top' );

		?>

		<div class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">

			<?php $this->screen->render_screen_reader_content( 'heading_list' ); ?>

			<div id="the-list"<?php echo esc_attr( $data_attr ); ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</div>

		</div>

		<?php

		$this->display_tablenav( 'bottom' );

	}

	public function display_rows() {

		$plugins_allowedtags = [
			'a'       => [
				'href'   => [],
				'title'  => [],
				'target' => [],
			],
			'abbr'    => [ 'title' => [] ],
			'acronym' => [ 'title' => [] ],
			'code'    => [],
			'pre'     => [],
			'em'      => [],
			'strong'  => [],
			'ul'      => [],
			'ol'      => [],
			'li'      => [],
			'p'       => [],
			'br'      => [],
		];

		$plugins_group_titles = [
			'Performance' => _x( 'Performance', 'Plugin installer group title' ),
			'Social'      => _x( 'Social', 'Plugin installer group title' ),
			'Tools'       => _x( 'Tools', 'Plugin installer group title' ),
		];

		foreach ( (array) $this->items as $plugin ) {

			if ( is_object( $plugin ) ) {

				$plugin = (array) $plugin;

			}

			$title = wp_kses( $plugin['name'], $plugins_allowedtags );

			// Remove any HTML from the description.
			$description = ! empty( $plugin['short_description'] ) ? strip_tags( $plugin['short_description'] ) : 'empty';
			$name        = strip_tags( $title );
			$author      = wp_kses( ( ! empty( $plugin['author'] ) ? $plugin['author'] : 'empty' ), $plugins_allowedtags );

			if ( ! empty( $author ) ) {

				/* translators: %s: Plugin author. */
				$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '</cite>'; // core i18n

			}

			$action_links = [];

			if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {

				$status = install_plugin_install_status( $plugin );

				switch ( $status['status'] ) {

					case 'install':
						if ( $status['url'] ) {

							$action_links[] = sprintf(
								'<a class="install-now button" data-slug="%s" href="%s" aria-label="%s" data-name="%s">%s</a>',
								esc_attr( $plugin['slug'] ),
								esc_url( $status['url'] ),
								esc_attr(
									sprintf(
										/* translators: %s: Plugin name and version. */
										__( 'Install %s now' ),
										$name
									)
								),
								esc_attr( $name ),
								__( 'Install Now' )
							);

						}
						break;

					case 'update_available':
						if ( $status['url'] ) {
							$action_links[] = sprintf(
								'<a class="update-now button aria-button-if-js" data-plugin="%s" data-slug="%s" href="%s" aria-label="%s" data-name="%s">%s</a>',
								esc_attr( $status['file'] ),
								esc_attr( $plugin['slug'] ),
								esc_url( $status['url'] ),
								esc_attr(
									sprintf(
										/* translators: %s: Plugin name and version. */
										__( 'Update %s now' ),
										$name
									)
								),
								esc_attr( $name ),
								__( 'Update Now' )
							);
						}
						break;

					case 'latest_installed':
					case 'newer_installed':
						if ( is_plugin_active( $status['file'] ) ) {

							$action_links[] = sprintf(
								'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
								_x( 'Active', 'plugin' )
							);

						} elseif ( current_user_can( 'activate_plugin', $status['file'] ) ) {

							$button_text = __( 'Activate' ); // core i18n

							/* translators: %s: Plugin name. */
							$button_label = _x( 'Activate %s', 'plugin' );

							$activate_url = add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
									'action'   => 'activate',
									'plugin'   => $status['file'],
								],
								network_admin_url( 'plugins.php' )
							);

							$action_links[] = sprintf(
								'<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
								esc_url( $activate_url ),
								esc_attr( sprintf( $button_label, $plugin['name'] ) ),
								$button_text
							);

						} else {

							$action_links[] = sprintf(
								'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
								_x( 'Installed', 'plugin' ) // core i18n
							);

						}
						break;

				}
			}

			$details_link = self_admin_url(
				'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] .
				'&amp;TB_iframe=true&amp;width=600&amp;height=550'
			);

			$action_links[] = sprintf(
				'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
				esc_url( $details_link ),
				/* translators: %s: Plugin name and version. */
				esc_attr( sprintf( __( 'More information about %s' ), $name ) ),
				esc_attr( $name ),
				__( 'More Details' )
			);

			if ( ! empty( $plugin['icons'] ) ) {
				if ( ! empty( $plugin['icons']['svg'] ) ) {
					$plugin_icon_url = $plugin['icons']['svg'];
				} elseif ( ! empty( $plugin['icons']['2x'] ) ) {
					$plugin_icon_url = $plugin['icons']['2x'];
				} elseif ( ! empty( $plugin['icons']['1x'] ) ) {
					$plugin_icon_url = $plugin['icons']['1x'];
				} else {
					$plugin_icon_url = $plugin['icons']['default'];
				}
			} else {
				$plugin_icon_url = 'https://via.placeholder.com/256';
			}

			/**
			 * Filters the install action links for a plugin.
			 *
			 * @since 2.7.0
			 *
			 * @param string[] $action_links An array of plugin action links. Defaults are links to Details and Install Now.
			 * @param array    $plugin       The plugin currently being listed.
			 */
			$action_links = apply_filters( 'plugin_install_action_links', $action_links, $plugin );

			?>

			<div class="plugin-card plugin-card-<?php echo sanitize_html_class( $plugin['slug'] ); ?>">

				<div class="plugin-card-top">
					<div class="name column-name">
						<h3>
							<a href="<?php echo esc_url( $plugin['homepage'] ); ?>" class="open-plugin-details-modal">
								<?php echo esc_html( $title ); ?>
								<img src="<?php echo esc_attr( $plugin_icon_url ); ?>" class="plugin-icon" alt="">
							</a>
						</h3>
					</div>

					<div class="action-links">
						<?php
						if ( $action_links ) {
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', array_map( 'wp_kses_post', $action_links ) ) . '</li></ul>';
						}
						?>
					</div>

					<div class="desc column-description">
						<p><?php echo esc_html( $description ); ?></p>
						<p class="authors">
							<?php
							echo wp_kses(
								$author,
								[
									'cite' => [],
									'a'    => [
										'href' => [],
									],
								]
							);
							?>
						</p>
					</div>
				</div>

			</div>

			<?php

		}

	}

}
