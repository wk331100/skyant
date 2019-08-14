<?php
/**
 * 一次性脚本 只执行一次
 */
namespace App\Console\Commands;

use App\Libs\RedisKey;
use App\Libs\Util;
use App\Models\UserFollowModel;
use App\Services\FollowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class RebuildFollowCache extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'RebuildFollowCache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重塑Redis中关注关系数据';



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
        //第一步，从数据库查询关注数据，被关注数据，互相关注数据
        $uidList = UserFollowModel::getInstance()->getFollowUidGroup();
        if(!empty($uidList)){
            foreach ($uidList as $item){
                //获取关注列表
                $followList = UserFollowModel::getInstance()->getFollowList($item->uid);
                foreach ($followList as $follow){
                    $time = strtotime($follow->create_time);
                    $score = Util::createScore($time);
                    FollowService::setFollowCache($follow->uid, $follow->followed_uid, $score); //设置redis缓存用户关注
                    echo "设置用户{$follow->uid} 关注 {$follow->followed_uid}\r\n";
                    FollowService::setFansCache($follow->uid, $follow->followed_uid, $score); //设置粉丝缓存
                    echo "设置用户{$follow->followed_uid} 成为 {$follow->uid} 的粉丝\r\n";
                    //设置redis用户互相关注
                    $followed = UserFollowModel::getInstance()->checkFollowed($follow->followed_uid,$follow->uid);
                    if($followed){
                        $followBothValue = implode('_', array_sort(([$follow->uid, $follow->followed_uid])));
                        Redis::hset(RedisKey::FOLLOW_BOTH, $followBothValue, 1);
                        echo "设置用户{$follow->followed_uid} 和 {$follow->uid} 互相关注\r\n";
                    }

                    echo  "=======================\r\n";
                }
            }
        }

        exit('finished');
    }






}
