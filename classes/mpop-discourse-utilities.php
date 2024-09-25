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
	public static function default_discourse_group_settings() {
        return [
            'mentionable_level' => 4,
            'messageable_level' => 4,
            'visibility_level' => 0,
            'primary_group' => false,
            'public_admission' => false,
            'public_exit' => false,
            'allow_membership_requests' => false,
            'members_visibility_level' => 2
        ];
    }
	public static function create_discourse_group($name = '', $full_name = '', $bio = '', $params = []) {
        if (!$name) {return false;}
        if (!$full_name) {$full_name = $name;}
        $res = static::discourse_request(
            "/admin/groups.json",
            [
                'method' => 'POST',
                'body' => [
                    'group' => [
                        'name' => $name,
                        'full_name' => $full_name,
                        'bio_raw' => $bio
                    ] + $params + static::default_discourse_group_settings()
                ]
            ]
        );
		if ($res && $res->basic_group) {
			delete_transient('wpdc_non_automatic_groups');
		}
		return $res;
    }
	public static function update_discourse_group($id, $params = null) {
        if (empty($params)) { return false; }
        return static::discourse_request(
            "/groups/$id.json",
            [
                'method' => 'PUT',
                'body' => ['group' => $params]
            ]
        );
    }
	public static function mpop_discourse_user($user_id) {
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        return static::discourse_request("/u/by-external/$user_id.json");
    }
	public static function get_discourse_groups_by_user($user_id, $auto_groups = false) {
		$disc_user = static::mpop_discourse_user($user_id);
		if(!$disc_user || !is_object($disc_user) || !$disc_user->user) {
			return false;
		}
		$group_names = [];
        foreach($disc_user->user->groups as $g) {
            if ($g->automatic && !$auto_groups) {
                continue;
            }
            $group_names[] = $g->name;
        }
        return $group_names;
    }
}