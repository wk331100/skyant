<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Combination extends Command 
{
	 /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'Combination';

    protected $signature = 'combination:params {params*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '组合处理';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $params     = $this->argument('params');
        if (!in_array($params[0], ['price' , 'asset','ranking','profit'])) {
            exit('参数错误'.PHP_EOL);
        }

        while (true) {
        	$this->{$params[0]}();
        }
    }

    /*
     * 市场价格
     */
    private function price()
    {   
        $res = DB::table('coin')->select('symbol')->where('enable',1)->get()->toArray();
        if (!$res) {
            exit('没有可用的币种'.PHP_EOL);
        }
        foreach ($res as $v) {
            $usdPrice = Redis::hGet('analysis:coin:ticker:'.strtolower($v->symbol),'usd');
            $cnyPrice = Redis::hGet('analysis:coin:ticker:'.strtolower($v->symbol),'cny');
            if (!$usdPrice && !$cnyPrice) {
                continue;
            }
            $tmpArr = ['cny'=>bcadd($cnyPrice, 0 , 4),'usd'=>bcadd($usdPrice, bcsub(rand(1,100),rand(1,100)) , 2)];
            if (in_array($v->symbol, ['BTC','ETH'])) {
                print_r($tmpArr);
            }
            Redis::hSet('combination:price',strtolower($v->symbol) , json_encode($tmpArr));
        }
        sleep(rand(1,2));
    }

    /*
     * 净值    
     */
    private function asset()
    {
        
        if (!$res = DB::table('combination')->select('id','profit','ranking','over_asset','net_worth','uid')->where('is_del',0)->get()->toArray()) {
            sleep(rand(1,2));
            return true;
        }

        if (!$price = Redis::HgetAll('combination:price')) {
            sleep(rand(1,2));
            return true;
        }

        foreach ($price as $k=>$v) {
            $price[$k] = json_decode($v , true);
        }

        foreach ($res as $k=>$v) {

            if (!$createTime = DB::table('positions')->where('combination_id',$v->id)->where('uid',$v->uid)->groupBy('create_time')->orderByDesc('create_time')->value('create_time')) {
                continue;
            }

            if (!$list = DB::table('positions')->select('coin','id','percent','price','type','number','combination_id')->where('number','>',0)->where('uid',$v->uid)->where('combination_id',$v->id)->where('create_time',$createTime)->get()->toArray()) {
                if ($v->net_worth != $v->over_asset) {
                    DB::table('combination')->where('id',$v->id)->update(['net_worth'=>$v->over_asset]);
                }
                continue;
            }
            $priceSpread = 0;

            foreach ($list as $key=>$val) {
                $priceSpread += bcmul($val->number , $price[$val->coin]['usd'] , 4);
            }

            $net_worth = $v->over_asset + $priceSpread;

            foreach ($list as $values) {

                $percent = bcmul(bcmul($values->number, $price[$values->coin]['usd'] , 4) / $net_worth , 100 , 2);

                if ($percent != $values->percent) {
                    DB::beginTransaction();
                    if (!DB::table('positions')->where('id',$values->id)->update(['percent'=>$percent])) {
                        DB::rollBack();
                        continue;
                    } else {
                        DB::commit();
                    }
                }
                echo 
                ' 虚拟货币资金 ',$priceSpread,
                " {$values->number}个{$values->coin} 所占的百分比 {$percent}% ",PHP_EOL;
            }
            if ($net_worth != $v->net_worth) {
                DB::beginTransaction();
                if (!DB::table('combination')->where('id',$v->id)->update(['net_worth'=>$net_worth])) {
                    DB::rollBack();
                } else {
                    DB::commit();
                }
            }
        }
        sleep(rand(1,2));
    }

    /*
     * 收益
     */
    private function profit()
    {
        if (!$res = DB::table('combination')->select('id','asset','net_worth')->where('is_del',0)->get()->toArray()) {
            sleep(rand(1,2));
            return true;
        }
        foreach ($res as $v) {

            $profit = bcdiv(bcsub($v->net_worth, $v->asset , 4) , $v->asset , 4) * 100;
            DB::table('combination')->where('id',$v->id)->update(['profit'=>$profit]);
        }
        sleep(rand(1,20));

    }

    /*
     * 排行榜
     */
    private function ranking()
    {
        if (!$res = DB::table('combination')->select('id','uid','ranking')->where('is_del',0)->orderByDesc('profit')->get()->toArray()) {
            sleep(rand(1,2));
            return true;
        }
        foreach ($res as $k=>$v) {
            if ($v->ranking == $k+1) {
                continue;
            }
            DB::table('combination')->where('id',$v->id)->update(['ranking'=>($k+1)]);
        }
        sleep(rand(1,2));
    }
}
