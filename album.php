<?php
/**
 *
 * @author    Abhinaya Ramachandran
 * @copyright  Abhinaya Ramachandran 2017
 *
 */

require_once 'demo-lib.php';
demo_init(); 

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit( 0 );

require_once 'DropboxClient.php';

$dropbox = new DropboxClient( array(
	'app_key' => "",      // Put your Dropbox API key here
	'app_secret' => "",   // Put your Dropbox API secret here
	'app_full_access' => false,
) );


/**
 * Dropbox will redirect the user here
 * @var string $return_url
 */
$return_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";

// first, try to load existing access token
$bearer_token = demo_token_load( "bearer" );

if ( $bearer_token ) {
	$dropbox->SetBearerToken( $bearer_token );
	//echo "loaded bearer token: " . json_encode( $bearer_token, JSON_PRETTY_PRINT ) . "\n";
} elseif ( ! empty( $_GET['auth_redirect'] ) ) // are we coming from dropbox's auth page?
{
	// get & store bearer token
	$bearer_token = $dropbox->GetBearerToken( null, $return_url );
	demo_store_token( $bearer_token, "bearer" );
} elseif ( ! $dropbox->IsAuthorized() ) {
	// redirect user to Dropbox auth page
	$auth_url = $dropbox->BuildAuthorizeUrl( $return_url );
	die( "Authentication required. <a href='$auth_url'>Continue.</a>" );
}
?>
<html>
<head>
<title>DropBox utility</title>
</head>
<body>
<hr/>
<center><h1>Dropbox Utility</h1></center>
<hr/>
<form enctype="multipart/form-data" action="album.php" method="POST">
<input type="hidden" name="MAX FILE SIZE" value="3000000" />
<h2>Upload an image here</h2>
<fieldset>
<input name="userfile" type="file" /><br/>
<legend> <h3>Submit this file:</h3> </legend>
<input type="submit" value="Send File"  name="upload"/>
</fieldset>
</form>


<?php

if ($_SERVER['REQUEST_METHOD'] == "POST" && (isset($_POST['upload']))) {
	      $file_name = $_FILES['userfile']['name'];
	      $file_size =$_FILES['userfile']['size'];
	      $file_tmp =$_FILES['userfile']['tmp_name'];
	      $file_type=$_FILES['userfile']['type'];
      	      move_uploaded_file($file_tmp,$file_name);
      	      $dropbox->UploadFile($file_name);

}

if($_SERVER['REQUEST_METHOD'] == "GET" && (isset($_GET['path']))){
	$file_path = $_GET['path'];
	$dropbox->Delete($file_path);
}



$files = $dropbox->GetFiles( "", false );
if (! empty($files) ){
	echo "<h2>Files available </h2>";
	echo "<table border = '1' cellpadding = '10'>";
	echo "<th>Filename</th> <th colspan = '2'>Actions</th> ";
	foreach($files as $file){
	echo "<tr>";
	echo "<td>";
	echo $file->name;
	echo "</td>";
	echo "<td>";
	echo "<a  href ='album.php?download=". $file->path."'>Download & Display</a>\n";
	echo "</td>";
	echo "<td>";
	echo "<form action='album.php' method='GET'><input type='hidden' value='".$file->path."' name='path'/><input type='submit' value='Delete'></input></form>";
	echo "</td>";
	
	echo "</tr>";
}
	echo "</table>";
}

if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['download'])){
	$dir ="downloads/". $_GET['download'];
	$dropbox->DownloadFile($_GET['download'], $dir);
	$location = $dropbox->GetLink($_GET['download']);
	$img_data = base64_encode( $dropbox->GetThumbnail( $_GET['download'] ,"xl") );
	echo "<br/>";
	echo "<img src=\"data:image/jpeg;base64,$img_data\" alt=\"Generating image failed!\" style=\"border: 2px solid black;  width:500px; height:500px\" />";
	 

}

?>

</body>
</html>