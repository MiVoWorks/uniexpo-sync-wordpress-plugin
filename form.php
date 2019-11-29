<!DOCTYPE HTML>  
<html>
<head>
</head>
<body>  

<?php
  $actionStatus=0;
  $post_types = get_option('post_types_array');
  if(empty(get_option('post_types_array'))){
    add_option('post_types_array', "post");
  }else{
    if(is_array(get_option('post_types_array'))){
      $post_types = implode(",",get_option('post_types_array'));
    }else{
      $post_types = get_option('post_types_array');
    }
  }

  /* Echo variable
 * Description: Uses <pre> and print_r to display a variable in formated fashion
 */
function echo_log( $what )
{
    echo '<pre>'.print_r( $what, true ).'</pre>';
}


function wpDataToFirestoreData($data){
  $postData = array(  
    'fields' => array(),
    
  );

  foreach ($data as $key => $value){  
    $postData['fields'][$key]=array("stringValue"=>$value.""); 
   } 

return $postData; //TODO MODIFIED



}

  function sendDataToFirestore($data){
    $postData=wpDataToFirestoreData($data);
    
    $url = "https://firestore.googleapis.com/v1/projects/".get_option('firebase_projectid')."/databases/(default)/documents/".$data['post_type']."?documentId=".$data['id'];
    echo_log($url);

    $response = wp_remote_post($url, array(
      'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'        => json_encode($postData),
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

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty($_POST["post_types"])){
      $post_types = get_option('post_types_array');
    }else{
      if(!(empty($_POST["apikey"]) || empty($_POST["projectid"]) || empty($_POST["appid"]))){
      
        update_option('firebase_apikey', $_POST["apikey"]);
        update_option('firebase_projectid', $_POST["projectid"]);
        update_option('firebase_appid', $_POST["appid"]);
        update_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
        update_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");

        if(count(explode(",",$_POST["post_types"])) > 1){
          update_option('post_types_array', explode(",",$_POST["post_types"]));
          $post_types = implode(",",get_option('post_types_array'));
        }else{
          update_option('post_types_array', $_POST["post_types"]);
          $post_types = get_option('post_types_array');
        }
      }else{
        add_option('firebase_apikey', $_POST["apikey"]);
        add_option('firebase_projectid', $_POST["projectid"]);
        add_option('firebase_appid', $_POST["appid"]);
        add_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
        add_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");

        if(count(explode(",",$_POST["post_types"])) > 1){
          update_option('post_types_array', explode(",",$_POST["post_types"]));
          $post_types = implode(",",get_option('post_types_array'));
        }else{
          update_option('post_types_array', $_POST["post_types"]);
          $post_types = get_option('post_types_array');
        }
      }
    }
    $actionStatus=1;
    //sendDataToFirestore(array("name"=>"My Post name","id"=>13,"author"=>"Daniel","post_type"=>"post"));
    
    //print_r(get_post("1"));

    //getPostsByType(get_option('post_types_array'));
    

  }
?>
<div class="wrap">
  <h1>UniExpo Plugin</h1>
  <?php if($actionStatus==1){ ?>
    <div class="notice notice-success settings-error is-dismissible alert-saved">
    <p>Project Settings Saved!</p>
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
    <br/>
    <h2>Sync Settings</h2>
    <hr/>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row">
          <label for="blogname">Post types to sync</label>
        </th>
        <td>
          <input name="post_types" type="text" id="post_types" value="<?php echo $post_types?>" class="regular-text" />
          <p class="description" id="tagline-description">Example (post,page)</p>
        </td>
      </tr>
    </table> 
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  /></p>
  </form>
</div>
</body>
</html>