<?php
namespace App\Services;


use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Models\CoinModel;
use App\Models\UserTransferModel;
use App\Models\UserWalletAddressModel;
use App\Models\UserAssetLogModel;
use App\Services\AccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WalletService{

    

    const STATUS_WAIT = '1';
    const STATUS_CONFIRM = '2';
    const STATUS_SUCCESS = '3';
    const STATUS_CANCEL  = '4';
    const STATUS_VERIFY  = '5';

    const TYPE_IN = '1';
    const TYPE_OUT = '2';



    public static function transferIn($data){
        
        $user = UserWalletAddressModel::getInstance()->getUidByAddress($data['coin'],$data['address'] );
        if(empty($user)){
            throw new ServiceException(MessageCode::WALLET_ADDRESS_ERROR);
        }

        //检查TXID是否存在
        if(UserTransferModel::getInstance()->checkTxidDuplicated($data['txid'], $data['coin'])){
            throw new ServiceException(MessageCode::TXID_DUPLICATED);
        }

        $insertData = [
            'uid' => $user->uid,
            'coin' => $data['coin'],
            'number' => $data['number'],
            'address' => $data['address'],
            'txid' => $data['txid'],
            'type' => self::TYPE_IN,
            'desc' => MessageCode::KEY_TRANSFER_IN,
            'confirm' => $data['confirm'] ?? 0,
            'status' => self::STATUS_CONFIRM,
            'bak' => isset($data['bak']) ? $data['bak'] : '',
            'create_time' => date('Y-m-d H:i:s',$data['timestamp']),
        ];

        return UserTransferModel::getInstance()->create($insertData);
    }


    public static function confirm($data){

        //检查txid对应的记录状态是否正确
        $transfer = UserTransferModel::getInstance()->getInfoByTxid($data['txid']);
        if(!$transfer || !in_array($transfer->status,[self::STATUS_WAIT, self::STATUS_CONFIRM]) || $transfer->coin != $data['coin']
            || $transfer->number != $data['number'] || $transfer->address != $data['address'] ){
            $logMsg =  $_SERVER['REMOTE_ADDR'] . ' - /api/wallet/confirm - request:' . json_encode($data) . ' - response:' . json_encode(MessageCode::getMessageByCode(MessageCode::TRANSFER_CONFIRM_FAILED));
            Log::channel('wallet')->info($logMsg);
            throw new ServiceException(MessageCode::TRANSFER_CONFIRM_FAILED);
        }

        //判断确认数是否符合条件
        $coin =  CoinModel::getInstance()->getCoinByName($data['coin']);

        if($data['confirm'] >= $coin->block_confirm){
            $updateData = [
                'confirm' => $data['confirm'],
                'status'  => self::STATUS_SUCCESS
            ];
        } else {
            $updateData = [
                'confirm' => $data['confirm'],
                'status'  => self::STATUS_CONFIRM
            ];
        }

        $updated = UserTransferModel::getInstance()->update($updateData, $transfer->id);
        //当确认数符合条件执行更新资产
        if($updated && ($data['confirm'] >= $coin->block_confirm)){
            //更新用户资产
            $data['event'] = AssetService::EVENT_TRANSFER_IN;
            $data['desc'] = MessageCode::KEY_TRANSFER_IN;
            return AssetService::addAsset($data, $transfer->uid);
        }
        return $updated;

    }

    public static function out($data)
    {       
        $where = [
            'uid'=>$data['uid'],
            'coin'=>$data['coin'],
            'number'=>$data['number'],
            'address'=>$data['address'],
            'status'=>self::STATUS_WAIT,
        ];

        $transfer = DB::table('user_transfer')->where(
            $where)->first();
        if (!$transfer) {
            return false;
        }
        DB::beginTransaction();
        if (!DB::table('user_transfer')->where(['id'=>$transfer->id])->update(['status'=>self::STATUS_SUCCESS,'txid'=>$data['txid']])) {
            DB::rollBack();
            return false;
        }
        DB::table('user_coin_'.$data['coin'])->where(['uid'=>$data['uid']])->lockForUpdate()->first();
        $increAsset = DB::table('user_coin_'.$data['coin'])->where(['uid'=>$data['uid']])->decrement('balance_lock', $data['number']);

        $log = [
            'uid' => $data['uid'],
            'type' => '2',
            'event' => 'transfer_out',
            'desc' => 'transfer_out',
            'coin' => $data['coin'],
            'number' => $data['number'],
            'create_time' => date('Y-m-d H:i:s'),
        ];

        $addLog = UserAssetLogModel::getInstance()->create($log);
        if (!$increAsset || !$addLog){
            DB::rollBack();
            return false;
        }
        DB::commit();
        return true;
    }   

    /*
    * 取消提币  
    */
    public static function cancel($data)
    {
        if (!$data['uid'] = AccountService::checkUserLogin($data)) {
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        DB::beginTransaction();
        if (!$transfer = DB::table('user_transfer')->where(['uid'=>$data['uid'],'id'=>$data[
            'id']])->lockForUpdate()->first()) {
            DB::rollBack();
            throw new ServiceException(MessageCode::PARAMS_ERROR);   
        }

        if ($transfer->status == 3) {
            DB::rollBack();
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        $decreAsset = DB::table('user_coin_'.$transfer->coin)->where(['uid'=>$data['uid']])->increment('balance', $transfer->number);
        $increAsset = DB::table('user_coin_'.$transfer->coin)->where(['uid'=>$data['uid']])->decrement('balance_lock', $transfer->number);

        if (!$decreAsset || !$increAsset || !DB::table('user_transfer')->where(['id'=>$transfer->id])->update(['status'=>4])) {
            DB::rollBack();
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        DB::commit();
        return true;
    }
}