<?php
require_once('../lib/Sprockets.php');

$sprockets = new Sprockets(
	preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']),
	array(
		'autoRender' => true
	)
);
