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
 * Description:       This is a plugin to sync data to your Firestore.
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

define("FIRESTORE_URL", "https://firestore.googleapis.com/v1/projects/".get_option('firebase_projectid')."/databases/(default)/documents/");
define("FIRESTORE_PROJECT_URL", "projects/".get_option('firebase_projectid')."/databases/(default)/documents/");

add_action( 'wp_ajax_update_post_types_to_sync', 'update_post_types_to_sync' );

function update_post_types_to_sync() {
  global $wpdb; // this is how you get access to the database
  
  //2.DEtermine if this post types exists
  $exists = false;
  $key_type_to_edit = '';
  $value_type_to_edit = '';

  //What we have clicked
  $clicked= $_POST['clicked'];

  //1.Get Options for post-type - what has been ckicked so far
  $selected_post_types = get_option('post_types_array');

  //3 Check if existis
  $exists=in_array($clicked, $selected_post_types);

  //4 Do the saving
  if(!is_array($selected_post_types)){
    $selected_post_types=[];
  }

  if($exists){
    //Remove it
    $tempArray=[];
    foreach ($selected_post_types as $key => $value) {
      if($value.""!=$clicked.""){
        array_push($tempArray, $value);
      }
    }
    $selected_post_types=$tempArray;
  }else{
    //Add it
    array_push($selected_post_types, $clicked);
  }
  update_option('post_types_array', $selected_post_types);

  print_r($selected_post_types);
  
	wp_die(); // this is required to terminate immediately and return a proper response
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

  return $postData;
}

function getPostCategory($post_id){
  global $wpdb;

  $query = "SELECT * FROM (SELECT * FROM(SELECT meta_id,post_id FROM {$wpdb->prefix}postmeta WHERE post_id=".$post_id.") a
            INNER JOIN {$wpdb->prefix}term_relationships ON {$wpdb->prefix}term_relationships.object_id = a.post_id) b
            INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_taxonomy.term_taxonomy_id = b.term_taxonomy_id GROUP BY {$wpdb->prefix}term_taxonomy.taxonomy";

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
    sendDataToFirestore(wpDataToFirestoreData($element),false,$element->taxonomy,$element->term_id,"publish", true);
  }
}

/**
 * @param {Array} data - Array Representation of the POST
 * @param {Boolean} shouldIDoAConversion
 * @param {String} action_type - fetched if publish or update in firestore
 */
function sendDataToFirestore($postData, $shouldIDoAConversion=true, $type, $id, $action_type, $isCategory){
  
  //$postMeta=get_post_meta($data['ID']);
  if($shouldIDoAConversion){
    $type=$postData['post_type'];
    $id=$postData['ID'];
    $postData=wpDataToFirestoreData($postData);
  }

  //if it's post get post status and post category
  if(!$isCategory){
    //get post category
    $postStatus = $postData["fields"]["post_status"]['stringValue'];

    $postCategory = getPostCategory($postData["fields"]["ID"]['stringValue']);
    if(!empty($postCategory)){
      if(count($postCategory) > 1){
        foreach($postCategory as $key => $obj){
          //debug_func($obj, $obj->meta_id);
          //$postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$obj->taxonomy."/".$obj->term_id);
          $postData['fields']['collection_'.$obj->taxonomy]=array("referenceValue"=>FIRESTORE_PROJECT_URL.$obj->taxonomy."/".$obj->term_id);
        }
      }else{
        //collection category reference
        //$postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);
        $postData['fields']['collection_'.$postCategory[0]->taxonomy]=array("referenceValue"=>FIRESTORE_PROJECT_URL.$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);
      }
    }
    /*if(!empty($postCategory)){
      //collection category reference 
      $postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);
    }*/
    
  }
  
  //if publish post
  if($action_type == "publish"){
    $url = FIRESTORE_URL.$type."?documentId=".$id;
  
    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => json_encode($postData),
      'method'      => 'POST',
      'data_format' => 'body',
    ));
  //if update post && post status != trash
  }else if($action_type == "update" && $postStatus != "trash"){
    $url = FIRESTORE_URL.$type."/".$id;
  
    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => json_encode($postData),
      'method'      => 'PATCH',
      'data_format' => 'body',
    ));
  //if update post action && post status == trash
  }else if(($action_type == "update" && $postStatus == "trash")){
    $url = FIRESTORE_URL.$type."/".$id;

    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'method'      => 'DELETE'
    ));
  //works on delete category
  }else if(($action_type == "delete")){
    $url = FIRESTORE_URL.$type."/".$id;

    wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'method'      => 'DELETE'
    ));
  }
}


/**
 * Synchronize post on publish new post
 * @param {Integer} post_id
 * @param {Object} post
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
 * @param {Integer} post_id
 * @param {Object} post
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

/**
 * Getting the meta additional information for added category and if there is data update to firestore
 * @param {Integer} term_id 
 * @param {Object} element 
 */
function checkMetaCategoryData($term_id, $element){
    global $wpdb;
    $query = "SELECT * FROM 
              (SELECT categories_meta.term_id, categories_meta.name, categories_meta.taxonomy, categories_meta.meta_key, categories_meta.meta_value, {$wpdb->prefix}posts.guid FROM 
              (SELECT categories.term_id, categories.name, categories.taxonomy, {$wpdb->prefix}termmeta.meta_key, {$wpdb->prefix}termmeta.meta_value FROM
              (SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy FROM {$wpdb->prefix}terms 
              INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id) categories
              LEFT JOIN {$wpdb->prefix}termmeta ON categories.term_id={$wpdb->prefix}termmeta.term_id) categories_meta
              LEFT JOIN {$wpdb->prefix}posts ON categories_meta.meta_value={$wpdb->prefix}posts.ID
              ORDER BY term_id) a WHERE term_id='".$term_id."'";

    $meta_categories = $wpdb->get_results($query);
    $changes_in_element = false;
    
    $new_object_to_be_created = (object) [];

    foreach($element as $key => $value){
      $new_object_to_be_created->$key = $value; 
    }
    
    foreach ($meta_categories as $key => $category_meta) {
      if(($category_meta->meta_key != null) && ($category_meta->meta_value != null)){
        $meta_key = $category_meta->meta_key; 
        if($category_meta->guid != null){
          $new_object_to_be_created->$meta_key = $category_meta->guid;
          $changes_in_element = true;
        }else{
          $new_object_to_be_created->$meta_key = $category_meta->meta_value;
          $changes_in_element = true;
        }
      }
    }

    if($changes_in_element){
      sendDataToFirestore(wpDataToFirestoreData($new_object_to_be_created),false,$element->taxonomy,$term_id,"update",true);
    }  
}
//ON NEW CATEGORY CREATE 
function action_create_category($term_id, $taxonomy_term_id){
  global $wpdb;

  $query = "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}term_taxonomy.taxonomy FROM {$wpdb->prefix}terms INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_id AND {$wpdb->prefix}terms.term_id='".$term_id."'";

  $element = $wpdb->get_results($query);

  //save do database nessecary info
  sendDataToFirestore(wpDataToFirestoreData($element[0]),false,$element[0]->taxonomy,$term_id,"publish",true);
  
  //check if there is any other meta data and update it the added category
  checkMetaCategoryData($term_id,$element[0]);
}

//ON CATEGORY DELETE 
function action_delete_category($term_id, $taxonomy_term_id, $deleted_term){
  sendDataToFirestore(wpDataToFirestoreData($deleted_term),false,$deleted_term->taxonomy,$term_id,"delete",true);
}

//ON POST DELETE
function action_delete_post($post_id){
  $post = get_post($post_id);
  sendDataToFirestore((array) $post, true, $post->post_type, $post_id, "delete", false);
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

      //on delete post permanently
      add_action( 'delete_'.$type, 'action_delete_post', 10, 2); 
    }
  //check if postTypes is string -> only one postTypes
  }else{
     //on post publish
     add_action('publish_'.$postTypes, 'action_publish_post', 10, 2);
     //add_action('update_'.$type, 'action_update_post', 10, 2);
 
     //on post update
     add_action('save_'.$postTypes, 'action_update_post', 10, 3);

     //on delete post permanently
     add_action( 'delete_'.$type, 'action_delete_post', 10, 2);
  } 
}

//Checking all selected post types
if(get_option('post_types_array')){
  subscribeToDifferentPostTypes(get_option('post_types_array'));
}

//on create category
add_action('create_category', 'action_create_category', 10, 2);

//on category delete
add_action('delete_category','action_delete_category',10, 3);

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
