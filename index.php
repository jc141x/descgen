<?php

require 'autoload.php';
header('Content-type: text/plain');
$ent = new Entity();
if (isset($_GET['appid'])) {
    if (isset($_GET['format']))
    {
        print($ent->fetch($_GET['appid'],$_GET['format']));
    } else
    {
        print($ent->fetch($_GET['appid']));
    }
}


