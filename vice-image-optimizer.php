<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Vice Image Optimizer
 * Plugin URI:        https://www.pallazzio.net/vice-image-optimizer/
 * Description:       Compress Images using Google PageSpeed Insights Image Optimizer
 * Version:           1.0.6
 * Author:            Jeremy Kozan
 * Author URI:        https://www.pallazzio.net/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       vio
 * Domain Path:       /languages
 */

// if this file is called directly, abort
if ( ! defined( 'WPINC' ) ) die;

$vio = new Vice_Image_Optimizer();
class Vice_Image_Optimizer {
	private $plugin_path;
	private $psf;

	function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );
		add_action( 'admin_menu', array( $this, 'init_settings' ), 99 );

		require_once $this->plugin_path . 'includes/pallazzio-wordpress-github-updater/pallazzio-wordpress-github-updater.php';
		new Pallazzio_WordPress_GitHub_Updater( $this->plugin_path . wp_basename( __FILE__ ), 'pallazzio' );

		require_once $this->plugin_path . 'includes/pallazzio-wordpress-settings-framework/pallazzio-wordpress-settings-framework.php';
		$this->psf = new Pallazzio_WordPress_Settings_Framework( $this->plugin_path . 'includes/settings.php', 'vio_settings' );
		add_filter( $this->psf->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );

		register_activation_hook( __FILE__,   array( $this, 'vio_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'vio_uninstall' ) );
		add_filter( 'cron_schedules',         array( $this, 'vio_add_cron_interval' ) );

		add_action( 'plugins_loaded',       array( $this, 'vio_init' ) );
		add_action( 'admin_notices',        array( $this, 'vio_notices' ) );
		add_action( 'add_attachment',       array( $this, 'vio_register_uploaded_image' ) );
		add_action( 'delete_attachment',    array( $this, 'vio_unregister_uploaded_image' ) );
		add_action( 'vice_optimize_images', array( $this, 'vio_optimize_image' ) );
	}

	/**
	 * Adds settings page.
	 *
	 * @return null
	 */
	public function init_settings() {
		$this->psf->add_settings_page( array(
			'parent_slug' => 'upload.php',
			'page_title'  => __( 'Vice Image Optimizer Settings', 'vio' ),
			'menu_title'  => __( 'VIO', 'vio' ),
		) );
	}

	/**
	 * Does settings validation.
	 * Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
	 *
	 * @param  mixed $input
	 * @return mixed $input
	 */
	function validate_settings( $input ) {
		// TODO: validate settings
		return $input;
	}

	/**
	 * Sets up stuff when plugin is activated.
	 *
	 * @return null
	 */
	function vio_install() {
		wp_schedule_event( time() + 60, 'vio_cron', 'vice_optimize_images');

		$g = get_field( 'vio_storage', 'option' );
		$to_optimize     = array();
		$optimized       = array();
		$all_attachments = array();
		if ( ! empty( $g[ 'to_optimize' ] ) ) {
			$to_optimize = json_decode( $g[ 'to_optimize' ], true );
		}
		if ( ! empty( $g[ 'optimized' ] ) ) {
			$optimized = json_decode( $g[ 'optimized' ], true );
		}

		$query = new WP_Query( array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_mime_type' => 'image',
		) );

		while ( $query->have_posts() ) : $query->the_post();
			$post_ID = get_the_ID();
			$all_attachments[] = $post_ID;
			if ( ! in_array( $post_ID, $optimized ) ) {
				if ( ! in_array( $post_ID, $to_optimize ) ) {
					$to_optimize[] = $post_ID;
				}
			}
		endwhile;

		$both_lists = array();
		$deleted = array();
		$both_lists = array_merge( $optimized, $to_optimize );
		$deleted = array_diff( $both_lists, $all_attachments );
		if ( ! empty( $deleted ) ) {
			foreach ( $deleted as $v ) {
				$k = -1;
				$k = array_search( $v, $optimized );
				if ( $k >= 0 ) {
					unset( $optimized[ $k ] );
				}
				$k = -1;
				$k = array_search( $v, $to_optimize );
				if ( $k >= 0 ) {
					unset( $to_optimize[ $k ] );
				}
			}
		}

		update_field( 
			'vio_storage',
			array(
				'optimized' => json_encode( $optimized ),
				'to_optimize' => json_encode( $to_optimize ),
			),
			'option'
		);
	}

	/**
	 * Displays various notices/warnings/errors in the WordPress admin area.
	 *
	 * @return null
	 */
	function vio_notices() {
		if ( ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
			?><div class="notice notice-error"><p><?php _e( '<strong>Vice Image Optimizer</strong> requires <strong>Advanced Custom Fields <em style="text-decoration: underline;">Pro</em></strong> to be installed and activated.', 'vio' ); ?></p></div><?php
		}

		if ( ! ini_get( 'allow_url_fopen' ) ) {
			ini_set( 'allow_url_fopen', 1 );
			if ( ! ini_get( 'allow_url_fopen' ) ) {
				?>
					<div class="notice notice-error">
						<p><?php _e( 'Error: file_get_contents(): https:// wrapper is disabled in the server configuration by allow_url_fopen=0.', 'vio' ); ?></p>
						<p><?php _e( '<strong>Vice Image Optimizer</strong> requires <strong>file_get_contents()</strong> to be enabled in the server configuration.', 'vio' ); ?></p>
					</div>
				<?php
			}
		}
		
		$conflicting_plugins = array(
			'cheetaho-image-optimizer/cheetaho.php'         => 'CheetahO Image Optimizer',
			'tiny-compress-images/tiny-compress-images.php' => 'Compress JPEG & PNG images',
			'fasterimage/fasterimage.php'                   => 'fasterImage',
			'high-compress/highcompress.php'                => 'High Compress',
			'imagify/imagify.php'                           => 'Imagify',
			'kraken-image-optimizer/kraken.php'             => 'Kraken Image Optimizer',
			'resmushit-image-optimizer/resmushit.php'       => 'reSmush.it Image Optimizer',
			'shortpixel-image-optimiser/wp-shortpixel.php'  => 'ShortPixel Image Optimizer',
			'way2enjoy-compress-images/way2enjoy.php'       => 'Way2enjoy Image Optimizer',
			'wp-image-shrinker/hetworkstinypng.php'         => 'WordPress Image Shrinker',
			'wp-smushit/wp-smush.php'                       => 'WP Smush',
			'wp-smush-pro/wp-smush.php'                     => 'WP Smush Pro',
		);
		foreach ( $conflicting_plugins as $plugin_file => $plugin_name ) {
			if ( is_plugin_active( $plugin_file ) ) {
				?><div class="notice notice-error"><p><strong><?php _e( 'Vice Image Optimizer', 'vio' ); ?></strong> <?php _e( 'and', 'vio' ); ?> <strong><?php echo $plugin_name; ?></strong> <?php _e( 'are both active. <strong><em>THIS IS VERY BAD!!!</em></strong> Both plugins provide similar functionality. Please disable one of them.', 'vio' ); ?></p></div><?php
			}
		}
	}

	/**
	 * Initializes plugin.
	 *
	 * @return null
	 */
	function vio_init() {
		if ( in_array( 'advanced-custom-fields-pro/acf.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			require_once $this->plugin_path . 'includes/add-field-groups.php';

			acf_add_options_sub_page( array(
				'page_title'  => __( 'Vice Image Optimizer', 'vio' ) . ' - ' . __( 'Settings', 'vio' ),
				'menu_title'  => __( 'Optimize', 'vio' ),
				'menu_slug'   => 'vio-settings',
				'parent_slug' => 'upload.php',
				'capability'  => 'manage_options',
			) );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'vio_add_settings_link' ) );
			add_filter( 'jpeg_quality', function( $arg ) { return 100; } );
			
			$settings = $this->psf->get_settings();
			if ( $settings[ 'general_resize' ] ) {
				add_image_size( 'vio', $settings[ 'general_max_width' ], $settings[ 'general_max_height' ], false );
				add_filter( 'wp_generate_attachment_metadata', 'vio_replace_uploaded_image' );
			}
		}
	}

	/**
	 * Adds 'Settings' link to the 'Plugins' page.
	 *
	 * @param  array $links
	 * @return array
	 */
	function vio_add_settings_link( $links ) {
		$settings_link = '<a href="upload.php?page=vio-settings">' . __( 'Settings', 'vio' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Adds newly uploaded images to the list of images to optimize.
	 *
	 * @param  int $attachment_id
	 * @return int
	 */
	function vio_register_uploaded_image( $attachment_id ) {
		$g = get_field( 'vio_storage', 'option' );
		$to_optimize = array();
		if ( ! empty( $g[ 'to_optimize' ] ) ) {
			$to_optimize = json_decode( $g[ 'to_optimize' ], true );
		}
		$to_optimize[] = $attachment_id;
		update_field(
			'vio_storage',
			array(
				'optimized' => $g[ 'optimized' ],
				'to_optimize' => json_encode( $to_optimize ),
			),
			'option'
		);

		return $attachment_id;
	}

	/**
	 * Removes deleted images from the lists 'to_optimize' and 'optimized'.
	 *
	 * @param  int $attachment_id
	 * @return null
	 */
	function vio_unregister_uploaded_image( $attachment_id ) {
		$g = get_field( 'vio_storage', 'option' );
		$to_optimize = array();
		if ( ! empty( $g[ 'to_optimize' ] ) ) {
			$to_optimize = json_decode( $g[ 'to_optimize' ], true );
			$k = array_search( $attachment_id, $to_optimize );
			if ( $k >= 0 ) {
				unset( $to_optimize[ $k ] );
			}
		}
		$optimized = array();
		if ( ! empty( $g[ 'optimized' ] ) ) {
			$optimized = json_decode( $g[ 'optimized' ], true );
			$k = array_search( $attachment_id, $optimized );
			if ( $k >= 0 ) {
				unset( $optimized[ $k ] );
			}
		}
		
		update_field( 
			'vio_storage',
			array(
				'optimized' => json_encode( $optimized ),
				'to_optimize' => json_encode( $to_optimize ),
			),
			'option'
		);
	}

	/**
	 * Converts images with extension '.jpeg' to '.jpg'.
	 *
	 * @param  array $image_data
	 * @return array
	 */
	function vio_fix_file_extension( $image_data ) {
		if ( '.jpeg' !== substr( $image_data[ 'file' ], -5 ) ) return $image_data;

		global $wpdb;
		$dir = wp_upload_dir();
		$guid = $dir[ 'baseurl' ] . '/' . $image_data[ 'file' ];
		$attachment_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) ) );
		$post = get_post( $attachment_id );
		if ( ! empty( $post->post_parent ) ) {
			$post_parent = get_post( $post->post_parent );
			$post_date = date( 'Y/m', strtotime( $post_parent->post_date ) );
			$dir = wp_upload_dir( $post_date );
		}
		$file_base = substr( $image_data[ 'file' ], strrpos( $image_data[ 'file' ], '/' ) + 1, -5 );

		if ( ! is_int( $attachment_id ) || $attachment_id < 0 ) return $image_data;

		$image_dest = $dir[ 'basedir' ] . $dir[ 'subdir' ] . '/' . $file_base . '.jpg';
		$n = 0;
		while ( file_exists( $image_dest ) ) {
			$n++;
			$s = explode( '.', $image_dest );
			$s[ count( $s ) - 2 ] .= '-' . $n;
			$image_dest = implode( '.', $s );
		}
		if ( $n > 0 ) {
			$n = '-' . $n;
		} else {
			$n = '';
		}

		rename( $dir[ 'basedir' ] . $dir[ 'subdir' ] . '/' . $file_base . '.jpeg', $dir[ 'basedir' ] . $dir[ 'subdir' ] . '/' . $file_base . $n . '.jpg' );
		$image_data[ 'file' ] = trim( $dir[ 'subdir' ], '/' ) . '/' . $file_base . $n . '.jpg';

		foreach ( $image_data[ 'sizes' ] as $k => $v ) {
			rename( $dir[ 'basedir' ] . $dir[ 'subdir' ] . '/' . $v[ 'file' ], $dir[ 'basedir' ] . $dir[ 'subdir' ] . '/' . $file_base . $n . '-' . $v[ 'width' ] . 'x' . $v[ 'height' ] . '.jpg' );
			$image_data[ 'sizes' ][ $k ][ 'file' ] = $file_base . $n . '-' . $v[ 'width' ] . 'x' . $v[ 'height' ] . '.jpg';
		}

		$wp_attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		update_post_meta( $attachment_id, '_wp_attached_file', trim( $dir[ 'subdir' ], '/' ) . '/' . $file_base . $n . '.jpg', $wp_attached_file );
		$wpdb->update( $wpdb->prefix . 'posts', array( 'guid' => $dir[ 'url' ] . '/' . $file_base . $n . '.jpg' ), array( 'guid' => $guid ) );

		return $image_data;
	}

	/**
	 * For newly uploaded images, deletes large original image and uses a smaller version in its place.
	 *
	 * @param  array $image_data
	 * @return array
	 */
	function vio_replace_uploaded_image( $image_data ) {
		//TODO: for non-jpeg images. check for transparency, convert to jpg, fetch google optimized jpg, compare sizes, delete attachment, create new attachment, update $image_data

		// Google PageSpeed Insights ignores files with the extension '.jpeg'
		$image_data = vio_fix_file_extension( $image_data );

		if ( ! isset ( $image_data[ 'sizes' ][ 'vio' ] ) ) return $image_data;

		$upload_dir = wp_upload_dir();
		$upload_subdir = substr( $image_data[ 'file' ], 0, strrpos( $image_data[ 'file' ], '/' ) );
		$original_image_location = $upload_dir[ 'basedir' ] . '/' . $image_data[ 'file' ];
		$vio_image_location = $upload_dir[ 'basedir' ] . '/' . $upload_subdir . '/' . $image_data[ 'sizes' ][ 'vio' ][ 'file' ];

		unlink( $original_image_location );
		rename( $vio_image_location, $original_image_location );

		$image_data[ 'width' ] = $image_data[ 'sizes' ][ 'vio' ][ 'width' ];
		$image_data[ 'height' ] = $image_data[ 'sizes' ][ 'vio' ][ 'height' ];
		unset( $image_data[ 'sizes' ][ 'vio' ] );

		//TODO: find other image sizes that were generated but are larger than the user settings and delete them (not sure if i should actually do this)

		return $image_data;
	}

	/**
	 * Gets an attachment, resizes and discards overly large images, and sends all image sizes to Google for optimization.
	 *
	 * @return null
	 */
	function vio_optimize_image() {
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			ini_set( 'allow_url_fopen', 1 );
			if ( ! ini_get( 'allow_url_fopen' ) ) {
				return;
			}
		}

		$g = get_field( 'vio_storage', 'option' );
		$to_optimize = array();
		$optimized = array();
		if ( ! empty( $g[ 'to_optimize' ] ) ) {
			$to_optimize = json_decode( $g[ 'to_optimize' ], true );
		}
		if ( ! empty( $g[ 'optimized' ] ) ) {
			$optimized = json_decode( $g[ 'optimized' ], true );
		}
		if ( count( $to_optimize ) < 1 ) return;

		$attachment_id = array_pop( $to_optimize );
		$attachment = wp_get_attachment_metadata( $attachment_id, true );
		$plugin_dir_path = plugin_dir_path( __FILE__ );
		$file = $plugin_dir_path . 'optimized-content/file-' . $attachment_id . '.zip';
		$upload_dir = wp_upload_dir();
		$upload_subdir = substr( $attachment[ 'file' ], 0, strrpos( $attachment[ 'file' ], '/' ) );

		//For existing large images - resize image, update attachment meta, replace original file with new size based on user settings
		$settings = $this->psf->get_settings();
		$image = wp_get_image_editor( $upload_dir[ 'basedir' ] . '/' . $attachment[ 'file' ] );
		if ( ! is_wp_error( $image ) && ( $attachment[ 'width' ] > $settings[ 'general_max_width' ] || $attachment[ 'height' ] > $settings[ 'general_max_height' ] ) ) {
			$attachment = vio_fix_file_extension( $attachment );

			$image->set_quality( 100 );
			$image->resize( $settings[ 'general_max_width' ], $settings[ 'general_max_height' ], false );
			$new_size = $image->get_size();
			$image->save( $upload_dir[ 'basedir' ] . '/' . $attachment[ 'file' ] ); //overwrite original

			$attachment[ 'width' ] = $new_size[ 'width' ];
			$attachment[ 'height' ] = $new_size[ 'height' ];
			wp_update_attachment_metadata( $attachment_id, $attachment );
		}

		$html  = '<!DOCTYPE html><html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Image ' . $attachment_id . '</title></head><body>';
		$html .= '<img src="' . $upload_dir[ 'baseurl' ] . '/' . $attachment[ 'file' ] . '" alt="' . $attachment_id . '" />';
		foreach ( $attachment[ 'sizes' ] as $size ) {
			$html .= '<img src="' . $upload_dir[ 'baseurl' ] . '/' . $upload_subdir . '/' . $size[ 'file' ] . '" alt="' . $attachment_id . '" />';
		}
		$html .= '</body></html>';
		file_put_contents( $plugin_dir_path . 'optimized-content/image-' . $attachment_id . '.html', $html );

		$page_speed_url = 'https://developers.google.com/speed/pagespeed/insights/';
		$referer_url = plugins_url( 'optimized-content/image-' . $attachment_id . '.html', __FILE__ );
		$opts = array(
			'http' => array(
				'header'  => array( 'Referer: ' . $page_speed_url . '?url=' . $referer_url ),
				'timeout' => 120,
			),
		);
		$context = stream_context_create( $opts );
		$optimized_content = file_get_contents( $page_speed_url . 'optimizeContents?url=' . $referer_url . '&strategy=mobile', false, $context );
		$response = $this->vio_parse_headers( $http_response_header );
		if ( $response[ 'reponse_code' ] === 200 ) {
			$optimized[] = $attachment_id;
			file_put_contents( $file, $optimized_content );
			$zip = new ZipArchive;
			$res = $zip->open( $file );
			if ( $res === true ) {
				$zip->extractTo( $plugin_dir_path . 'optimized-content/' );
				$zip->close();

				$optimized_image = $plugin_dir_path . 'optimized-content/image/' . end( explode( '/', $attachment[ 'file' ] ) );
				if ( is_file( $optimized_image ) ) {
					$original_image = $upload_dir[ 'basedir' ] . '/' . $attachment[ 'file' ];
					unlink( $original_image );
					rename( $optimized_image, $original_image );
				}
				if ( is_array( $attachment[ 'sizes' ] ) ) {
					foreach ( $attachment[ 'sizes' ] as $size ) {
						$optimized_image = $plugin_dir_path . 'optimized-content/image/' . $size[ 'file' ];
						if ( is_file( $optimized_image ) ) {
							$original_image = $upload_dir[ 'basedir' ] . '/' . $upload_subdir . '/' . $size[ 'file' ];
							unlink( $original_image );
							rename( $optimized_image, $original_image );
						}
					}
				}
			}
		} else {
			array_unshift( $to_optimize, $attachment_id );
		}

		$di = new RecursiveDirectoryIterator( $plugin_dir_path . 'optimized-content', FilesystemIterator::SKIP_DOTS );
		$ri = new RecursiveIteratorIterator( $di, RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $ri as $file ) {
			$file->isDir() ? rmdir( $file ) : unlink( $file );
		}
		
		update_field( 
			'vio_storage',
			array(
				'optimized' => json_encode( $optimized ),
				'to_optimize' => json_encode( $to_optimize ),
			),
			'option'
		);
		//update_field( 'vio_status', array( 'attachments_optimized' => count( $optimized ) ), 'option' );
	}

	/**
	 * Converts HTTP Response headers to a more readable format.
	 *
	 * @param  array/object $headers
	 * @return array
	 */
	function vio_parse_headers( $headers ) {
		if ( ! is_array( $headers ) && ! is_object( $headers ) ) return false;

		$head = array();
		foreach ( $headers as $k => $v ) {
			$t = explode( ':', $v, 2 );
			if ( isset( $t[ 1 ] ) ) {
				$head[ trim( $t[ 0 ] ) ] = trim( $t[ 1 ] );
			} else {
				$head[] = $v;
				$matches = array();
				if ( preg_match( '#HTTP/[0-9\.]+\s+([0-9]+)#', $v, $matches ) ) {
					$head[ 'reponse_code' ] = intval( $matches[ 1 ] );
				}
			}
		}

		return $head;
	}

	/**
	 * Clears wp_cron() job upon plugin deactivation.
	 *
	 * @return null
	 */
	function vio_uninstall() {
		wp_clear_scheduled_hook( 'vice_optimize_images' );
	}

	/**
	 * Adds wp_cron() job.
	 *
	 * @param  array $schedules
	 * @return array
	 */
	function vio_add_cron_interval( $schedules ) {
		$schedules[ 'vio_cron' ] = array(
			'interval' => 360,
			'display'  => __( 'Once Every Six Minutes', 'vio' ),
		);

		return $schedules;
	}

	/**
	 * Writes to error_log.
	 *
	 * @return null
	 */
	function vio_write_log( $log, $id = '' ) {
		error_log( '************* ' . $id . ' *************' );
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

}
