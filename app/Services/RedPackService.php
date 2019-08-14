<?php
namespace App\Services;

use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Libs\RedisKey;
use App\Models\CoinModel;
use App\Models\RedPackModel;
use App\Models\RedPackPatchModel;
use App\Models\UserAssetLogModel;
use App\Models\UserInfoModel;
use App\Models\UserModel;
use App\Models\UserCoinModel;
use App\Services\RegisterService;
use Illuminate\Support\Facades\Redis;

class RedPackService{
    const TYPE_AVG = '1';
    const TYPE_RANDOM = '2';

    const STATUS_DEFAULT = '0';
    const STATUS_ACTIVE  = '1';
    const STATUS_FINISH  = '2';
    const STATUS_TIMEOUT = '3';
    const STATUS_CANCEL  = '4';

    const PATCH_STATUS_DEFAULT = '0';
    const PATCH_STATUS_USED   = '1';
    const PATCH_STATUS_TIMEOUT = '3';
    const PATCH_STATUS_CANCEL = '4';

    const RED_PACK_SEND  = '2';
    const RED_PACK_RECEIVE = '1';

    /**
     * 获取红包币种列表
     * @return array
     * @throws ServiceException
     */
   public static function getRedPackCoinList($data){
       if(empty($data['token'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

        $list = CoinModel::getInstance()->getRedPackCoin();
        $result = [];
        if(!empty($list)){
            foreach ($list as $item){
                $result[] = [
                    'coin' => $item->name,
                    'display' => $item->display,
                    'icon'  => $item->icon,
                    'full_name' => $item->full_name,
                    'red_pack_min' => $item->red_pack_min
                ];
            }
        }
        return $result;
   }


    /**
     * 创建红包
     * @param $data
     * @return int
     * @throws ServiceException
     */
   public static function create($data){
       if(empty($data['token']) || empty($data['coin']) || empty($data['amount']) || empty($data['number'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       $coinInfo = CoinModel::getInstance()->getCoinByName($data['coin']);
       if(empty($coinInfo)){
           throw new ServiceException(MessageCode::ASSET_COIN_NOT_EXIST);
       }

      
/*       $userInfo = UserModel::getInstance()->getInfo($uid);
       if (empty($userInfo->assets_password)) {
        # 请完善资金密码
          throw new ServiceException(MessageCode::USER_ASSETS_PWD);
       }
       if ($userInfo->assets_password != RegisterService::makeUserPassword($data['pwd'],$userInfo->pwd_rand)){
        # 资金密码错误
          throw new ServiceException(MessageCode::USER_ASSETS_ERROR);
       }*/

       //检查用户当前币种资产
       $balance = UserCoinModel::getInstance($data['coin'])->getAsset($uid);
       if($balance['balance_available'] < $data['amount']){
           throw new ServiceException(MessageCode::ASSET_NOT_ENOUGH);
       }

       //检查红包数量
       $maxNum = ConfigService::getConfig(ConfigService::RED_PACK_MAX_NUM);
       if($data['number'] > $maxNum){
           throw new ServiceException(MessageCode::RED_PACK_NUM_ERROR);
       }

       //检查红包最小金额
        if(bcdiv($data['amount'], $data['number'], 8) < $coinInfo->red_pack_min){
            throw new ServiceException(MessageCode::RED_PACK_MIN_ERROR,[
              floatval($coinInfo->red_pack_min).' '.strtoupper($data['coin'])
            ]);
        }

        //扣除资产
       $assetData = [
           'coin' => $data['coin'],
           'number' => $data['amount'],
           'event' => AssetService::EVENT_RED_PACK,
           'desc' => MessageCode::KEY_MOMENT_RED_PACK_COST
       ];
       $deductAsset = AssetService::deductAsset($assetData, $uid);

       if(!$deductAsset){
           throw new ServiceException(MessageCode::RED_PACK_MIN_ERROR);
       }

       //创建红包
        $redPackData = [
            'uid'   => $uid,
            'coin' => $data['coin'],
            'amount' => $data['amount'],
            'type' => self::TYPE_AVG,
            'number' => $data['number'],
            'wishing' => MessageCode::KEY_MOMENT_RED_PACK_COST,
            'status' => self::STATUS_DEFAULT,
            'create_time' => date('Y-m-d H:i:s')
        ];
       return RedPackModel::getInstance()->create($redPackData);
   }


    /**
     * 激活红包，产生子红包，并且添加队列用于抢
     * @param $redPackId
     * @param $uid
     * @return int
     * @throws ServiceException
     */
   public static function activeRedPack($redPackId, $uid, $momentId = ''){
       //判断红包是否存在
       $redPackInfo = RedPackModel::getInstance()->getInfo($redPackId);
       if(!$redPackInfo){
           throw new ServiceException(MessageCode::RED_PACK_NOT_EXIST);
       }
        //判断红包所属
       if($redPackInfo->uid != $uid){
           throw new ServiceException(MessageCode::RED_PACK_UID_NOT_MATCH);
       }
        //判断红包状态
       if($redPackInfo->status != '0'){
           throw new ServiceException(MessageCode::RED_PACK_STATUS_ERROR);
       }

       $makePatch = self::makeRedPackPatch($redPackId, $redPackInfo->coin, $redPackInfo->amount, $redPackInfo->type, $redPackInfo->number);
       if(!$makePatch){
           throw new ServiceException(MessageCode::RED_PACK_PATCH_ERROR);
       }

       $updateRedPack = [
           'moment_id' => $momentId,
           'status' => self::STATUS_ACTIVE
       ];

        return RedPackModel::getInstance()->update($updateRedPack, $redPackId);
   }


    /**
     * 计算每个子红包的金额
     * @param $amount
     * @param $type
     * @param $number
     * @return array
     */
   public static function makeRedPackPatch($redPackId, $coin, $amount, $type, $number){
       $redPackKey = RedisKey::getRedPackQueueKey($redPackId);
        if($type == self::TYPE_AVG){
            $avg = bcdiv($amount, $number, 8);
            $time = date('Y-m-d H:i:s');
            for ($i = 0;  $i < $number ; $i++){
                $patch = [
                    'red_pack_id' => $redPackId,
                    'coin' => $coin,
                    'amount' => $avg,
                    'create_time' =>  $time
                ];
                $patchId = RedPackPatchModel::getInstance()->create($patch);
                Redis::lpush($redPackKey, $patchId);
            }

        } elseif($type == self::TYPE_RANDOM){

        }

        return true;
   }

   public static function grabRedPack($uid, $redPackId, $event = '', $desc = ''){
       //添加抢红包锁
       $key = $uid . '_' . $redPackId;
       $lockKey = RedisKey::getRedPackLockKey($key);
       if(false == Redis::setnx($lockKey, '1')){
           throw new ServiceException(MessageCode::RED_PACK_UID_LOCKED);
       }
       Redis::expire($lockKey, 1);

       //判断红包状态
       $redPack = RedPackModel::getInstance()->getInfo($redPackId);
       if(!$redPack || $redPack->status != self::STATUS_ACTIVE){
           throw new ServiceException(MessageCode::RED_PACK_STATUS_ERROR);
       }

       //判断红包是否已过期
       if(time() - strtotime($redPack->create_time) > 86400){
           throw new ServiceException(MessageCode::RED_PACK_TIMEOUT);
       }

       //判断用户是否已经领过红包
       if($info = RedPackPatchModel::getInstance()->checkUserGrabedRedPack($redPackId, $uid)){
           throw new ServiceException(MessageCode::RED_PACK_ALREADY_GRAB);
       }

       $patchKey = RedisKey::getRedPackQueueKey($redPackId);
       $patchId = Redis::rpop($patchKey);
       if(!$patchId){
           throw new ServiceException(MessageCode::RED_PACK_OVER);
       }

       $patchInfo = RedPackPatchModel::getInstance()->getInfo($patchId);
       if($patchInfo->status != self::PATCH_STATUS_DEFAULT){
           throw new ServiceException(MessageCode::RED_PACK_OVER);
       }

       if(RedPackPatchModel::getInstance()->grabPatch($patchId, $uid)){
           //更新主红包红包
           RedPackModel::getInstance()->addGrabPatch($redPackId, $patchInfo->amount);
           //检查所有红包是否已被抢完
           if(RedPackPatchModel::getInstance()->checkAllPatchGrab($redPackId)){
               RedPackModel::getInstance()->update(['status' => RedPackService::STATUS_FINISH], $redPackId);
           }
           //更新用户资产
           $assetData = [
               'coin' => $patchInfo->coin,
               'number' => $patchInfo->amount,
               'event' => $event,
               'desc' => $desc
           ];
           if(AssetService::addAsset($assetData, $uid)){
               Redis::del($lockKey); //删除用户锁
               return [
                   'amount' => $patchInfo->amount,
                   'coin'   => $patchInfo->coin
               ];
           }
       }
       return [
           'amount' => 0.00000000,
           'coin'   => $patchInfo->coin
       ];
   }


    /**
     * 取消红包
     * @param $data
     * @return bool
     * @throws ServiceException
     */
   public static function cancelRedPack($data){
       if(empty($data['token']) || empty($data['red_pack_id'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       //判断红包是否存在
       $redPack = RedPackModel::getInstance()->getInfo($data['red_pack_id']);
       if(!$redPack){
           throw new ServiceException(MessageCode::RED_PACK_NOT_EXIST);
       }

       //判断红包是否属于登陆用户
       if($redPack->uid != $uid){
           throw new ServiceException(MessageCode::RED_PACK_UID_NOT_MATCH);
       }

       //判断红包状态是否为，未激活，其他状态不可取消
       if($redPack->status  != self::STATUS_DEFAULT){
           throw new ServiceException(MessageCode::RED_PACK_STATUS_ERROR);
       }

       //更新红包状态
       $updateStatus = [
           'status' => self::STATUS_CANCEL
       ];
       if(RedPackModel::getInstance()->update($updateStatus, $redPack->id)){
           $assetData = [
               'coin' => $redPack->coin,
               'number' => $redPack->amount,
               'event' => AssetService::EVENT_RED_PACK,
               'desc' => MessageCode::KEY_MOMENT_RED_PACK_CANCEL
           ];
           return AssetService::addAsset($assetData, $uid);
       }

       return false;
   }


    /**
     * 获取用户发出红包明细
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
   public static function list($data){
       if(empty($data['token'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       if(!empty($data['coin'])){
           return  RedPackModel::getInstance()->getMyRedPackList($uid, $data['coin']);
       }

       return  RedPackModel::getInstance()->getMyRedPackList($uid);
   }

    /**
     * h
     * @param $data
     * @return \Illuminate\Support\Collection
     * @throws ServiceException
     */
   public static function grabList($data){
       if(empty($data['token'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       if(!empty($data['coin'])){
           return  RedPackPatchModel::getInstance()->getGrabList($uid, $data['coin']);
       }

       return RedPackPatchModel::getInstance()->getGrabList($uid);
   }


    /**
     * 获取用户红包流水明细
     * @param $data
     * @return \Illuminate\Support\Collection
     * @throws ServiceException
     */
   public static function flow($data){
       if(empty($data['token'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       $result = UserAssetLogModel::getInstance()->getRedPackLog($uid);
       if(!empty($result)){
           foreach ($result as $key => $item){
               $result[$key]->desc = MessageCode::getTranslate($item->desc);
           }
       }
        return $result;
   }


    /**
     * 获取用户红包列表
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
    public static function coinSum($data){
        if(empty($data['token']) || empty($data['type'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $result = [];
        if($data['type'] == self::RED_PACK_SEND){
            $sum = RedPackModel::getInstance()->getRedPackSendSum($uid);
            if(!empty($sum)){
                foreach ($sum as $coin){
                    $coinInfo = AssetService::getCoinInfo($coin->coin);
                    $result[] = [
                        'icon' => $coinInfo['icon'],
                        'full_name' => $coinInfo['full_name'],
                        'coin' => $coin->coin,
                        'sum'  => bcsub($coin->amount, $coin->return_amount, 8)
                    ];
                }
            }
        } elseif($data['type'] == self::RED_PACK_RECEIVE){
            $sum = RedPackPatchModel::getInstance()->getRedPackPatchReceivedSum($uid);
            if(!empty($sum)){
                foreach ($sum as $coin){
                    $coinInfo = AssetService::getCoinInfo($coin->coin);
                    $result[] = [
                        'icon' => $coinInfo['icon'],
                        'full_name' => $coinInfo['full_name'],
                        'coin' => $coin->coin,
                        'sum'  => $coin->amount
                    ];
                }
            }
        }
        return $result;
    }



    /**
     * 获取红包详情
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     * @throws ServiceException
     */
   public static function info($data){
       if(empty($data['red_pack_id'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       $info = RedPackModel::getInstance()->getInfo($data['red_pack_id']);
       if(!$info){
           throw new ServiceException(MessageCode::RED_PACK_NOT_EXIST);
       }

       //校验主红包和子红包数据是否一致
       if($info->status != self::STATUS_DEFAULT){
           $patchList = RedPackPatchModel::getInstance()->getPatchList($data['red_pack_id']);
           $receiver = 0;
           $receivedAmount = 0;
           foreach ($patchList as $patch){
               if($patch->status == self::PATCH_STATUS_USED){
                   $receivedAmount += $patch->amount;
                   $receiver++;
               }
           }
           $info->receiver_count = $receiver;
           $info->received_amount = $receivedAmount;
       }

        return $info;
   }



    /**
     * 获取红包配置
     * @param $data
     * @return array
     */
   public static function getRedPackConfig($data){
       if(empty($data['token'])){
           throw new ServiceException(MessageCode::PARAMS_ERROR);
       }

       //检查用户Token是否有效
       $uid = AccountService::checkUserLogin($data);
       if(!$uid){
           throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
       }

       return [
           'max_num' => ConfigService::getConfig(ConfigService::RED_PACK_MAX_NUM)
       ];
   }


}
