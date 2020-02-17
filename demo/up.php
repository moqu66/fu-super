<?php
/**
 * @author        LittleMo
 * @description   文件描述
 */

require 'vendor/autoload.php';

use \FuSuper\Main;

$file_data = $_FILES['fileData'];

$fu = new Main(__DIR__);

$fu->setConf();

$rs = $fu->upSave($file_data);

if ($rs) {
    echo '<pre>';
    print_r($fu->file_info);
    echo '<hr/>';
    if ($fu->error_info['number'] > 0) {
        print_r($fu->error_info);
    }
    echo '</pre>';
} else {
    echo '<pre>';
    print_r($fu->error_info);
    echo '</pre>';
}