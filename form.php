<!DOCTYPE HTML>  
<html>
<head>
<style>
  #feedback { font-size: 1.4em; }
  #selectable .ui-selecting { background: #FECA40; }
  #selectable .ui-selected { background: #F39814; color: white; }
  #selectable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
  #selectable li { margin: 3px; padding: 0.4em; font-size: 1.0em; height: 18px; display: inline-block; border: 1px solid #32373c; border-radius: 3px; padding-bottom: 5px; cursor: pointer;}
  </style>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
  $( function() {
      //remove ui-selected class after page reload and then add again.  
      //$("li").removeClass("ui-selected");
      var currentPostTypes = JSON.parse('<?php echo json_encode(get_option('post_types_array')); ?>');
    
      var postTypesArray = [];
      if(currentPostTypes.length){ 
        if(Array.isArray(currentPostTypes) && currentPostTypes.length){
          currentPostTypes.forEach(function(postType){
            //add current selected items to array that will be saved in session
            postTypesArray.push(postType);

            //add class selected
            $("li[name*="+postType+"]").addClass("ui-selected");
          })
        }else{
          postTypesArray.push(currentPostTypes);
          $("li[name*="+currentPostTypes+"]").addClass("ui-selected");
        }
      }

      $(".ui-widget-content").click( function() {
        $(this).toggleClass("ui-selected");
        $.ajax({
            type: "POST",
            url: "admin-ajax.php",
            data: {
              'action': "update_post_types_to_sync",
              'clicked': $(this).attr("name")
              }
          });
      })



      /*$(".ui-widget-content_old").click( function() {
        //if is selected remove the class and removed it from selected array
        if($(this).hasClass("ui-selected")){
          $(this).removeClass("ui-selected");

          //index to remove from array
          //var index = $("#selectable li").index(this);
          postTypesArray.splice( postTypesArray.indexOf($(this).attr("name")), 1 );
        }else{
          $(this).toggleClass("ui-selected");
          postTypesArray.push($(this).attr("name"))
        }       
      });*/
  });
  </script>
</head>
<body>  

<?php
  $arrayTypes = array();
  $types = get_post_types( [], 'objects' );
    foreach ( $types as $type ) {
      if ( isset( $type->name ) ) {
        // you'll probably want to do something else.
        // debug_func($type->name,"data".$type->name);
        array_push($arrayTypes, $type->name);
    }
  }
  
  $actionStatus = 0;
  $actionCategoriesSyncStatus = 0;
  $post_types = get_option('post_types_array');
  /*if(empty(get_option('post_types_array'))){
    add_option('post_types_array', "post");
  }else{
    if(is_array(get_option('post_types_array'))){
      $post_types = implode(",",get_option('post_types_array'));
    }else{
      $post_types = get_option('post_types_array');
    }
  }*/

  /* Echo variable
 * Description: Uses <pre> and print_r to display a variable in formated fashion
 */
function echo_log( $what )
{
    echo '<pre>'.print_r( $what, true ).'</pre>';
}
  /*function getPostsByType($post_type){
    if(is_array($post_type)){
      //do sth
    }else{
      $args = array(  
        'post_type' => $post_type
      );
    }
  }*/
  function debug_funcc($data,$file="debug"){
    $myfile = fopen(__DIR__ .'/debug/'.$file.'.txt', 'w');
    //fwrite($myfile, json_encode( (array)$data ));
    fwrite($myfile, json_encode( $data ));
    fclose($myfile);
  }

  function getPostCategory2($post_id){
    global $wpdb;
  
    $query = "SELECT * FROM (SELECT * FROM(SELECT meta_id,post_id FROM wp_postmeta WHERE post_id=".$post_id.") a
              INNER JOIN wp_term_relationships ON wp_term_relationships.object_id = a.post_id) b
              INNER JOIN wp_term_taxonomy ON wp_term_taxonomy.term_taxonomy_id = b.term_taxonomy_id LIMIT 1";
  
    $category = $wpdb->get_results($query);
    
    return $category;
  }

  function wpDataToFirestoreData2($data){
    $postData = array(  
      'fields' => array(),
    );
  
    foreach ($data as $key => $value){  
      $postData['fields'][$key]=array("stringValue"=>$value.""); 
     }

     //$postCategory = getPostCategory2($data['ID']);
     //collection category reference
     //$postData['fields']['collection']=array("referenceValue"=>"projects/mytestexample-d5aaa/databases/(default)/documents/".$postCategory[0]->taxonomy."/".$postCategory[0]->term_id);
     
    return $postData;
  }
  
  function sendDataToFirestore2($postData, $shouldIDoAConversion=true, $type, $id, $action_type, $isCategory){
  
    //$postMeta=get_post_meta($data['ID']);
    if($shouldIDoAConversion){
      $type=$postData['post_type'];
      $id=$postData['ID'];
      $postData=wpDataToFirestoreData2($postData);
    }

    if(!$isCategory){
      $postCategory = getPostCategory2($postData["fields"]["ID"]['stringValue']);
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

  function saveCategories2(){
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
      sendDataToFirestore2(wpDataToFirestoreData2($element),false,$element->taxonomy,$element->term_id,"publish", true);
    }
  }

  function getAllPostsByPostType($post_type){
    global $wpdb;

    $query = "SELECT * FROM {$wpdb->prefix}posts WHERE {$wpdb->prefix}posts.post_type='".$post_type."'";
    return $wpdb->get_results($query);
  }

  //Handle Cat sync
  if(isset($_GET['dofullcatsync'])){
    saveCategories2();
    $actionCategoriesSyncStatus = 1;
  }

  //Handle full post sync
  if(isset($_GET['dofullpostsync'])){
    if(is_array($post_types)){
      foreach ($post_types as $key => $type) {

        $posts = getAllPostsByPostType($type);
        
        foreach ($posts as $post_key => $post){
          sendDataToFirestore2((array) $post, true, $post->post_type, $post->ID, "publish", false);
        }
      }
    //check if postTypes is string -> only one postTypes
    }else{
      $posts = getAllPostsByPostType($post_types);
      foreach ($posts as $post_key => $post){
        sendDataToFirestore2((array) $post, true, $post->post_type, $post->ID, "publish", false);
      }
    }
    $actionPostSyncStatus=1;
  }

  //HANDLE POST REQUESTS
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(!(empty($_POST["apikey"]) || empty($_POST["projectid"]) || empty($_POST["appid"]))){
        
      update_option('firebase_apikey', $_POST["apikey"]);
      update_option('firebase_projectid', $_POST["projectid"]);
      update_option('firebase_appid', $_POST["appid"]);
      update_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
      update_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");
      
      /*if(get_option('post_types_array') || $_COOKIE["postTypesArray"]){
        if(count(explode(",",$_COOKIE["postTypesArray"])) > 1){
          update_option('post_types_array', explode(",",$_COOKIE["postTypesArray"]));
          //$post_types = implode(",",get_option('post_types_array'));
        }else{
          update_option('post_types_array',$_COOKIE["postTypesArray"]);
          //$post_types = get_option('post_types_array');
        }
      }*/
      
    }else{
      add_option('firebase_apikey', $_POST["apikey"]);
      add_option('firebase_projectid', $_POST["projectid"]);
      add_option('firebase_appid', $_POST["appid"]);
      add_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
      add_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");

      /*if(get_option('post_types_array') || $_COOKIE["postTypesArray"]){
        if(count(explode(",",$_COOKIE["postTypesArray"])) > 1){
          update_option('post_types_array', explode(",",$_COOKIE["postTypesArray"]));
          //$post_types = implode(",",get_option('post_types_array'));
        }else{
          update_option('post_types_array',$_COOKIE["postTypesArray"]);
          //$post_types = get_option('post_types_array');
        }
      }*/
    }
    $actionStatus=1;
    //header("Refresh:0");
    //header("Location: ".$_SERVER['PHP_SELF']);
    echo("<meta http-equiv='refresh' content='1'>");
  }
?>
<div class="wrap">
  <h1>UniExpo Plugin</h1>
  <?php if($actionStatus==1){ ?>
    <div class="notice notice-success settings-error is-dismissible alert-saved">
    <p>Project Settings Saved!</p>
  </div>
  <?php } ?>
  <?php if($actionCategoriesSyncStatus==1){ ?>
    <div class="notice notice-success settings-error is-dismissible alert-saved">
    <p>Categories Synchronized successfully!</p>
  </div>
  <?php } ?>
  <?php if($actionPostSyncStatus==1){ ?>
    <div class="notice notice-success settings-error is-dismissible alert-saved">
    <p>Post Types Synchronized successfully!</p>
  </div>
  <?php } ?>
  <br/>
  <h2>Firebase Project Settings</h2>
  <hr/>
  <form method="post" action="admin.php?page=uniexpo-plugin" novalidate="novalidate">
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row">
          <label for="blogname">apiKey</label>
        </th>
        <td>
          <input name="apikey" type="text" id="apikey" value="<?php echo get_option('firebase_apikey');?>" class="regular-text" />
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="blogdescription">projectId</label>
        </th>
        <td>
        <input name="projectid" type="text" id="projectid" value="<?php echo get_option('firebase_projectid');?>" class="regular-text" />
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="blogdescription">appId</label>
        </th>
        <td>
        <input name="appid" type="text" id="appid" value="<?php echo get_option('firebase_appid');?>" class="regular-text" />
        </td>
      </tr>
    </table> 
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  /></p>
    <br/>
    <?php if(get_option('firebase_projectid')): ?>
    <h2>Sync Settings</h2>
    <hr/>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row">
          <label for="blogname">Post types to sync</label>
        </th>
        <td>
          <ol id="selectable">
            <!--<li class="ui-widget-content">Item 1</li>-->
            <?php if(is_array($arrayTypes)): ?>
              <?php foreach($arrayTypes as $post_type): ?>
                <li id="ui-widget" class="ui-widget-content" name=<?php echo $post_type ?>><?php echo $post_type ?></li>
              <?php endforeach; ?>
            <?php else : ?>
              <!--<li class="ui-widget-content"> execute sth here</li>-->
            <?php endif; ?>
          </ol>
          <p class="description" id="tagline-description">Select one of the post types below.</p>
        </td>
      </tr>
    </table>
    </form>
    <?php if(!empty(get_option('post_types_array'))): ?>
      <h2>Initial Full sync</h2>
      <hr/>
    <table class="form-table" role="presentation">
      <tr>
        <td>
          <a href="admin.php?page=uniexpo-plugin&dofullcatsync=true" class="button button-primary" value="Categories">Categories</a>
            &nbsp;&nbsp;  
            <a href="admin.php?page=uniexpo-plugin&dofullpostsync=true" class="button button-primary" value="Post Types">Post Types</a>
        </td>
      </tr>
    </table>
    <?php endif; ?>
    <br/>
    <?php endif; ?>

    
  
</div>
</body>
</html>