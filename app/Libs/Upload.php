<?php

namespace App\Libs;

use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\Log;
use OSS\OssClient;
use OSS\Core\OssException;

class Upload{

    const TYPE_HEAD_IMAGE  = 'headImage';
    const TYPE_NORMAL_IMAGE = 'image';
    const TYPE_FILE   = 'file';
    const TYPE_AUTH   = 'auth';

    #private $_accessKeyId       = 'LTAIVG8f7P4u7o0w';
    #private $_accessKeySecret   = 'TRe0FmPKPq1eYOxFveem7nKkfwOg82';
    private $_accessKeyId       = 'LTAISwYtpbwl2Lru';
    private $_accessKeySecret   = 'W0HyWqSrPAw7glAVtQZ0HNQ2ns7hWh';
    private $_endPoint          = 'oss-cn-beijing.aliyuncs.com';
    private $_bucket            = 'isbars';

    private $_type;

    function __construct($type)
    {
        $this->_type = $type;
    }


    /**
     * 上传图片
     * @param $image，Request获取到的file类型
     * @return bool
     */
    public function uploadImage($image){
        if($image->getClientSize() > 40960000){
            throw new ServiceException(MessageCode::IMAGE_SIZE_TOO_LARGE);
        }
        $clientName = $image->getClientOriginalName(); //客户端文件名称..
        $extension = $image -> getClientOriginalExtension(); //上传文件的后缀.
        if (!in_array($extension, ['png','jpeg','gif','jpg'])) {
            return false;
        }
        $newName =  date('YmdHis') . md5($clientName) . "." . $extension; //定义上传文件的新名称
        $path = $image->move(storage_path('uploads'), $newName); //把缓存文件移动到制定文件夹

        $object = $this->_type . '/'  . $newName;
        $content = $path->getPathName();
        try {
            $ossClient = new OssClient($this->_accessKeyId,  $this->_accessKeySecret, $this->_endPoint);
            $res = $ossClient->uploadFile($this->_bucket, $object, $content);
            if($res){
                return $res['info']['url'];
            }
        } catch (OssException $e) {
            Log::channel('third')->info('FileUpload - path:' . $path . ' | response: ' . $e->getMessage());
        }
        return false;
    }








}
