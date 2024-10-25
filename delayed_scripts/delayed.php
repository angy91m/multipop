<?php
    if ($argc > 1) {
        $GLOBALS['mpop_delayed_action'] = true;
        require_once(__DIR__ . '/../../../../wp-load.php');
        $mpop = new MultipopPlugin();
        if (isset($mpop->delayed_scripts[$argv[1]])) {
            $mpop->delayed_scripts[$argv[1]](...array_slice($argv, 2));
        }
    }