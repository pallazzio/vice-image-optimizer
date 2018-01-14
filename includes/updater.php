<?php

if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'Pallazzio_WordPress_GitHub_Updater' ) ) :

class Pallazzio_WordPress_GitHub_Updater {
	private $plugin          = null; // e.g. 'plugin-folder/plugin-file.php'
	private $plugin_file     = null; // e.g. '/home/user/public_html/wp-content/plugins/plugin-folder/plugin-file.php'
	private $github_user     = null; // e.g. 'pallazzio'
	private $github_repo     = null; // e.g. 'plugin-folder'
	private $github_response = null; // array - info about new version from github
	private $access_token    = null; // string - optional - for private github repo
	private $plugin_data     = null; // array - info about currently installed version
	private $plugin_active   = null; // bool

	/**
	 * Class constructor.
	 *
	 */
	function __construct( $plugin_file = null, $github_user = null, $access_token = null ) {
		$plugin_file_r = explode( '/', $plugin_file );

		$this->plugin_file  = $plugin_file;
		$this->github_user  = $github_user;
		$this->github_repo  = $plugin_file_r[ count( $plugin_file_r ) - 2 ];
		$this->access_token = $access_token;
		$this->plugin       = $this->github_repo . '/' . end( $plugin_file_r );

		add_filter( 'plugins_api',                           array( $this, 'admin_area_show_plugin_info' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ),            10, 1 );
		add_filter( 'upgrader_pre_install',                  array( $this, 'pre_install'  ),                10, 3 );
		add_filter( 'upgrader_post_install',                 array( $this, 'post_install' ),                10, 3 );
	}

	private function init_plugin_data() {
		return get_plugin_data( $this->plugin_file );
	}

	private function github_api_fetch( $github_user, $github_repo, $access_token = null ) {
		$url = 'https://api.github.com/repos/' . $github_user . '/' . $github_repo . '/releases';

		if ( ! empty( $access_token ) ) {
			$url = add_query_arg( array( 'access_token' => $access_token ), $url );
		}

		$github_response = wp_remote_retrieve_body( wp_remote_get( $url ) );

		// TODO: check to make sure the response isn't just a message saying that the request limit has been exceeded.

		if ( ! empty( $github_response ) ) {
			$github_response = @json_decode( $github_response );
		}

		if ( is_array( $github_response ) ) {
			$github_response = $github_response[ 0 ];
		}

		$matches = null;
		preg_match( '/tested:\s([\d\.]+)/i', $github_response->body, $matches );
		if ( ! empty( $matches ) && is_array( $matches ) && count( $matches ) > 1 ) {
			$github_response->tested = $matches[ 1 ];
		}

		return $github_response;
	}

	public function admin_area_show_plugin_info( $result, $action = null, $args = null ) {
		return $result;
	}

	public function modify_transient( $transient ) {
		if ( isset( $transient->response[ $this->plugin ] ) ) return $transient;

		$last_github_call_time = get_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater_Time' );
		if ( $last_github_call_time && time() - $last_github_call_time < 60 * 60 * 6 ) { //Don't call github more than once every six hours.
			if ( ! empty( get_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater' ) ) ) {
				$transient->response[ $this->plugin ] = json_decode( get_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater' ) );
			} else {
				unset( $transient->response[ $this->plugin ] );
			}
		} else {
			$this->plugin_data     = $this->init_plugin_data();
			$this->github_response = ! empty( $this->github_response ) ? $this->github_api_fetch( $this->github_user, $this->github_repo, $this->access_token ) : null;

			update_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater_Time', time() );

			if ( ! version_compare( $this->github_response->tag_name, $this->plugin_data[ 'Version' ] ) ) {
				update_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater', '' );
				return $transient;
			}

			$obj              = new stdClass();
			$obj->slug        = $this->github_repo;
			$obj->plugin      = $this->plugin;
			$obj->url         = $this->plugin_data[ 'PluginURI' ];
			$obj->new_version = $this->github_response->tag_name;
			$obj->package     = $this->github_response->zipball_url;

			if ( isset( $this->github_response->tested ) ) {
				$obj->tested  = $this->github_response->tested;
			}

			$transient->response[ $this->plugin ] = $obj;

			update_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater', wp_json_encode( $transient->response[ $this->plugin ] ) );
		}

		return $transient;
	}

	public function pre_install( $true, $args ) {
		$this->plugin_active = is_plugin_active( $this->plugin );
	}

	public function post_install( $true, $hook_extra, $result ) {
		global $wp_filesystem;

		$plugin_path = substr( $this->plugin_file, 0, strrpos( $this->plugin_file, '/' ) - 1 );
		$wp_filesystem->move( $result[ 'destination' ], $plugin_path );
		$result[ 'destination' ] = $plugin_path;

		//TODO: parse .gitmodules file and grab the contents of included repos
		/*
		$modules = parse_ini_file( wp_file_get_contents( $plugin_path . '.gitmodules' ) );
		foreach ( $modules as $module ) {
			$github_response = $this->github_api_fetch( $module[ 'github_user' ], $module[ 'github_repo' ], $module[ 'access_token' ] );

			$zipball = wp_remote_get( $github_response->zipball_url );

			$wp_filesystem->unzip_file( $zipball, $plugin_path . 'includes/' . $module[ 'github_repo' ] . '/' );
		}
		*/

		if ( $this->plugin_active ) {
			$activate = activate_plugin( $this->plugin );
		}

		update_option( $this->github_repo . '_Pallazzio_WordPress_GitHub_Updater', '' );

		return $result;
	}

}

endif;
