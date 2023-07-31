<?php
require '../Bootstrap.php';

use \Http\Router;

$router = new Router();

$router->add('/', function() use ($cms) {
  include 'welcome.php';
});

$router->add('/users', function() use ($cms) {
print $cms->toJson($cms->store('users')->findAll());
});


$pages = $cms->store('pages')->findAll();

foreach ($pages as $page) {
  $router->add('/page/'.$page['slug'], function() use ($cms, $page) {
  print $page['body'];
  });
}

$router->run();