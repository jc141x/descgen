<?php

function load_classes($class_name)
{
    $path_to_file = 'models/' . $class_name . '.php';

    if (file_exists($path_to_file)) {
        require $path_to_file;
    }
}

spl_autoload_register('load_classes');