<?php

class WikipopClient {
    private array $cookieJar = [];
    private string $baseURL = '';
    private string $username = '';
    private string $password = '';
    private bool $authenticated = false;
    private array $managedGroups = [];
    private bool $debug = false;

    function __construct(string $baseURL, string $username, string $password, array $managedGroups = [], $debug = false) {
        $this->baseURL = $baseURL;
        $this->username = $username;
        $this->password = $password;
        $this->managedGroups = $managedGroups;
        $this->debug = $debug;
    }
    private function getCookies() {
        $res = '';
        foreach($this->cookieJar as $k=>$v) {
            $res .= "$k=$v; ";
        }
        return trim($res);
    }
    private function curlInit($url, $settings = []) {
        $curlObj = curl_init($url);
        $settings += [
            CURLOPT_AUTOREFERER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_TIMEOUT => 5
        ];
        if ($this->debug) {
            $settings += [
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            ];
        }
        foreach($settings as $key => $value) {
            curl_setopt($curlObj, $key, $value);
        }
        return $curlObj;
    }
    private function execWithCookies($curlObj) {
        curl_setopt($curlObj, CURLOPT_HEADER, true);
        curl_setopt($curlObj, CURLOPT_COOKIE, $this->getCookies());
        $res = curl_exec($curlObj);
        if ($res === false) {
            throw new Exception(curl_error($curlObj));
        }
        $header_len = curl_getinfo($curlObj, CURLINFO_HEADER_SIZE);
        $curlHeader = substr($res, 0, $header_len);
        $curlBody = substr($res, $header_len);
        if (preg_match_all("/^Set-Cookie:\s+(.*);/mU", $curlHeader, $cookieMatchArray)) {
            foreach ($cookieMatchArray[1] as $c) {
                $c_arr = explode('=', $c);
                $this->cookieJar[$c_arr[0]] = $c_arr[1];
            }
        }
        return $curlBody;
    }
    private function getTokens($types = 'csrf') {
        $curlObj = $this->curlInit($this->baseURL . "/api.php?action=query&meta=tokens&type=$types&format=json");
        curl_setopt($curlObj, CURLOPT_POST, true);
        $res = json_decode($this->execWithCookies($curlObj), true);
        return $res['query']['tokens'];
    }
    private function authenticate($force = false) {
        if (!$this->authenticated || $force) {
            $token = $this->getTokens('login')['logintoken'];
            $curlObj = $this->curlInit($this->baseURL . "/api.php");
            curl_setopt($curlObj, CURLOPT_POST, true);
            curl_setopt($curlObj, CURLOPT_HTTPHEADER, [
                "Content-Type: application/x-www-form-urlencoded"
            ]);
            curl_setopt($curlObj, CURLOPT_POSTFIELDS, "action=login&lgname=" . urlencode($this->username) . "&lgpassword=" . urlencode($this->password) . "&lgtoken=" . urlencode($token) . "&format=json");
            $res = json_decode($this->execWithCookies($curlObj), true);
            if ($res['login']['result'] == 'Success') {
                $this->authenticated = true;
            } else {
                throw new Exception($res);
            }
        }
    }
    public function getUsersByUsername($usernames, $usprop='groups') {
        $curlObj = $this->curlInit($this->baseURL . "/api.php?action=query&format=json&usprop=".urlencode($usprop)."&list=users&ususers=" . urlencode($usernames));
        $res = curl_exec($curlObj);
        if ($res === false) {
            throw new Exception(curl_error($curlObj));
        }
        return json_decode($res, true)['query']['users'];
    }
    public function setUserGroups($user, array $groups = []) {
        $filteredGroups = [];
        foreach($groups as $g) {
            if (in_array($g, $this->managedGroups)) {
                $filteredGroups[] = $g;
            } 
        }
        $filteredGroups = array_values(array_unique($filteredGroups));
        if (!is_object($user)) {
            $user = get_user_by('ID', $user);
        }
        if ($user) {
            return false;
        }
        $wikiUser = $this->getUsersByUsername($user->user_login)[0];
        if (!isset($wikiUser['userid'])) {
            return false;
        }
        $gToAdd = [];
        $gToRem = [];
        foreach($filteredGroups as $g) {
            if (!in_array($g, $wikiUser['groups'])) {
                $gToAdd[] = $g;
            }
        }
        foreach($wikiUser['groups'] as $g) {
            if (in_array($g, $this->managedGroups) && !in_array($g, $filteredGroups)) {
                $gToRem[] = $g;
            }
        }
        if (!empty($gToAdd) || !empty($gToRem)) {
            $this->authenticate();
            $token = $this->getTokens('userrights')['userrightstoken'];
            $curlObj = $this->curlInit($this->baseURL . "/api.php?action=userrights&format=json&user=". urlencode($wikiUser['name']) . (!empty($gToAdd) ? "&add=" . urlencode(implode('|', $gToAdd)) : '' ) . (!empty($gToRem) ? "&remove=" . urlencode(implode('|', $gToRem)) : '' ));
            curl_setopt($curlObj, CURLOPT_POST, true);
            curl_setopt($curlObj, CURLOPT_POSTFIELDS, "token=" . urlencode($token));
            return $this->execWithCookies($curlObj);
        }
    }
    public function blockUser($user, string $reason = '') {
        // if (!is_object($user)) {
        //     $user = get_user_by('ID', $user);
        // }
        // if ($user) {
        //     return false;
        // }
        // $wikiUser = $this->getUsersByUsername($user->user_login)[0];
        $wikiUser = $this->getUsersByUsername($user, 'blockinfo')[0];
        if (isset($wikiUser['blockid'])){
            return true;
        }
        $this->authenticate();
        $token = $this->getTokens()['csrftoken'];
        $curlObj = $this->curlInit($this->baseURL . "/api.php?action=block&allowusertalk=false&noemail=true&format=json&user=" . urlencode($wikiUser['name']) . ($reason? 'reason=' . urlencode($reason) : ''));
        curl_setopt($curlObj, CURLOPT_POST, true);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, "token=" . urlencode($token));
        return $this->execWithCookies($curlObj);
    }
    public function unlockUser($user, string $reason = '') {
        // if (!is_object($user)) {
        //     $user = get_user_by('ID', $user);
        // }
        // if ($user) {
        //     return false;
        // }
        // $wikiUser = $this->getUsersByUsername($user->user_login)[0];
        $wikiUser = $this->getUsersByUsername($user, 'blockinfo')[0];
        if (!isset($wikiUser['blockid'])){
            return true;
        }
        $this->authenticate();
        $token = $this->getTokens()['csrftoken'];
        $curlObj = $this->curlInit($this->baseURL . "/api.php?action=unblock&format=json&user=" . urlencode($wikiUser['name']) . ($reason? 'reason=' . urlencode($reason) : ''));
        curl_setopt($curlObj, CURLOPT_POST, true);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, "token=" . urlencode($token));
        return $this->execWithCookies($curlObj);
    }
}

$wikiClient = new WikipopClient('https://wiki.test.mpop', 'Wp system@wp_system', 'he0tevt3gdagf6a4roj03iuhh7ul2j93', ['writer'], true);
var_dump($wikiClient->blockUser('wp_system'));