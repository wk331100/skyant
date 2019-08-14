<?php
/**
 * 一次性脚本 只执行一次
 */
namespace App\Console\Commands;

use App\Libs\GmsSms;
use App\Libs\MessageCode;
use App\Models\RedPackModel;
use App\Models\RedPackPatchModel;
use App\Services\AssetService;
use App\Services\RedPackService;
use Illuminate\Console\Command;


class CheckRedPackTimeOut extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'CheckRedPackTimeOut';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查红包过期';



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
        while (true){
            //获取24小时前的红包列表
            $list = RedPackModel::getInstance()->getTimeOutRedPack();

            if(!empty($list)){
                foreach ($list as $item){
                    //首先更新红包状态为已超时
                    RedPackModel::getInstance()->update(['status'=>RedPackService::STATUS_TIMEOUT], $item->id);
                    $returnAmount = 0;
                    if($item->status == RedPackService::STATUS_DEFAULT){
                        $returnAmount += $item->amount;
                    } else {
                        //查询所有子红包
                        $patchList = RedPackPatchModel::getInstance()->getPatchList($item->id);
                        //统计未被领取的子红包金额
                        if(!empty($patchList)){
                            foreach ($patchList as $patch){
                                if($patch->status == RedPackService::PATCH_STATUS_DEFAULT){
                                    $returnAmount += $patch->amount;
                                    //子红包状态更新
                                    RedPackPatchModel::getInstance()->update(['status' => RedPackService::PATCH_STATUS_TIMEOUT],$patch->id );
                                }

                            }
                        }
                    }

                    //返回用户为被领取的红包资产
                    if($returnAmount > 0) {
                        $addAssetData = [
                            'coin' => $item->coin,
                            'number' => $returnAmount,
                            'event' => AssetService::EVENT_RED_PACK,
                            'desc' => MessageCode::KEY_MOMENT_RED_PACK_RETURN
                        ];
                        AssetService::addAsset($addAssetData, $item->uid);
                    }
                }
            }
            sleep(60);
        }
    }






}
