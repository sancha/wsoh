<?php
if(empty($_GET['url']))
	exit;
$youtube_id = $_GET['url'];
?>
<object style="height: 390px; width: 640px">
<param name="movie" value="http://www.youtube.com/v/<?php echo $youtube_id;?>?version=3&autoplay=1">
<param name="allowFullScreen" value="true">
<param name="allowScriptAccess" value="always">
<embed src="http://www.youtube.com/v/<?php echo $youtube_id;?>?version=3&autoplay=1" type="application/x-shockwave-flash" allowfullscreen="true" allowScriptAccess="always" width="640" height="390">
</object>