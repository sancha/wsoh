<?php
/*
 * jQuery File Upload Plugin PHP Example 5.2.2
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://creativecommons.org/licenses/MIT/
 */

error_reporting(E_ERROR);

class UploadHandler{
    private $options;
	private $doc_types;
	private $video_types;
    
    function __construct($options=null) {
		$this->doc_types = array('pdf','txt','ps','rtf','epub','odt','odp','ods','odg','odf','sxw','sxc','sxi','sxd','doc','ppt','pps','xls','docx','pptx','ppsx','xlsx','tif','tiff');
        $this->video_types = array('avi','mpg','wmv','mov','flv','mp4','f4v','mpeg','web','divx','ogg');
		
		$this->options = array(
            'script_url' => $_SERVER['PHP_SELF'],
            'upload_dir' => dirname(__FILE__).'/files/',
            'upload_url' => dirname($_SERVER['PHP_SELF']).'/files/',
            'param_name' => 'files',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+$/i',
            'max_number_of_files' => null,
            'discard_aborted_uploads' => true,
            'image_versions' => array(
                // Uncomment the following version to restrict the size of
                // uploaded images. You can also add additional versions with
                // their own upload directories:
                /*
                'large' => array(
                    'upload_dir' => dirname(__FILE__).'/files/',
                    'upload_url' => dirname($_SERVER['PHP_SELF']).'/files/',
                    'max_width' => 1920,
                    'max_height' => 1200
                ),
                */
                'thumbnail' => array(
                    'upload_dir' => dirname(__FILE__).'/thumbnails/',
                    'upload_url' => dirname($_SERVER['PHP_SELF']).'/thumbnails/',
                    'max_width' => 80,
                    'max_height' => 80
                )
            )
        );
        if ($options) {
            $this->options = array_merge_recursive($this->options, $options);
        }
    }
    
    private function get_file_object($file_name) {
        $file_path = $this->options['upload_dir'].$file_name;
        if (is_file($file_path) && $file_name[0] !== '.') {
            $file = new stdClass();
            $file->name = $file_name;
            $file->size = filesize($file_path);
            $file->url = $this->options['upload_url'].rawurlencode($file->name);
            foreach($this->options['image_versions'] as $version => $options) {
                if (is_file($options['upload_dir'].$file_name)) {
                    $file->{$version.'_url'} = $options['upload_url']
                        .rawurlencode($file->name);
                }
            }
			
			$file->exten = end(explode(".",$file_name));
			$file->doc_url = null;
			$file->video_url = null;
			
			if(in_array($file->exten, $this->doc_types)||in_array($file->exten, $this->video_types)){
				require_once('inc/connect.php');
				$sql_name = mysql_escape_string($file->name);
				$result = mysql_query("SELECT * FROM files WHERE name='$sql_name' ORDER BY id DESC LIMIT 1");
				while($row = mysql_fetch_array($result)){
					if($row['type'] == "doc")
						$file->doc_url = $row['id'];
					elseif($row['type'] == "video")
						$file->video_url = $row['youtube_id'];
				}
			}
			
            $file->delete_url = $this->options['script_url']
                .'?file='.rawurlencode($file->name);
            $file->delete_type = 'DELETE';
            return $file;
        }
        return null;
    }
    
    private function get_file_objects() {
        return array_values(array_filter(array_map(
            array($this, 'get_file_object'),
            scandir($this->options['upload_dir'])
        )));
    }

    private function create_scaled_image($file_name, $options) {
        $file_path = $this->options['upload_dir'].$file_name;
        $new_file_path = $options['upload_dir'].$file_name;
        list($img_width, $img_height) = @getimagesize($file_path);
        if (!$img_width || !$img_height) {
            return false;
        }
        $scale = min(
            $options['max_width'] / $img_width,
            $options['max_height'] / $img_height
        );
        if ($scale > 1) {
            $scale = 1;
        }
        $new_width = $img_width * $scale;
        $new_height = $img_height * $scale;
        $new_img = @imagecreatetruecolor($new_width, $new_height);
        switch (strtolower(substr(strrchr($file_name, '.'), 1))) {
            case 'jpg':
            case 'jpeg':
                $src_img = @imagecreatefromjpeg($file_path);
                $write_image = 'imagejpeg';
                break;
            case 'gif':
                $src_img = @imagecreatefromgif($file_path);
                $write_image = 'imagegif';
                break;
            case 'png':
                $src_img = @imagecreatefrompng($file_path);
                $write_image = 'imagepng';
                break;
            default:
                $src_img = $image_method = null;
        }
        $success = $src_img && @imagecopyresampled(
            $new_img,
            $src_img,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        ) && $write_image($new_img, $new_file_path);
        // Free up memory (imagedestroy does not delete files):
        @imagedestroy($src_img);
        @imagedestroy($new_img);
        return $success;
    }
    
    private function has_error($uploaded_file, $file, $error) {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            return 'maxFileSize';
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        }
        if (is_int($this->options['max_number_of_files']) && (
                count($this->get_file_objects()) >= $this->options['max_number_of_files'])
            ) {
            return 'maxNumberOfFiles';
        }
        return $error;
    }
    private function stripName($name){
		return ereg_replace("[^A-Za-z0-9.]", "", $name );
	}
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error) {
        $file = new stdClass();
        $file->name = basename(stripslashes($this->stripName($name)));
        $file->size = intval($size);
        $file->type = $type;
		
        $error = $this->has_error($uploaded_file, $file, $error);
        if (!$error && $file->name) {
            if ($file->name[0] === '.') {
                $file->name = substr($file->name, 1);
            }
            $file_path = $this->options['upload_dir'].$file->name;
            $append_file = is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = filesize($file_path);
            if ($file_size === $file->size) {
                $file->url = $this->options['upload_url'].rawurlencode($file->name);
                foreach($this->options['image_versions'] as $version => $options) {
                    if ($this->create_scaled_image($file->name, $options)) {
                        $file->{$version.'_url'} = $options['upload_url']
                            .rawurlencode($file->name);
                    }
                }
            } else if ($this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
			
			require_once('inc/connect.php');
			
			$file->exten = end(explode(".",$file->name));
			$file->doc_url = null;
			if(in_array($file->exten, $this->doc_types)){
				$file->doc_url = $this->addDocFile($file);
			}

			if(in_array($file->exten, $this->video_types)){
				$file->video_url = $this->addVideoFile($file);
			}
			
            $file->size = $file_size;
            $file->delete_url = $this->options['script_url']
                .'?file='.rawurlencode($file->name);
            $file->delete_type = 'DELETE';
        } else {
            $file->error = $error;
        }
        return $file;
    }
	
	public function addVideoFile($file){
		require_once('inc/connect.php');
		$filepath = "files/".$file->name;
		$sql_name = mysql_escape_string($file->name);
		
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
		$filesource = $yt->newMediaFileSource($filepath);
		$filesource->setContentType(mime_content_type($filepath));
		// set slug header
		$filesource->setSlug($file->name);

		// add the filesource to the video entry
		$myVideoEntry->setMediaSource($filesource);

		$myVideoEntry->setVideoTitle($file->name);
		$myVideoEntry->setVideoDescription($file->name);
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

		$gotVideo = false;
		$tries = 0;
		while($gotVideo == false && $tries < 10){
			$yt = new Zend_Gdata_YouTube();
			$videoFeed = $yt->getUserUploads('askalvinn');
			$youtube_id = null;
			foreach($videoFeed as $videoEntry) {
				$title = $videoEntry->getVideoTitle();
				if($title == $file->name){
					$youtube_id = $videoEntry->getVideoId();
					$gotVideo = true;
					break;
				}
			}
			$tries++;
			sleep(2);
		}

		require_once('inc/connect.php');
		$sql = "INSERT INTO files (youtube_id, name, type, tries) VALUES ('$youtube_id','$sql_name','video','$tries')";
		mysql_query($sql);
		return $youtube_id;
	}
	
	public function addDocFile($file){
		$base = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'];
		$url = $base.$file->url;

		$api_key = "1ik0u8x1fnbw4db98egs6";

		$contents = file_get_contents("http://api.scribd.com/api?method=docs.uploadFromUrl&api_key=".$api_key."&url=".$url);
		$data = json_decode(json_encode((array) simplexml_load_string($contents)),1);
		if($data['@attributes']['stat'] == "ok"){
			$doc_id = $data['doc_id'];
			$access_key = $data['access_key'];
			$name = mysql_escape_string($file->name);
			mysql_query("INSERT INTO files (name, doc_id, doc_key) VALUES ('$name','$doc_id','$access_key')");
			return mysql_insert_id();
		}
		return 0;
	}
    
    public function get() {
        $file_name = isset($_REQUEST['file']) ?
            basename(stripslashes($_REQUEST['file'])) : null; 
        if ($file_name) {
            $info = $this->get_file_object($file_name);
        } else {
            $info = $this->get_file_objects();
        }
        header('Content-type: application/json');
        echo json_encode($info);
    }
    
    public function post() {
        $upload = isset($_FILES[$this->options['param_name']]) ?
            $_FILES[$this->options['param_name']] : array(
                'tmp_name' => null,
                'name' => null,
                'size' => null,
                'type' => null,
                'error' => null
            );
        $info = array();
        if (is_array($upload['tmp_name'])) {
            foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ?
                        $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                        $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                        $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index]
                );
            }
        } else {
            $info[] = $this->handle_file_upload(
                $upload['tmp_name'],
                isset($_SERVER['HTTP_X_FILE_NAME']) ?
                    $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'],
                isset($_SERVER['HTTP_X_FILE_SIZE']) ?
                    $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'],
                isset($_SERVER['HTTP_X_FILE_TYPE']) ?
                    $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'],
                $upload['error']
            );
        }
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo json_encode($info);
    }
    
    public function delete() {
        $file_name = isset($_REQUEST['file']) ?
            basename(stripslashes($_REQUEST['file'])) : null;
        $file_path = $this->options['upload_dir'].$file_name;
        $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        if ($success) {
            foreach($this->options['image_versions'] as $version => $options) {
                $file = $options['upload_dir'].$file_name;
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        header('Content-type: application/json');
        echo json_encode($success);
    }
}

$upload_handler = new UploadHandler();

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'HEAD':
    case 'GET':
        $upload_handler->get();
        break;
    case 'POST':
        $upload_handler->post();
        break;
    case 'DELETE':
        $upload_handler->delete();
        break;
    default:
        header('HTTP/1.0 405 Method Not Allowed');
}
?>