<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ServiceException;
use App\Libs\Curl;
use Illuminate\Support\Facades\Redis;


class RedPackTop extends Command 
{
    protected $signature        = 'redpacktop';
    protected $description      = '动态置顶';

    public function __construct()
    {   
        parent::__construct();
    }

    public function handle()
    {
    	for(;;){
    		//$this->sendRedPack();
    		$this->exec();
    		sleep(rand(1,3));
    	}
    }

    private function exec()
    {	
        
        # 获取不是置顶动态	   
    	$list = DB::table('user_moment')->where(['type'=>2,'is_top'=>0])->where('create_time','>=',date('Y-m-d H:i:s',strtotime('-1 day')))->select('red_pack_id','id')->get()->toArray();
    	if ($list) {
    		foreach ($list as $v) {
    			# 是否有红包
    			if  (!$tmp = DB::table('red_pack')->where(['id'=>$v->red_pack_id,'status'=>1])->first()) {
    				continue;
    			}
                # 红包是否余额
    			if ($tmp->amount - $tmp->received_amount < 0.1) {
    				continue;
    			}
    			DB::table('user_moment')->where(['id'=>$v->id])->update(['is_top'=>1]);
    		}
    	}
        # 获取置顶动态
        $list = DB::table('user_moment')->where(['type'=>2,'is_top'=>1])->select('red_pack_id','id','uid')->get()->toArray();
        if ($list) {
            foreach ($list as $v) {
                # 官方账号动态不撤
                /*if (2 == DB::table('user')->where(['uid'=>$v->uid])->value('type')) {
                    continue;
                }*/
                # 是否红包动态
                if  (!$tmp = DB::table('red_pack')->where(['id'=>$v->red_pack_id,'status'=>1])->first()) {
                    DB::table('user_moment')->where(['id'=>$v->id])->update(['is_top'=>0]);
                    continue;
                }
                # 是否有余额
                if ($tmp->amount - $tmp->received_amount <= 0.1) {
                    DB::table('user_moment')->where(['id'=>$v->id])->update(['is_top'=>0]);
                    continue;
                }
            }
        }
    }

    private function sendRedPack()
    {	
    	$config = ['eth'=>1,'bz'=>1];
    	
    	$list = DB::select("select coin,sum(amount) number from red_pack_patch where status = 0 group by coin;");
    	if (!$list) {
    		foreach ($config as $k=>$v) {
    			$text = strtoupper($k). ' 币种没有资产';
    			$key  = $k.':'.'redPack';
    			if (Redis::get($key)) {
    				return false;
    			} else {
    				Redis::setex($key , 60 , 1);
    				(new Curl)->setHeader(['Content-Type: application/json;charset=utf-8'])->setParams(json_encode(['msgtype'=>'markdown', 'markdown'=>['title'=> '@all', 'text'=>$text], 'at'=>['isAtAll'=>true]]))->post('https://oapi.dingtalk.com/robot/send?access_token=d66ee4cabd6c451a512dd9999274807d71861f05101b18bcbbb5582c0a28c42d');	
    			}
    		}
    	} else {
    		foreach ($list as $v) {
    			if ($config[$v->coin] < $v->number) {
    				continue;
    			}
    			$text = strtoupper($v->coin).'币种红包还剩下 '.bcadd($v->number,0,4).' ['.date('Y-m-d H:i:s').']';
    			$key = $v->coin.':'.'redPack';
    			if (Redis::get($key)) {
    				return false;
    			} else {
    				Redis::setex($key , 60 , 1);
    				(new Curl)->setHeader(['Content-Type: application/json;charset=utf-8'])->setParams(json_encode(['msgtype'=>'markdown', 'markdown'=>['title'=> '@all', 'text'=>$text], 'at'=>['isAtAll'=>true]]))->post('https://oapi.dingtalk.com/robot/send?access_token=d66ee4cabd6c451a512dd9999274807d71861f05101b18bcbbb5582c0a28c42d');	
    			}
    		}
    	}
    }
}
