<?php

// Database Configuration
$default_stores = [
	'projects' => [
		'title' => 'text',
		'job' => 'text',
		'body' => 'textarea',
		'image' => 'image',
	],
	'posts' => [
		[
			'translatable' => ['title','body']
		],
		'title' => 'text',
		'body' => 'textarea',
		'image' => 'image',
		'created'=> 'datetime',
	],
];

// Other options
$config = [
	'app_name' => 'SleekdbVCMS',
	'public_path' => dirname(__FILE__).'/public', // DIRECTORY where your index.php should be.
	'translations' => ['en','es'],
	'stores' => $default_stores,
	'upload_files_extensions_allowed' => [
		'image/jpeg' => 'jpeg', 
		'text/xml' => 'xml',
	],
	'options' => [
		'auto_cache' => true,
		'timeout' => 121
	],
];