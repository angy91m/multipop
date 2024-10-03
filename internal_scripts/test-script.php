<?php
    require_once(__DIR__ . '/../../../../wp-load.php');
    $mpop = new MultipopPlugin();
    save_test($mpop->discourse_utilities()->get_discourse_mpop_groups());