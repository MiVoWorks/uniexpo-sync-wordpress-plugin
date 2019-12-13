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

function debug_func($data,$file="debug"){
  $myfile = fopen(__DIR__ .'/debug/'.$file.'.txt', 'w');
  //fwrite($myfile, json_encode( (array)$data ));
  fwrite($myfile, json_encode( $data ));
  fclose($myfile);
}

function wpDataToFirestoreData($data){
  $postData = array(  
    'fields' => array(),
  );

  foreach ($data as $key => $value){  
    $postData['fields'][$key]=array("stringValue"=>$value.""); 
   }

   //$postCategory = getPostCategory($data['ID']);
   //collection category reference
   //$postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);

  return $postData;
}

function getPostCategory($post_id){
  global $wpdb;

  $query = "SELECT * FROM (SELECT * FROM(SELECT meta_id,post_id FROM wp_postmeta WHERE post_id=".$post_id.") a
            INNER JOIN wp_term_relationships ON wp_term_relationships.object_id = a.post_id) b
            INNER JOIN wp_term_taxonomy ON wp_term_taxonomy.term_taxonomy_id = b.term_taxonomy_id LIMIT 1";

  $category = $wpdb->get_results($query);
  
  return $category;
}

function saveCategories(){
  global $wpdb;
  
  //$query = "SELECT * FROM {$wpdb->prefix}terms INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id";
  $query = "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy FROM {$wpdb->prefix}terms INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id";
  $categories = $wpdb->get_results($query);

  //New JOIN
  $query = "SELECT * FROM (SELECT categories_meta.term_id, categories_meta.name, categories_meta.taxonomy, categories_meta.meta_key, categories_meta.meta_value, {$wpdb->prefix}posts.guid FROM 
            (SELECT categories.term_id, categories.name, categories.taxonomy, {$wpdb->prefix}termmeta.meta_key, {$wpdb->prefix}termmeta.meta_value FROM 
            (SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy FROM {$wpdb->prefix}terms 
            INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id) categories
            LEFT JOIN {$wpdb->prefix}termmeta ON categories.term_id={$wpdb->prefix}termmeta.term_id) categories_meta
            LEFT JOIN {$wpdb->prefix}posts ON categories_meta.meta_value={$wpdb->prefix}posts.ID
            ORDER BY term_id) a WHERE meta_value IS NOT NULL";
  
  $meta_categories = $wpdb->get_results($query);

  foreach ($meta_categories as $key => $element) {
    foreach($categories as $new_key => $new_element){
      if($element->term_id == $new_element->term_id){
        $meta_key = $element->meta_key;
        if($element->guid != null){
          $categories[$new_key]->$meta_key = $element->guid;
        }else{
          $categories[$new_key]->$meta_key = $element->meta_value;
        }
       
      }
    }
  }

  foreach ($categories as $key => $element) {
    sendDataToFirestore(wpDataToFirestoreData($element),false,$element->taxonomy,$element->term_id, true);
  }
}

//saveCategories();

/**
 * @param {Array} data - Array Representation of the POST
 * @param {Boolean} shouldIDoAConversion
 * @param {String} action_type - fetched if publish or update in firestore
 * @param {Boolean} isCategory - check if category -> categories don't have reference value
 */
function sendDataToFirestore($postData, $shouldIDoAConversion=true, $type, $id, $action_type, $isCategory){
  
  //$postMeta=get_post_meta($data['ID']);
  if($shouldIDoAConversion){
    $type=$postData['post_type'];
    $id=$postData['ID'];
    $postData=wpDataToFirestoreData($postData);
  }

  if(!$isCategory){
    $postCategory = getPostCategory($postData["fields"]["ID"]['stringValue']);
    //collection category reference
    $postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);
  }
  
  //if publish post
  if($action_type == "publish"){
    $url = "https://firestore.googleapis.com/v1/projects/".get_option('firebase_projectid')."/databases/(default)/documents/".$type."?documentId=".$id;
  
    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => json_encode($postData),
      'method'      => 'POST',
      'data_format' => 'body',
    ));
  //if update post
  }else if($action_type == "update"){
    $url = "https://firestore.googleapis.com/v1/projects/".get_option('firebase_projectid')."/databases/(default)/documents/".$type."/".$id;
  
    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => json_encode($postData),
      'method'      => 'PATCH',
      'data_format' => 'body',
    ));
  }
}

/*add_action( 'init', 'wpse34410_init', 0, 99 );
  function wpse34410_init() 
  {
      $arrayTypes = array();
      $types = get_post_types( [], 'objects' );
      foreach ( $types as $type ) {
          if ( isset( $type->name ) ) {
              // you'll probably want to do something else.
             // debug_func($type->name,"data".$type->name);
             array_push($arrayTypes, $type->name);
          }
      }

      debug_func($arrayTypes,"data");
     
  }*/

/**
 * Synchronize post on publish new post
 */
function action_publish_post( $post_id, $post ) { 

  //debug_func($post,'event');
  
  //if is ID of author return his display name
  if(intval($post->post_author)){
    $author = get_userdata($post->post_author);
    $post->post_author = $author->display_name;
  }

  global $wpdb;
  $query = "SELECT categories.name FROM (SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy 
            FROM {$wpdb->prefix}terms 
            INNER JOIN {$wpdb->prefix}term_taxonomy 
            ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id) categories
            INNER JOIN {$wpdb->prefix}term_relationships
            ON {$wpdb->prefix}term_relationships.term_taxonomy_id=categories.term_id 
            AND {$wpdb->prefix}term_relationships.object_id=".$post_id;

  $category_name = $wpdb->get_results($query);
  $post->category_name = $category_name[0]->name;

  sendDataToFirestore((array) $post, true, $post->post_type, $post_id, "publish", false);
}; 

/**
 * Synchronize post on update post
 */
function action_update_post($post_id, $post){

  //debug_func($post,'eventu');
  //if is ID of author return his display name
  if(intval($post->post_author)){
    $author = get_userdata($post->post_author);
    $post->post_author = $author->display_name;
  }
  
  global $wpdb;
  $query = "SELECT categories.name FROM (SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy 
            FROM {$wpdb->prefix}terms 
            INNER JOIN {$wpdb->prefix}term_taxonomy 
            ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id) categories
            INNER JOIN {$wpdb->prefix}term_relationships
            ON {$wpdb->prefix}term_relationships.term_taxonomy_id=categories.term_id 
            AND {$wpdb->prefix}term_relationships.object_id=".$post_id;

  $category_name = $wpdb->get_results($query);
  $post->category_name = $category_name[0]->name;
  
  sendDataToFirestore((array) $post, true, $post->post_type, $post_id, "update", false);
}


function subscribeToDifferentPostTypes($postTypes){
  //check if postTypes is array
  if(is_array($postTypes)){
    foreach ($postTypes as $key => $type) {
      //on post publish
      add_action('publish_'.$type, 'action_publish_post', 10, 2);
      //add_action('update_'.$type, 'action_update_post', 10, 2);
  
      //on post update
      add_action('save_'.$type, 'action_update_post', 10, 3);
    }
  //check if postTypes is string -> only one postTypes
  }else{
     //on post publish
     add_action('publish_'.$postTypes, 'action_publish_post', 10, 2);
     //add_action('update_'.$type, 'action_update_post', 10, 2);
 
     //on post update
     add_action('save_'.$postTypes, 'action_update_post', 10, 3);
  }
  
}

if(get_option('post_types_array')){
  subscribeToDifferentPostTypes(get_option('post_types_array'));
}

//subscribeToDifferentPostTypes(['post','event']);


//add_action( 'publish_post', 'action_publish_post', 10, 1 );

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
