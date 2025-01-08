<!DOCTYPE html>
<html>
    <head>
        <style>
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                height: 100vh;
            }
            textarea {
                height: 100%;
                width: 100%;
                border: 4px solid black;
                margin: 0;
                background-color: black;
                color: white;
            }
        </style>
    </head>
    <body>
<?php

require 'autoload.php';
$ent = new Entity();
$desc = "";
if (isset($_GET['appid'])) {
    if (isset($_GET['format']))
    {
        $desc = $ent->fetch($_GET['appid'],$_GET['format']);
    } else
    {
        $desc = $ent->fetch($_GET['appid']);
    }
}
    ?>
    <textarea><?php echo $desc; ?></textarea>
    </body>
</html>

