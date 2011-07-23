<?php

if(empty($_GET['name']))
	exit;
$file = "files/".$_GET['name'];

set_include_path(get_include_path() . PATH_SEPARATOR . '/var/framework/');
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

$serviceName = Zend_Gdata_YouTube::AUTH_SERVICE_NAME;
$user = "sng.alvin@gmail.com";
$pass = "axce30fo";

$httpClient = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $serviceName);

$yt = new Zend_Gdata_YouTube($httpClient);

// create a new VideoEntry object
$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();

// create a new Zend_Gdata_App_MediaFileSource object
$filesource = $yt->newMediaFileSource($file);
$filesource->setContentType(mime_content_type($file));
// set slug header
$filesource->setSlug($_GET['name']);

// add the filesource to the video entry
$myVideoEntry->setMediaSource($filesource);

$myVideoEntry->setVideoTitle($_GET['name']);
$myVideoEntry->setVideoDescription($_GET['name']);
// The category must be a valid YouTube category!
$myVideoEntry->setVideoCategory('Autos');

// Set keywords. Please note that this must be a comma-separated string
// and that individual keywords cannot contain whitespace
$myVideoEntry->SetVideoTags('cars, funny');

// set some developer tags -- this is optional
// (see Searching by Developer Tags for more details)
$myVideoEntry->setVideoDeveloperTags(array('mydevtag', 'anotherdevtag'));

// set the video's location -- this is also optional
$yt->registerPackage('Zend_Gdata_Geo');
$yt->registerPackage('Zend_Gdata_Geo_Extension');
$where = $yt->newGeoRssWhere();
$position = $yt->newGmlPos('37.0 -122.0');
$where->point = $yt->newGmlPoint($position);
$myVideoEntry->setWhere($where);

// upload URI for the currently authenticated user
$uploadUrl = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads?key=AI39si5x525mzrjata3CfWdAd7okp7k9UsVfEiz8hF-x6HBiQOIbhT82c7LvGp9i7Iz0puzAq6DcNwqOrJd-bltwxxK_q8Y9Gw';

// try to upload the video, catching a Zend_Gdata_App_HttpException, 
// if available, or just a regular Zend_Gdata_App_Exception otherwise
try {
  $newEntry = $yt->insertEntry($myVideoEntry, $uploadUrl, 'Zend_Gdata_YouTube_VideoEntry');
} catch (Zend_Gdata_App_HttpException $httpException) {
  echo $httpException->getRawResponseBody();
} catch (Zend_Gdata_App_Exception $e) {
    echo $e->getMessage();
}

$yt = new Zend_Gdata_YouTube();
$videoFeed = $yt->getUserUploads('askalvinn');
$youtube_id = null;
foreach($videoFeed as $videoEntry) {
	$title = $videoEntry->getVideoTitle();
	if($title == $_GET['name']){
		$youtube_id = $videoEntry->getVideoId();
		break;
	}
}

require_once('inc/connect.php');
$sql_name = mysql_escape_string($_GET['name']);
$sql = "UPDATE files SET youtube_id='$youtube_id' WHERE name='$sql_name' AND type='video' ORDER BY id DESC LIMIT 1";
echo $sql;
mysql_query($sql);