<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://mobidonia.com
 * @since             1.0.0
 * @package           Uniexpo
 *
 * @wordpress-plugin
 * Plugin Name:       UniExpo
 * Plugin URI:        https://reactappbuilder.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Mobidonia
 * Author URI:        https://mobidonia.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       uniexpo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Add UniExpo menu in Admin navigation
 */
add_action("admin_menu","addMenu");

function addMenu(){
    add_menu_page("UniExpo Plugin","UniExpo",4,"uniexpo-plugin","UniExpoMenu","dashicons-smartphone",110);
}

function UniExpoMenu(){
	include_once('form.php');
}

/**
 * Synchronize posts on publish new post
 */
/*add_action('publish_post', 'onPostPublish');

function onPostPublish(){
	$url = "https://firestore.googleapis.com/v1/projects/".get_option('firebase_projectid')."/databases/(default)/documents/post?documentId=100";
   

    $response = wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => array(),
      'method'      => 'POST',
      'data_format' => 'body',
  ));

  if ( is_wp_error( $response ) ) {
    $error_message = $response->get_error_message();
    echo "Something went wrong: $error_message";
  } else {
      echo 'Response:<pre>';
      print_r( $response );
      echo '</pre>';
  }
}*/

function action_publish_post( $post ) { 
	//$postID = $post->ID; 
	//print_r($post);
}; 
add_action( 'publish_post', 'action_publish_post', 10, 1 );



/*add_action('admin_menu','my_admin_plugin');

function my_admin_plugin() {
    wp_register_script( 'my_plugin_script', plugins_url('/my_plugin.js', __FILE__), array('jquery'));
    wp_enqueue_script( 'my_plugin_script' );
}*/

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'UNIEXPO_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-uniexpo-activator.php
 */
function activate_uniexpo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-uniexpo-activator.php';
	Uniexpo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-uniexpo-deactivator.php
 */
function deactivate_uniexpo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-uniexpo-deactivator.php';
	Uniexpo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_uniexpo' );
register_deactivation_hook( __FILE__, 'deactivate_uniexpo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-uniexpo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_uniexpo() {

	$plugin = new Uniexpo();
	$plugin->run();
}
run_uniexpo();