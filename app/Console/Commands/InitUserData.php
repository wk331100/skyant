<?php
/**
 * 一次性脚本 只执行一次
 */
namespace App\Console\Commands;

use App\Libs\GmsSms;
use App\Services\AccountService;
use Illuminate\Console\Command;


class InitUserData extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'InitUserData';

    protected $signature = 'InitUserData {uid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化用户数据表';



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
        $uid = $this->argument('uid');
        AccountService::initUserConfig($uid);   //初始化用户配置
        AccountService::initUserInfo($uid);     //初始化用户基本信息
        AccountService::initUserNotice($uid);   //初始化用户通知
    }






}
