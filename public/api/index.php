<?php

require '../../Bootstrap.php';

if (isset($_GET['users'])) {
    $users = $cms->store('users')->findAll(['_id'=>'desc']);
    print json_encode($users);
}