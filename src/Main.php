<?php
/**
 * @author        LittleMo
 * @description   文件描述
 */
 
namespace FuSuper;

use Mimey\MimeTypes;

class Main{

    public $name = '小莫';

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function Hello()
    {
        printf("你好啊 %s , 非常高兴看到你", $this->name);
    }

    public function TestType($type)
    {
        $mime = new MimeTypes();
        $type_name = $mime->getMimeType($type);
        echo $type_name;
    }
}