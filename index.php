<?php

define("DS","/",true);
define('BASE_PATH',realpath(dirname(__FILE__)).DS,true);

//some configurations
$FOLDER_IMAGES 	= BASE_PATH.'/images';
$FOLDER_SCSS 	= BASE_PATH.'/stylesheets';
$BACKGROUND_URL = '/images';

// Add images to sprites.png
include('spritr.class.php');
$sprintr = new spritr();
$sprintr->init($FOLDER_IMAGES);
$sprintr->generate_scss($FOLDER_SCSS, $BACKGROUND_URL);


?>