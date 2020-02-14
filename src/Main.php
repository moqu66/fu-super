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
    public $file_path;      #文件保存路径
    public $error_info;     #错误信息

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
     * 上传保存文件
     * @param string $file $_FILES['userfile'] 预定义变量获取的数组
     * @param string $file_name 文件新名称, 默认将使用 uniqid() 函数随机创建
     * @param bool $cover 是否覆盖原有文件, 默认否 false
     * @return bool 上传成功 true , 失败false
     */
    public function upSave($file, $file_name = null, $cover = false)
    {
        if (empty($file)) {
            $this->error_info = array(
                'code' => 1,
                'msg' => '没有接收到文件数据！',
            );
            return false;
        } else if (!is_file($file['tmp_name'])) {
            $this->error_info = array(
                'code' => 2,
                'msg' => '临时文件不存在！',
            );
            return false;
        }

        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_name = $file_info->file($file['tmp_name']);
        if (!$this->checkSuffix($mime_name)) {
            return false;
        }

        if ($file['error'] > 0) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE :
                    $this->error_info = array(
                        'code' => 3,
                        'msg' => '文件大小超出了服务器的限制！！',
                    );
                    break;
                case UPLOAD_ERR_FORM_SIZE :
                    $this->error_info = array(
                        'code' => 4,
                        'msg' => '文件大小超出了表单规定范围！',
                    );
                    break;
                case UPLOAD_ERR_PARTIAL :
                    $this->error_info = array(
                        'code' => 5,
                        'msg' => '文件只有部分被上传！',
                    );
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->error_info = array(
                        'code' => 6,
                        'msg' => '没有文件被上传！',
                    );
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->error_info = array(
                        'code' => 7,
                        'msg' => '找不到临时文件夹！',
                    );
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->error_info = array(
                        'code' => 8,
                        'msg' => '文件写入失败！',
                    );
                    break;
            }
            return false;
        }

        if (filesize($file['tmp_name']) > $this->file_size) {
            $this->error_info = array(
                'code' => 9,
                'msg' => '文件大小超出了限制范围！',
            );
            return false;
        }

        $file_dir = $this->root_path . $this->save_dir;
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0755, true)) {
                $this->error_info = array(
                    'code' => 10,
                    'msg' => '创建文件保存目录时失败！',
                );
                return false;
            }
        }

        if (empty($file_name)) {
            $file_name = uniqid();
        }
        $file_name .= '.' . $this->file_suffix;

        $file_path = $file_dir . $file_name;
        if (file_exists($file_path)) {
            if ($cover) {
                $file_name = uniqid() . '.' . $this->file_suffix;
                $file_path = $this->root_path . $file_dir . $file_name;
            }
        }

        try {
            $rs = move_uploaded_file($file['tmp_name'], $file_path);
            if (!$rs) {
                throw new Exception('保存文件到指定位置失败！');
            }
            $this->file_path = array(
                0 => $file_path,
                1 => $this->save_dir . $file_name,
            );
            return true;
        } catch (Exception $e) {
            $this->error_info = array(
                'code' => 11,
                'msg' => $e->getMessage(),
            );
            return false;
        }

    }

    /**
     * 检查MIME类型对应的文件后缀名是否在白名单内
     * @param string $mime_name MIME类型, 例: application/json
     * @return bool 存在白名单内返回 true, 不存在返回 false
     */
    private function checkSuffix($mime_name)
    {
        $mimes = new MimeTypes();
        $mime_suffix = $mimes->getExtension($mime_name);
        if (empty($mime_suffix)) {
            $this->error_info = array(
                'code' => 12,
                'msg' => '检测失败, 未知的文件后缀名！',
            );
            return false;
        } else {
            if (!in_array($mime_suffix, $this->suffix)) {
                $this->error_info = array(
                    'code' => 13,
                    'msg' => '文件类型不在上传许可范围内！',
                );
                return false;
            } else {
                $this->file_suffix = $mime_suffix;
                return true;
            }
        }
    }
}