<?php

defined( 'ABSPATH' ) || exit;

class MpopDiscourseUtilities extends WPDiscourse\Utilities\Utilities {
    public static function get_discourse_groups($force = false) {
		if ($force) {delete_transient('wpdc_non_automatic_groups');}
		return parent::get_discourse_groups();
    }
	public static function get_discourse_mpop_groups($force = false) {
		$res = static::get_discourse_groups($force);
		if (is_wp_error($res)) {return $res;}
		return array_values(array_filter($res, function($g) { return str_starts_with($g->name, 'mp_'); }));
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
	public static function create_discourse_group(string $name = '', string $full_name = '', string $bio = '', array $params = []) {
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
		if (!is_wp_error($res) && !empty($res->basic_group)) {
			delete_transient('wpdc_non_automatic_groups');
		}
		return $res;
    }
	public static function update_discourse_group($id, array $params = []) {
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
		if(is_wp_error($disc_user) || empty($disc_user->user)) {
			return new WP_Error( 'wpdc_response_error', 'No users were returned from Discourse.' );
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
    public static function get_mpop_discourse_groups_by_user($user_id) {
        $res = static::get_discourse_groups_by_user($user_id);
        if (is_wp_error($res)) {return false;}
        return array_values(array_filter($res, function($g) { return str_starts_with($g, 'mp_'); }));
    }
	public static function logout_user_from_discourse($user_id) {
		$user = false;
		if (is_object($user_id)) {
			$user = $user_id;
		} else {
			$user = get_user_by('ID',intval($user_id));
		}
		if (!$user) {
			return false;
		}
		if ($user->discourse_sso_user_id) {
			$res = $this->discourse_request( "/admin/users/$user->discourse_sso_user_id/log_out", array( 'method' => 'POST' ) );
			if (is_wp_error($res)) {
				return false;
			}
		}
		return true;
	}
}