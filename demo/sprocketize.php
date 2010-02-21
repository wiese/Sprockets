<?php
require_once('../lib/Sprockets.php');
$filePath = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
$sprockets = new Sprockets(
	$filePath,
	array(
		'baseUri' => '/php-sprockets',
		'autoRender' => true
	)
);
