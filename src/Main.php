<?php
/**
 * @author  LittleMo
 */

namespace FuSuper;

use Mimey\MimeTypes;

class Main
{

    private $root_path;     #项目根目录,绝对路径
    private $suffix;        #后缀白名单
    private $file_size;     #文件大小
    private $save_dir;      #保存目录
    public $file_info;      #文件信息
    public $mime_array;     #MIME映射后缀

    //错误信息
    public $error_info = array(
        'info' => null,
        'number' => 0,
        'msg' => null,
    );

    /**
     * Main constructor.
     * 初始化, 设置项目根目录路径
     * @param string $root_path 项目根目录, 绝对路径
     */
    public function __construct($root_path)
    {
        $this->root_path = rtrim($root_path, '/');
    }

    /**
     * 设置文件上传所需条件
     * @param string $save_dir 文件保存目录,可以多级, 默认: 项目根目录/upload/年月日/
     * @param array $suffix 文件后缀白名单, 默认允许 png,jpg,jpeg,gif,icon
     * @param int $file_size 文件大小上限, 单位MB, 默认10
     */
    public function setConf($save_dir = null, $suffix = null, $file_size = 10)
    {
        if (empty($save_dir)) {
            $this->save_dir = '/upload/' . date('Ymd') . '/';
        } else {
            $this->save_dir = '/' . trim($save_dir, '/') . '/';
        }

        if (empty($suffix)) {
            $this->suffix = array('png', 'jpg', 'jpeg', 'gif', 'icon');
        } else {
            $this->suffix = $suffix;
        }

        $this->file_size = $file_size * 1048576;
    }

    /**
     * @param array $file $_FILES['userfile'] 预定义变量获取的数组
     * @param bool $cover 是否覆盖原有文件, 默认否 false
     * @return bool 上传成功 true , 失败false
     */
    public function upSave($file, $cover = false)
    {
        if (!is_array($file['tmp_name']) and is_file($file['tmp_name'])) {
            $data = array(
                'name' => $file['name'],
                'tmp_name' => $file['tmp_name'],
                'error' => $file['error'],
            );
            return $this->upload($data, $cover);
        } else if (is_array($file['tmp_name']) and !empty($file['tmp_name'])) {
            for ($i = 0; $i < count($file['tmp_name']); $i++) {
                $data = array(
                    'name' => $file['name'][$i],
                    'tmp_name' => $file['tmp_name'][$i],
                    'error' => $file['error'][$i],
                );
                $this->upload($data, $cover);
            }
            if ($this->error_info['number'] >= count($file['tmp_name'])) {
                return false;
            } else {
                return true;
            }
        } else {
            $this->outputError(11, '没有接收到文件数据！');
        }
    }

    /**
     * 上传保存文件
     * @param array $file $_FILES['userfile'] 预定义变量获取的数组
     * @param bool $cover 是否覆盖原有文件, 默认否 false
     * @return bool 上传成功 true , 失败false
     */
    private function upload($file, $cover = false)
    {
        if (!is_file($file['tmp_name'])) {
            $this->outputError(12, '临时文件不存在！', $file['name']);
            return false;
        }

        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_name = $file_info->file($file['tmp_name']);
        if (!$this->checkSuffix($mime_name, $file['name'])) {
            return false;
        }

        if ($file['error'] > 0) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE :
                    $this->outputError(21, '文件大小超出了服务器的限制！', $file['name']);
                    break;
                case UPLOAD_ERR_FORM_SIZE :
                    $this->outputError(22, '文件大小超出了表单的规定范围！', $file['name']);
                    break;
                case UPLOAD_ERR_PARTIAL :
                    $this->outputError(23, '文件只有部分被上传！', $file['name']);
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->outputError(24, '没有文件被上传！', $file['name']);
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->outputError(25, '找不到临时文件夹！', $file['name']);
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->outputError(26, '文件写入失败！', $file['name']);
                    break;
            }
            return false;
        }

        if (filesize($file['tmp_name']) > $this->file_size) {
            $this->outputError(13, '文件大小超出了限制范围！', $file['name']);
            return false;
        }

        $file_dir = $this->root_path . $this->save_dir;
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0755, true)) {
                $this->outputError(14, '创建保存目录失败！', $file['name']);
                return false;
            }
        }


        $file_name = uniqid('fu_super_') . '.' . $this->file_suffix;

        $file_path = $file_dir . $file_name;
        if (file_exists($file_path)) {
            if ($cover) {
                $file_name = uniqid('fu_super_') . '.' . $this->file_suffix;
                $file_path = $this->root_path . $file_dir . $file_name;
            }
        }

        try {
            $rs = move_uploaded_file($file['tmp_name'], $file_path);
            if (!$rs) {
                throw new Exception('保存文件到指定位置失败！', $file['name']);
            }
            $this->file_info[] = array(
                'file_name_old' => $file['name'],
                'file_name_new' => $file_name,
                'file_size' => filesize($file_path),
                'file_mime' => $mime_name,
                'file_suffix' => $this->file_suffix,
                'absolute_path' => realpath($file_path),
                'relative_path' => realpath($this->save_dir . $file_name),
            );
            return true;
        } catch (Exception $e) {
            $this->outputError(15, $e->getMessage(), $file['name']);
            return false;
        }

    }

    /**
     * 检查MIME类型对应的文件后缀名是否在白名单内
     * @param string $mime_name MIME类型, 例: application/json
     * @return bool 存在白名单内返回 true, 不存在返回 false
     */
    private function checkSuffix($mime_name, $file_name)
    {
        if (empty($this->mime_array)) {
            $mimes = new MimeTypes();
        } else {
            $builder = \Mimey\MimeMappingBuilder::create();
            foreach ($this->mime_array as $k => $v) {
                foreach ($v as $vv) {
                    $builder->add($k, $vv);
                }
            }
            $mimes = new MimeTypes($builder->getMapping());
        }

        $mime_array = array();
        foreach ($this->suffix as $v) {
            $w = $mimes->getAllMimeTypes($v);
            if (!empty($w)) {
                foreach ($w as $s) {
                    $mime_array[] = $s;
                }
            }
        }

        if (in_array($mime_name, $mime_array)) {
            $this->file_suffix = $mimes->getExtension($mime_name);
            return true;
        } else {
            if (empty($mimes->getAllExtensions($mime_name))) {
                $this->outputError(16, '找不到MIME对应的后缀名！', $file_name);
            }
            $this->outputError(17, '文件类型不在上传许可范围内！', $file_name);
            return false;
        }
    }

    /**
     * 生成错误信息到成员属性 $error_info
     * @param int $code 错误代码
     * @param string $msg 错误信息
     * @param string $file_name 文件名
     */
    private function outputError($code, $msg, $file_name = null)
    {
        $info = array(
            'code' => $code,
            'msg' => $msg,
            'file_name' => $file_name,
        );

        $this->error_info['info'][] = $info;
        $this->error_info['number'] = count($this->error_info['info']);
        $this->error_info['msg'] = '共有' . $this->error_info['number'] . '个文件上传失败！';
    }
}