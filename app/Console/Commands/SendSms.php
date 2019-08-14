<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Libs\{Curl,RedisKey};
use App\Libs\AliyunSms\SignatureHelper;
use Illuminate\Support\Facades\Redis;
use App\Models\SmsSendModel;

class SendSms extends Command 
{
    protected $signature        = 'send:sms {params*}';
    protected $description      = '发送短信';
    /*private   $accessKeyId      = 'LTAIyaoTWFc6DRct';
    private   $accessKeySecret  = 'UPZa8J46rXqwI3ofYwp4BCxbO9aokY';
    private   $domain           = 'dysmsapi.aliyuncs.com';
    private   $signName         = 'Bitkop';*/
    private $_accessKeyId       = "LTAIaLpi5T0PY2ct";
    private $_accessKeySecret   = "J0crFqbBc8zvOPRahPShGEbKgNvC0E";
    private $_domain            = 'dysmsapi.aliyuncs.com';
    const SIGN_NAME             = 'SkyAnt';

    public function __construct()
    {   
        parent::__construct();
    }

    public function handle()
    {   
        $params     = intval($this->argument('params')[0]);
        if ($params < 100 || $params > 500) {
            exit('Please input 100 to 500'.PHP_EOL);
        }
        while (true) {
            if (!$this->exec($params)) {
                sleep(rand(1,3));
            }
        }
    }

    public function exec(int $limit)
    {   
        $redisData = Redis::rpop(RedisKey::SMS_QUEUE);
        $redisData = json_decode($redisData, true);
        if (!$redisData) {
            return false;
        }
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
                "RegionId"  => "cn-hangzhou",
                "Action"    => "SendSms",
                "Version"   => "2017-05-25",
            ))
        );
        //执行成功更新数据库
        $updateResult = [
            'status' => '2',
            'sms_server' => 'aliyun',
            'response' => json_encode($response)
        ];

        if (mb_strtoupper($response->Code) == 'OK') {
            $updateResult['status'] = '1';
        } else {
            Redis::lpush(RedisKey::SMS_QUEUE , json_encode($redisData));
            return false;
        }
        SmsSendModel::getInstance()->update($updateResult, $redisData['id']);
        return true;
    }

    // public function exec(int $limit)
    // {
    //     $res = DB::table('gms_sms_send')->where('status',0)->limit($limit)->get()->toArray();
    //     if (!$res) {
    //         return true;
    //     }

    //     $workers = [];
    //     foreach ($res as $k=>$v) {
    //         $process = new \swoole_process(function($worker) use ($v) {
    //             $result = $this->send([
    //                 'PhoneNumbers' => '00'.$v->prefix.$v->phone,
    //                 'SignName'     => $this->signName,
    //                 'TemplateCode' => $v->template_code,
    //                 'TemplateParam'=> $v->message,
    //                 'OutId'        => $v->id, 
    //             ]);
    //             $worker->write($result);
    //         },true);
    //         $process->start();
    //         $workers[$v->id] = $process;
    //     }
        
    //     $tmpArr = [];
    //     foreach ($workers as $k=>$process) {            
    //         $response = $process->read();
    //         $data     = json_decode($response,true);
    //         if (isset($data['Message'] , $data['Code']) && $data['Message'] == 'OK' && 'OK' == $data['Code']) {
    //             $tmpArr[$k] = ['status'=>1,'response'=>$response];
    //         } else {
    //             $tmpArr[$k] = ['status'=>2,'response'=>$response];
    //         }
    //     }
    //     while (\swoole_process::wait()) {
    //     }
    //     foreach ($tmpArr as $k=>$v) {
    //         DB::table('gms_sms_send')->where('id' , $k)->update($v);
    //     }
    // }
    // public function send(array $params)
    // {
    //     $apiParams = array_merge([
    //         "SignatureMethod"   => "HMAC-SHA1",
    //         "SignatureNonce"    => uniqid(mt_rand(0,0xffff), true),
    //         "SignatureVersion"  => "1.0",
    //         "AccessKeyId"       => $this->accessKeyId,
    //         "Timestamp"         => gmdate("Y-m-d\TH:i:s\Z"),
    //         "Format"            => "JSON",
    //         "RegionId"          => "cn-hangzhou",
    //         "Action"            => "SendSms",
    //         "Version"           => "2017-05-25",
    //     ], $params);

    //     ksort($apiParams);

    //     $sortedQueryStringTmp   = '';
    //     foreach ($apiParams as $key => $value) {
    //         $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
    //     }
    //     $stringToSign = "POST&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));
    //     $sign         = base64_encode(hash_hmac("sha1", $stringToSign, $this->accessKeySecret . "&",true));
    //     $signature    = $this->encode($sign);
    //     $url          = (true ? 'https' : 'http')."://{$this->domain}/";
    //     $res          = (new Curl)->setHeader(["x-sdk-client" => "php/2.0.0"])->setParams("Signature={$signature}{$sortedQueryStringTmp}")->post($url);
    //     return        $res;
    // }
    // private function encode($str)
    // {
    //     $res = urlencode($str);
    //     $res = preg_replace("/\+/", "%20", $res);
    //     $res = preg_replace("/\*/", "%2A", $res);
    //     $res = preg_replace("/%7E/", "~", $res);
    //     return $res;
    // }
}
