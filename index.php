<?php
date_default_timezone_set('America/Los_Angeles');

define("DS", "/", true);
define('BASE_PATH', realpath(dirname(__FILE__)), true);

//Folder path configurations
$FOLDER_IMAGES = BASE_PATH . DS .'images';
$FOLDER_SCSS = BASE_PATH . DS . 'stylesheets';
$BACKGROUND_IMAGE_SCSS = '/images';
$FOLDER_SPRITES = $FOLDER_IMAGES . DS . 'sprites';

include ('spritr.class.php');
include ('FileAlterationMonitor.class.php');

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	//get $_POST data as String value and convert it into Array
	$data = json_decode(file_get_contents('php://input'), true);
	$f = new FileAlterationMonitor($FOLDER_SPRITES);
	if (!empty($data['fileList'])) {
		$f->updateMonitor($data['fileList']);
	}
	$newFiles = $f->getNewFiles();
	$removedFiles = $f->getRemovedFiles();
	
	$result = false;
	if (($newFiles || $removedFiles) || (empty($data['fileList']))){
		// Add images to sprites.png
		$sprintr = new spritr();
		$sprintr->init($FOLDER_IMAGES);
		$result = $sprintr->generate_scss($FOLDER_SCSS, $BACKGROUND_IMAGE_SCSS);
	}
	$myObj = new StdClass;
	$myObj->fileList = $f->updateMonitor();
	
	if ($result) {
		$myObj->result = date('Y-m-d H:i:s').' --- New "sprites.png" was generated...';
		$myObj->error = '0';
	} else {
		$myObj->result = date('Y-m-d H:i:s').' --- Error, cannot generate sprites.png...';
		$myObj->error = '1';
	}
	header('Content-type: application/json');
	echo json_encode($myObj);
	exit;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript">
			var myObj = new Object();
			var intervalID;
			var cancel_btn = '<a href="javascript:cancelRequest();">Cancel</a>';
			var reload_btn = '<a href="javascript:window.location.reload();">Reload</a>';
			function cancelRequest() {
				clearInterval(intervalID);
				$('body').append('<div>Stop monitoring folder:<?php echo $FOLDER_SPRITES;?> | ' + reload_btn + '</div>');
			}
			
			myObj.fileList = new Array();
			$(document).ready(function(){
				intervalID = setInterval(function(){
					sendRequest();
				}, 1000);
				function sendRequest() {
					$.ajax({
						type: "POST",
						async: false,
						url: 'index.php',
						dataType: "json",
						data: JSON.stringify(myObj),
						timeout: 800,
						contentType: "application/json; charset=utf-8",
						success: function(msg) {
							myObj = msg;
							if (msg.error == 0) {
								$('body').append('<div>' + msg.result + ' | ' + cancel_btn + ' | ' + reload_btn + '</div>');
							}
						},
						error: function(error) {
							$('body').append('<div>' + error.responseText + ' | ' + reload_btn + '</div>');
							clearInterval(intervalID);
						}
					});
				}
			});
		</script>
	</head>
	<body>
		<div><?php echo 'Start monitoring folder: ' . $FOLDER_SPRITES.'<br/>'; ?></div>
	</body>
</html>