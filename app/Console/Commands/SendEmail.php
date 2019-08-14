<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Libs\{Curl,RedisKey};
include_once __DIR__.'/../../Libs/AliyunEmail/aliyun-php-sdk-core/Config.php';
use Dm\Request\V20151123 as Dm;

class SendEmail extends Command 
{
    protected $signature        = 'send:email {params*}';
    protected $description      = '发送邮件';

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
		$res = Redis::rpop(RedisKey::EMAIL_QUEUE);
		if (!$res = json_decode($res , true)) {
			return false;
		}
        
        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", env('EMAIL_KEY'), env('EMAIL_SERCET'));
        $client         = new \DefaultAcsClient($iClientProfile);    
        $request        = new Dm\SingleSendMailRequest();     
        $request->setAccountName(env('EMAIL_FROM'));
        $request->setFromAlias('SkyAnt');
        $request->setAddressType(1);
        $request->setTagName("SkyAnt");
        $request->setReplyToAddress("true");
        $request->setToAddress($res['to']);
        $request->setSubject($res['subject']);
        $request->setHtmlBody($res['html']);
        $response       = $client->getAcsResponse($request);

        if (isset($response->EnvId , $response->RequestId) && $response->EnvId && $response->RequestId) {
            return DB::table('gms_email_send')->where('id',$res['id'])->update([
                'status'        => 1,
                'result'        => json_encode($response),
                'email_server'  => 'aliyun',
            ]);
        } else {
            Redis::lpush(RedisKey::EMAIL_QUEUE, json_encode($res));
            return false;
        }
    }
}
