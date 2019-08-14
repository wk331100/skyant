<?php
/**
 * 一次性脚本 只执行一次
 */
namespace App\Console\Commands;

use App\Libs\GmsSms;
use Illuminate\Console\Command;


class ExecSmsSend extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ExecSmsSend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '异步执行发短信';



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
        //队列中获取需要发送的短信
        $sms = new GmsSms();
        $sms->execSend();
        exit('finished');
    }






}
