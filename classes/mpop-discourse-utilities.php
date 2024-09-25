<?php

defined( 'ABSPATH' ) || exit;

class MpopDiscourseUtilities extends WPDiscourse\Utilities\Utilities {
    public static function get_discourse_groups($force = false) {
        if (!$force) {return parent::get_discourse_groups();}
        $path                = '/groups';
		$response            = static::discourse_request( $path );
		$discourse_page_size = 36;

		if ( ! is_wp_error( $response ) && ! empty( $response->groups ) ) {
			$groups         = static::extract_groups( $response->groups );
			$total_groups   = $response->total_rows_groups;
			$load_more_path = $response->load_more_groups;

			if ( ( $total_groups > $discourse_page_size ) ) {
				$last_page = ( ceil( $total_groups / $discourse_page_size ) ) - 1;

				foreach ( range( 1, $last_page ) as $index ) {
					if ( $load_more_path ) {
						$response       = static::discourse_request( $load_more_path );
						$load_more_path = $response->load_more_groups;

						if ( ! is_wp_error( $response ) && ! empty( $response->groups ) ) {
						 	$groups = array_merge( $groups, static::extract_groups( $response->groups ) );
						}
					}
				}
			}

			set_transient( 'wpdc_non_automatic_groups', $groups, 10 * MINUTE_IN_SECONDS );

			return $groups;
		} else {
			return new WP_Error( 'wpdc_response_error', 'No groups were returned from Discourse.' );
		}
    }
}