<!DOCTYPE HTML>  
<html>
<head>
<style>
.error {color: #FF0000;}
</style>
</head>
<body>  

<?php
// define variables and set to empty values
$err = $apikey = $authdomain = $databaseurl = $projectid = $storagebucket = $senderid = $appid = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST["apikey"]) || empty($_POST["authdomain"]) || empty($_POST["databaseurl"]) || empty($_POST["projectid"]) || 
      empty($_POST["storagebucket"]) || empty($_POST["senderid"]) || empty($_POST["appid"])) {

    $err = "Please enter all required fields";
  } else {
    $apikey = $_POST["apikey"];
    $authdomain = $_POST["authdomain"];
    $databaseurl = $_POST["projectid"];
    $projectid = $_POST["projectid"];
    $storagebucket = $_POST["storagebucket"];
    $senderid = $_POST["senderid"];
    $appid = $_POST["appid"];

    add_option('firebase_apikey', $apikey);
    add_option('firebase_authdomain', $authdomain);
    add_option('firebase_databaseurl', $databaseurl);
    add_option('firebase_projectid', $projectid);
    add_option('firebase_storagebucket', $storagebucket);
    add_option('firebase_senderid', $senderid);
    add_option('firebase_appid', $appid);
  }
}

?>
<h1>UniExpo Plugin</h1>
<br/>
<h2>Firebase Project Settings</h2>
<hr/>
<p><span class="error">* Required fields</span></p>
<form method="post" action="admin.php?page=uniexpo-plugin">  
  apiKey:<br/><input type="text" name="apikey" value="<?php echo get_option('firebase_apikey');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  authDomain:<br/><input type="text" name="authdomain" value="<?php echo get_option('firebase_authdomain');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  databaseURL:<br/><input type="text" name="databaseurl" value="<?php echo get_option('firebase_databaseurl');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  projectId:<br/><input type="text" name="projectid" value="<?php echo get_option('firebase_projectid');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  storageBucket:<br/><input type="text" name="storagebucket" value="<?php echo get_option('firebase_storagebucket');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  messagingSenderId:<br/><input type="text" name="senderid" value="<?php echo get_option('firebase_senderid');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br>
  appId:<br/><input type="text" name="appid" value="<?php echo get_option('firebase_appid');?>">
  <span class="error">* <?php echo $err;?></span>
  <br><br><br>
  <?php submit_button(); ?>
</form>

</body>
</html>