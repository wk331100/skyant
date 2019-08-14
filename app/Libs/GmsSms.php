<?php

namespace App\Libs;

use App\Models\SmsPrefixModel;
use App\Models\SmsSendModel;
use App\Libs\AliyunSms\SignatureHelper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class GmsSms{

    const DEFAULT_TMP_CODE = 'SMS_133969383';
    const DEFAULT_PREFIX = '86';
    const SIGN_NAME      = 'Bitkop';

    private $_params = [];

    // *** 需用户填写部分 ***
    private $_accessKeyId = "LTAIyaoTWFc6DRct";
    private $_accessKeySecret = "UPZa8J46rXqwI3ofYwp4BCxbO9aokY";
    private $_domain    = 'dysmsapi.aliyuncs.com';


    /**
     * 发送短信，将内容添加到数据库，等待执行发送
     * @param $data
     * @return bool|int
     */
    public function send($data){
        $data['prefix'] = !empty($data['prefix']) ? $data['prefix'] : self::DEFAULT_PREFIX;

        if(!self::checkPrefix($data['prefix'])) {
            return false;
        }
        if(empty($data['phone']) || empty($data['code'])){
            return false;
        }

        $sendData = [
            'template_code' => isset($data['template_code']) ? $data['template_code'] : self::DEFAULT_TMP_CODE,
            'prefix' => $data['prefix'],
            'phone' => $data['phone'],
            'message' => json_encode(['code' => $data['code']]),
            'create_time' => date('Y-m-d H:i:s')
        ];
        $sendData['id'] = SmsSendModel::getInstance()->create($sendData);
        $redisData = json_encode($sendData);
        return Redis::lpush(RedisKey::SMS_QUEUE, $redisData);
    }


    /**
     * 异步执行发送短信
     * @return bool|\stdClass
     */
    public function execSend(){
        $redisData = Redis::rpop(RedisKey::SMS_QUEUE);
        $redisData = json_decode($redisData, true);
        $params = [
            'PhoneNumbers' => '00' . $redisData['prefix'] . $redisData['phone'],
            'SignName'      => self::SIGN_NAME,
            'TemplateCode'  => $redisData['template_code'],
            'TemplateParam' => $redisData['message'],
            'OutId'         => $redisData['id'],
        ];
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $response = $helper->request(
            $this->_accessKeyId,
            $this->_accessKeySecret,
            $this->_domain,
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );
        //执行成功更新数据库
        $updateResult = [
            'status' => '2',
            'sms_server' => 'aliyun',
            'response' => json_encode($response)
        ];
        if(mb_strtoupper($response->Code) == 'OK'){
            $updateResult['status'] = '1';
        }

        SmsSendModel::getInstance()->update($updateResult, $redisData['id']);

        //记录日志
        $logMsg = 'Params:' . json_encode($params) . ' | Response:' . json_encode($response);
        Log::channel('third')->info($logMsg);
        return $response;
    }

    /**
     * Check Prefix Valid
     * @param $prefix
     * @return bool
     */
    public static function checkPrefix($prefix){
        if(SmsPrefixModel::getInstance()->checkPrefix($prefix)){
            return true;
        }
        return false;
    }



}