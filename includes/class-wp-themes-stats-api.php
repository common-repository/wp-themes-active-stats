<?php
/**
 * Calling W.ORG API Response.
 *
 * @package WP Themes Active Stats
 * @author Brainstorm Force
 */

/**
 * Helper class for the ActiveCampaign API.
 *
 * @since 1.0.0
 */
class WP_Themes_Stats_Api {
	/**
	 * Constructor calling W.ORG API Response.
	 */
	function __construct() {
		add_shortcode( 'wp_theme_active_install', array( $this, 'bsf_display_active_installs' ) );
	}

	/**
	 * Get the Theme Details.
	 *
	 * @param int $action Get attributes Theme Details.
	 * @param int $api_params Get attributes Theme Details.
	 */
	function get_theme_activate_installs( $action, $api_params = array() ) {
		$theme_slug       = isset( $api_params['theme'] ) ? $api_params['theme'] : '';
		$activet_installs = get_transient( "bsf_active_status_$theme_slug" );
		if ( false === $activet_installs ) {

			$url = 'https://api.wordpress.org/themes/info/1.0/';
			if ( wp_http_supports( array( 'ssl' ) ) ) {
				$url = set_url_scheme( $url, 'https' );
			}

			$args      = (object) $api_params;
			$http_args = array(
				'body' => array(
					'action'  => $action,
					'timeout' => 15,
					'request' => serialize( $args ),
				),
			);

			$request = wp_remote_post( $url, $http_args );

			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );

				$themes_list = ( is_object( $response ) && isset( $response->themes ) ) ? $response->themes : array();

				// If theme list is not returned, retuen false.
				if ( ! isset( $themes_list[0] ) ) {
					return false;
				}

				$activet_installs = $themes_list[0]->active_installs;
				set_transient( "bsf_active_status_$theme_slug", $activet_installs, 604800 );
			}
		}

		return $activet_installs;
	}

	/**
	 * Display Active Install Count.
	 *
	 * @param int $atts Get attributes theme_name and theme_author.
	 */
	function bsf_display_active_installs( $atts ) {

		$atts = shortcode_atts(
			array(
				'wp_theme_slug'   => isset( $atts['wp_theme_slug'] ) ? $atts['wp_theme_slug'] : '',
				'theme_author' => isset( $atts['theme_author'] ) ? $atts['theme_author'] : '',
			), $atts
		);

		$active_installs = false;
		$wp_theme_slug   = $atts['wp_theme_slug'];
		$wp_theme_author = $atts['theme_author'];

		// bail early if theme name is not provided.
		if ( '' == $wp_theme_slug ) {
			return 'Please Verify Theme Details!';
		}

		if ( '' != $wp_theme_slug && false != $wp_theme_slug ) {
			$api_params = array(
				'theme'    => $wp_theme_slug,
				'author'   => $wp_theme_author,
				'per_page' => 1,
				'fields'   => array(
					'homepage'        => false,
					'description'     => false,
					'screenshot_url'  => false,
					'active_installs' => true,
				),
			);

			$active_installs = $this->get_theme_activate_installs( 'query_themes', $api_params );

			// return if we get false response.
			if ( false == $active_installs ) {
				return 'Please Verify Theme Details!';
			}
		}

		return $active_installs;
	}
}

new WP_Themes_Stats_Api();
