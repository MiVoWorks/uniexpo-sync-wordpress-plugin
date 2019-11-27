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

add_action("admin_menu","addMenu");

function addMenu(){
    add_menu_page("UniExpo Plugin","UniExpo",4,"uniexpo-plugin","UniExpoMenu","dashicons-smartphone",110);
}

function UniExpoMenu(){
	include_once('form.php');
}

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