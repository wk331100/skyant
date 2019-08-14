<?php
/**
 * 一次性脚本 只执行一次
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libs\Curl;
use App\Libs\RedisKey;
use Illuminate\Support\Facades\Redis;


class SetCoinPrice extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'SetCoinPrice';
    //protected $signature = 'SetCoinPrice{coin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'bz eth  cny price';
    const BZ_PRICE_URL    = 'https://apiv2.bitz.com/Market/currencyCoinRate';
    const SLEEP_TIME      = 300;
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
        //价格
        while (1) {
            $price =  $this->getCoinPriceByBZ();
        }
        exit('finished');
    }
    #获取币数据
    public function getCoinPriceByBZ(){
        $coins = $this->getCoins();
        for ($i=0; $i <count($coins); $i++) {
            $coin      = $coins[$i];
            $curl      = new Curl();
            $fullUrl   = self::BZ_PRICE_URL . '?coins=' .$coin;
            $priceKey  = RedisKey::COIN_PRICE;
            $result    = $curl->get($fullUrl);
            $result    = json_decode($result, true);
            #抽取需要的数据
            if($result['status'] == 200 && isset($result['data'][$coin]['cny'])){
                $price = $result['data'][$coin];
                Redis::hset($priceKey, $coin, json_encode($price));
            }
            echo json_encode($price);
        }
        #休眠规定的时间后再次获取
        sleep(self::SLEEP_TIME);
    }
    #币种
    public function getCoins(){

       return  array(
            'eth',
            'bz',
        );
    }
}
