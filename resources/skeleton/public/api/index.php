<?php

require dirname(__DIR__, 2) . '/Bootstrap.php';

if (isset($_GET['users'])) {
    $users = $cms->getDatabase()->findAll('users', ['_id' => 'desc']);
    print json_encode($users);
}
