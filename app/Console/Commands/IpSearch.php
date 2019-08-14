<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Libs\{Curl};

class IpSearch extends Command 
{
    protected $signature        = 'serarch:ip {params*}';
    protected $description      = '查询Ip详细';

    public function __construct()
    {   
        parent::__construct();
    }

    public function handle()
    {      
        $params     = intval($this->argument('params')[0]);
        if ($params < 10 || $params > 500) {
            exit('Please input 100 to 500'.PHP_EOL);
        }
        while (true) {
            $this->exec($params);
            sleep(rand(1,3));
        }
    }

    public function exec(int $number)
    {   
        $res = DB::select("select id,ip from user_login where area is null limit 0,{$number};");
        if (!$res) {
            return true;
        }
        $workers = [];
        foreach($res as $v) {
            $process = new \swoole_process(function($worker) use($v){
                $str = (new Curl)->setTimeout(10)->setIp()->get("http://api.k780.com/?app=ip.get&ip={$v->ip}&appkey=20904&sign=d60ed52342f8ba964ef2fdab1a80932f&format=json");
                $worker->write($str);
            },true);
            $process->start();
            $workers[$v->id] = $process;
        }
        
        foreach ($workers as $k=>$process) {            
            $result = json_decode($process->read() , true);
            $tmpArr = [
                'continent' => 'unknown',
                'country'   => 'unknown',
                'city'      => 'unknown',
                'area'      => 'unknown',
            ];
            if (isset($result['success']) && $result['success'] == 1) {
                $result              = $result['result'];
                $tmpArr['continent'] = $result['area_style_areanm'];
                $tmpArr['country']   = $result['area_style_simcall'];
                $tmpArr['city']      = $result['detailed'];
                $tmpArr['area']      = $result['att'];
            }            
            DB::table('user_login')->where('id',$k)->update($tmpArr);
        }
        while (\swoole_process::wait()) {
        }
    }
}
