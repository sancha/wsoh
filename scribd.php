<?php
if(empty($_GET['url']))
	exit;
if(!is_numeric($_GET['url']))
	exit;
require_once('inc/connect.php');
$result = mysql_query("SELECT * FROM files WHERE id='$_GET[url]' ORDER BY id DESC LIMIT 1");
while($row = mysql_fetch_array($result)){
	$doc_id = $row['doc_id'];
	$access_key = $row['doc_key'];
	?>

		<script type='text/javascript' src='http://www.scribd.com/javascripts/view.js'></script>
		<div id='embedded_flash'><a target='_blank' href="http://www.scribd.com">Scribd</a></div>
		
		<script type="text/javascript">
			var scribd_doc = scribd.Document.getDoc(<?php echo $doc_id;?>, '<?php echo $access_key;?>' );
			scribd_doc.addParam( 'jsapi_version', 1 );
			scribd_doc.write( 'embedded_flash' );
		</script>
	<?php
}