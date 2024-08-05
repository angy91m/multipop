<?php
/**
 * @package Multipop
 * @version 0.1.0
 */
/*
Plugin Name: Multipop
Plugin URI: http://multipopolare.it
Description: Il plugin di Multipopolare
Author: angy91m
Version: 0.1.0
Author URI: https://www.facebook.com/Hamburg91/
*/

defined( 'ABSPATH' ) || exit;
define( 'MULTIPOP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// REGISTER FUNCTIONS AND HOOKS
require_once( __DIR__ . '/classes/multipop-plugin.php');

new MultipopPlugin();

?>