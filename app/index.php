<?php

error_reporting(E_ALL | E_STRICT);
// require('UploadHandler.php');
// $upload_handler = new UploadHandler();

require 'UploadHandler-mysql.php';

$options = [
	'dsn'           => 'mysql:dbname=uploader;host=localhost;charset=utf8',
	'dbUserName'    => getenv('DB_USER'),
	'dbPassword'    => getenv('DB_PASS'),
	'user_dirs'     => true,
	'userdir_time_to_live' => 7200
];

$upload_handler = New UploadHandlerMYSQL($options);
