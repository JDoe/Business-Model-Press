<?php
/*
Plugin Name: Business Model Press
Plugin URI: http://bmpress.io
Description: Easily publish your business model assumptions directly from your WordPress dashboard, and get feedback from customers and stakeholders using the WordPress commenting system.
Version: 0.4
Author: Mikal Madsen and Tor Grønsund
Author URI: http://bmpress.io
License: GNU General Public License (GPL) version 3
*/
$bm_press;
include('includes/data-structure.php');
include('includes/events.php');
include('includes/render.php');
// initialize common globals and hook up to wordpress

class BM_Press {

	public $version = 0.4;
	public $d = null;
	public $e = null;
	public $r = null;
	function __construct() {
		$this->d = new BM_Press_Data_Structure();
		$this->e = new BM_Press_Events();
		$this->r = new BM_Press_Render();
	}
}
/*
Runs after WordPress has finished loading but before any headers are sent.
Useful for intercepting $_GET or $_POST triggers.
- this would be where we could intercept our own headers,
  as sent to $url_dir_bm_press .'bm-press.php?m=039dJRJE'
*/
  add_action('init', function() {
	global $bm_press;
	$bm_press = new BM_Press();
});
add_option( "bm_press_db_version", "0.3" );

// code to be run when the plugin is activated
/*register_activation_hook( __FILE__, function() {
	global $wpdb;
} );
*/
