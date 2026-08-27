<?php

require dirname(__DIR__, 2) . '/Bootstrap.php';

use SleekDBVCMS\Controllers\AdminController;

$controller = new AdminController($cms);
$controller->handleRequest();
