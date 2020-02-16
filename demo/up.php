<?php

require 'vendor/autoload.php';

$file_data = $_FILES['imgdata'];

use \FuSuper\Main;

$fs = new Main(__DIR__);

$fs->setConf();

$rs = $fs->upSave($file_data);

if ($rs) {
    echo '<pre>';
    print_r($fs->file_info);
    echo '<hr/>';
    print_r($fs->error_info);
    echo '</pre>';
} else {
    echo '<pre>';
    print_r($fs->error_info);
    echo '</pre>';
}
