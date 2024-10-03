<?php
    if ($argc) {
        require_once(__DIR__ . '/../../../../wp-load.php');
        $mpop = new MultipopPlugin();
        $func = $mpop->delayed_scripts[$argv[0]];
        if ($func) {
            $func(...array_slice($argv, 1));
        }
    }