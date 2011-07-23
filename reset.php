<?php
require_once('inc/connect.php');
mysql_query("TRUNCATE TABLE files");
popen("rm files/*","r");
popen("rm thumbnails/*","r");