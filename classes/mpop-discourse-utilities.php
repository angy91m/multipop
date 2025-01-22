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
    public static function get_discourse_group($name) {
        return static::discourse_request("/groups/$name.json");
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
    public static function add_discourse_group_owners(int $id, array $owners = []) {
        return static::discourse_request("/groups/$id/owners.json",
            ['method' => 'PUT', 'body' => ['usernames' => implode(',', $owners)]]
        );
    }
    public static function delete_discourse_group_owner(int $id, $user_id) {
        $res = static::discourse_request("/admin/groups/$id/owners.json",
            ['method' => 'DELETE', 'body' => ['user_id' => intval($user_id)]]
        );
        return $res;
    }
    public static function get_group_members($name, int $limit = 100, int $offset = 0) {
        return static::discourse_request("/groups/$name/members.json?limit=$limit&offset=$offset");
    }
    public static function get_discourse_group_owners($name) {
        $res = static::get_group_members($name, 0, 0);
        if (is_wp_error($res)) {return $res;}
        return $res->owners;
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
		$groups = [];
        foreach($disc_user->user->groups as $g) {
            if ($g->automatic && !$auto_groups) {
                continue;
            }
            $groups[] = ['id' => $g->id, 'name' => $g->name];

        }
        return $groups;
    }
    public static function get_mpop_discourse_groups_by_user($user_id) {
        $res = static::get_discourse_groups_by_user($user_id);
        if (is_wp_error($res)) {return false;}
        return array_values(array_filter($res, function($g) { return str_starts_with($g['name'], 'mp_'); }));
    }
    public static function update_mpop_discourse_groups_by_user($user_id, $new_groups = []) {
        $user = false;
		if (is_object($user_id)) {
			$user_id = $user_id->ID;
		}
        $user = get_user_by('ID',intval($user_id));
		if (!$user || !$user->discourse_sso_user_id) {
			return false;
		}
        $current_groups = static::get_mpop_discourse_groups_by_user($user);
        if (is_wp_error($current_groups)) {return $current_groups;}
        $add_groups = [];
        $remove_groups = [];
        $owner_changes = [];
        foreach($new_groups as $group) {
            $found = array_filter($current_groups, function($g) use ($group) {return $g['name'] == $group['name'];});
            $found = array_pop($found);
            if (!$found) {
                $add_groups[] = $group;
            } else if ($found) {
                $owner_changes[] = ['id' => $found['id'], 'name' => $group['name'], 'owner' => $group['owner']];
            }
        }
        foreach($current_groups as $group) {
            $found = array_filter($new_groups, function($g) use ($group) {return $g['name'] == $group['name'];});
            if (!array_pop($found) ) {
                $remove_groups[] = $group;
            }
        }
        if (!empty($remove_groups)) {
            static::remove_user_from_discourse_group($user->ID, implode(',',array_map(function($g) {return $g['name'];}, $remove_groups)));
        }
        if (!empty($add_groups)) {
            $disc_groups = static::get_discourse_mpop_groups();
            foreach($add_groups as $group) {
                $found = array_filter($disc_groups, function($g) use ($group) {return $g->name == $group['name'];});
                $found = array_pop($found);
                if (!$found) {
                    $res = static::create_discourse_group($group['name'], $group['full_name']);
                    if ($group['owner']) {
                        $owner_changes[] = ['id' => $res->basic_group->id, 'name' => $group['name'], 'owner' => $group['owner'], 'new' => true];
                    }
                } else if ($group['owner']) {
                    $owner_changes[] = ['id' => $found->id, 'name' => $group['name'], 'owner' => $group['owner']];
                }
            }
            static::add_user_to_discourse_group($user->ID, implode(',', array_map(function($g) {return $g['name'];}, $add_groups)));
        }
        foreach($owner_changes as $change) {
            if (isset($change['new']) && $change['new']) {
                static::add_discourse_group_owners($change['id'], [$user->user_login]);
            } else {
                if ($change['owner']) {
                    static::add_discourse_group_owners($change['id'], [$user->user_login]);
                } else {
                    static::delete_discourse_group_owner($change['id'], $user->discourse_sso_user_id);
                }
            }
        }
        if (isset($new_groups[0]) && $new_groups[0]['name'] == 'mp_disabled_users') {
            $res = static::discourse_request( "/admin/users/$user->discourse_sso_user_id/log_out", ['method' => 'POST'] );
			if (is_wp_error($res)) {
				return false;
			}
        }
    }
	public static function logout_user_from_discourse($user_id) {
		$user = false;
		if (is_object($user_id)) {
			$user_id = $user_id->ID;
		}
        $user = get_user_by('ID',intval($user_id));
		if (!$user) {
			return false;
		}
		if (isset($user->discourse_sso_user_id)) {
			$res = static::discourse_request( "/admin/users/$user->discourse_sso_user_id/log_out", array( 'method' => 'POST' ) );
			if (is_wp_error($res)) {
				return false;
			}
		}
		return true;
	}
    public static function create_category( array $body ) {
        $res = static::discourse_request(
            "/categories.json",
            [
                'method' => 'POST',
                'body' => $body + [
                    'permissions' => [
                        // 1: Create
                        // 2: Reply
                        // 3: Read
                        'mp_enabled_users' => 2
                    ]
                ]
            ]
        );
		return $res;
    }
    public static function update_category( int $id, array $body ) {
        $res = static::discourse_request(
            "/categories/$id",
            [
                'method' => 'PUT',
                'body' => $body
            ]
        );
		return $res;
    }
}