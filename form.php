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
      var currentPostTypes = '<?php echo get_option('post_types_array'); ?>';
      if(Array.isArray(currentPostTypes)){
        currentPostTypes.forEach(function(postType){
          $("li[name*="+postType+"]").addClass("ui-selected");
        })
      }else{
        $("li[name*="+currentPostTypes+"]").addClass("ui-selected");
      }

      var postTypesArray = [];
      $(".ui-widget-content").click( function() {
        $(this).toggleClass("ui-selected");
        postTypesArray.push($(this).attr("name"))
        //localStorage.setItem('postTypesArray', postTypesArray);
        document.cookie = "postTypesArray="+postTypesArray;
      });
  } );
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
  
  $actionStatus=0;
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

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /*if(empty($_POST["post_types"])){
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
    }*/
      if(!(empty($_POST["apikey"]) || empty($_POST["projectid"]) || empty($_POST["appid"]))){
      
        update_option('firebase_apikey', $_POST["apikey"]);
        update_option('firebase_projectid', $_POST["projectid"]);
        update_option('firebase_appid', $_POST["appid"]);
        update_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
        update_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");

        update_option('post_types_array', $_COOKIE["postTypesArray"]);
      }else{
        add_option('firebase_apikey', $_POST["apikey"]);
        add_option('firebase_projectid', $_POST["projectid"]);
        add_option('firebase_appid', $_POST["appid"]);
        add_option('firebase_authdomain', $_POST["projectid"] . ".firebaseapp.com");
        add_option('firebase_databaseurl', "https://" . $_POST["projectid"] . ".firebaseio.com");

        if(count(explode(",",$_COOKIE["postTypesArray"])) > 1){
          update_option('post_types_array', explode(",",$_COOKIE["postTypesArray"]));
          //$post_types = implode(",",get_option('post_types_array'));
        }else{
          update_option('post_types_array',$_COOKIE["postTypesArray"]);
          $post_types = get_option('post_types_array');
        }
      }
    

    $actionStatus=1;
    //sendDataToFirestore(array("name"=>"My Post name","id"=>13,"author"=>"Daniel","post_type"=>"post"));
    
    //print_r(get_post("1"));

    //getPostsByType(get_option('post_types_array'));
    //echo "<script>document.write(localStorage.getItem('postTypesArray'));</script>";
    /*if( isset($_COOKIE["postTypesArray"])){
      debug_funcc($_COOKIE["postTypesArray"],"debug");
    }*/
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
          <ol id="selectable">
            <!--<li class="ui-widget-content">Item 1</li>-->
            <?php if(is_array($arrayTypes)): ?>
              <?php foreach($arrayTypes as $post_type): ?>
                <li class="ui-widget-content" name=<?php echo $post_type ?>><?php echo $post_type ?></li>
              <?php endforeach; ?>
            <?php else : ?>
              <!--<li class="ui-widget-content"> execute sth here</li>-->
            <?php endif; ?>
          </ol>
          <p class="description" id="tagline-description">Select one of the post types below.</p>
        </td>
      </tr>
    </table> 
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  /></p>
  </form>
</div>
</body>
</html>