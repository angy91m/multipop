<?php
    if ($argc > 1) {
        require_once(__DIR__ . '/../../../../wp-load.php');
        $mpop = new MultipopPlugin();
        $mpop->delayed_action = true;
        if (isset($mpop->delayed_scripts[$argv[1]])) {
            $mpop->delayed_scripts[$argv[1]](...array_slice($argv, 2));
        }
    }