<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Libs\Curl;
use App\Libs\Util;
use App\Services\{WalletService};

class UserCoinModel extends DB
{

    protected $coin = '';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance($coin){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        self::$_instance->table =  'user_coin_' . $coin;
        self::$_instance->coin = $coin;
        return self::$_instance;
    }

    public function deductAsset($uid,$number, $event = '', $desc = ''){
        $where = [
            'uid' => $uid
        ];

        DB::beginTransaction();
        DB::table($this->table)->where($where)->lockForUpdate()->first();
        $deductAsset = DB::table($this->table)->where($where)->decrement('balance', $number);
        $log = [
            'uid' => $uid,
            'type' => '2',
            'event' => $event,
            'desc' => $desc,
            'coin' => $this->coin,
            'number' => $number,
            'create_time' => date('Y-m-d H:i:s')
        ];

        $addLog = UserAssetLogModel::getInstance()->create($log);

        if(!$deductAsset || !$addLog){
            DB::rollBack();
            throw new ServiceException(MessageCode::ASSET_UPDATE_ERROR);
        }
        DB::commit();
        return true;
    }

    public function outAsset($uid,$address,$number, $desc = '')
    {   
        $where = [
            'uid' => $uid
        ];
        $number = bcadd($number, 0 , 8);
        DB::beginTransaction();
        DB::table($this->table)->where($where)->lockForUpdate()->first();
        $decreAsset = DB::table($this->table)->where($where)->decrement('balance', $number);
        $increAsset = DB::table($this->table)->where($where)->increment('balance_lock', $number);
        $tmp = DB::table('coin')->where(['name'=>$this->coin])->first();

        $status = WalletService::STATUS_WAIT;
        if ($number > $tmp->transfer_verify_limit) {
            $status = WalletService::STATUS_VERIFY;
        }


        $create_time = date('Y-m-d H:i:s');
        $transfer = DB::table('user_transfer')->insertGetId([
            'uid' => $uid,
            'coin' => $this->coin,
            'number' => $number,
            'fee_number'=>$tmp->fee_number,
            'address'=>$address,
            'type' => WalletService::TYPE_OUT,
            'desc' => MessageCode::KEY_TRANSFER_OUT,
            'status' => $status,
            'bak' => $desc,
            'create_time' => $create_time,

        ]);
        
        if(!$decreAsset || !$transfer || !$increAsset){
            DB::rollBack();
            throw new ServiceException(MessageCode::ASSET_UPDATE_ERROR);
        }
        DB::commit();
        if (WalletService::STATUS_VERIFY != $status) {
            (new Curl)->setTimeout(2)->setParams([
            'uid' => $uid,
            'coin'=>$this->coin,
            'number'=>$number,
            'address'=>$address,
            'create_time'=>strtotime($create_time),
            'create_ip'    => Util::getIp(),
            ])->post(env('WALLET_URL').'/index/outcoin');
        }
        return true;
    }


    public function addAsset($uid,$number, $event = '', $desc = ''){
        $where = [
            'uid' => $uid
        ];

        DB::beginTransaction();
        $userCoin = DB::table($this->table)->where($where)->first();
        $time = date('Y-m-d H:i:s');
        if($userCoin){
            DB::table($this->table)->where($where)->lockForUpdate()->first();
            $addAsset = DB::table($this->table)->where($where)->increment('balance', $number);
        } else {
            $createData = [
                'uid' => $uid,
                'coin' => $this->coin,
                'balance' =>  $number,
                'create_time' => $time
            ];
            $addAsset = DB::table($this->table)->where($where)->insert($createData);
        }

        $log = [
            'uid' => $uid,
            'type' => '1',
            'event' => $event,
            'desc' => $desc,
            'coin' => $this->coin,
            'number' => $number,
            'create_time' => $time
        ];

        $addLog = UserAssetLogModel::getInstance()->create($log);

        if(!$addAsset || !$addLog){
            DB::rollBack();
            throw new ServiceException(MessageCode::ASSET_UPDATE_ERROR);
        }
        DB::commit();
        return true;
    }

    /**
     * 获取用户资产
     * @param $uid
     * @return array
     */
    public function getAsset($uid){
        $where = [
            'uid' => $uid,
            'coin' => $this->coin
        ];
        $data = DB::table($this->table)->where($where)->first();
        if($data){
            $balance = $data->balance;
            $balanceLock = $data->balance_lock;
        } else {
            $balance = $balanceLock = '0.00000000';
        }

        return [
            'uid' => $uid,
            'coin' => $this->coin,
            'balance' => bcadd($balance,0,8),
            'balance_lock' => bcadd($balanceLock,0,8),
            'balance_available' => bcadd($balance,0,8),
        ];
    }





    //===============以下为基本增删改查====================
    public function getList(){
        return DB::table($this->table)->get();
    }

    public function getInfo($id){
        return DB::table($this->table)->where($this->_pk, $id)->first();
    }

    public function create($insertData){
        return DB::table($this->table)->insertGetId($insertData);
    }

    public function update($updateData, $id){
        return DB::table($this->table)->where($this->_pk, $id)->update($updateData);
    }

    public function delete($id){
        return DB::table($this->table)->where($this->_pk, $id)->delete();
    }

    public function multiUpdate($updateData, $ids){
        return DB::table($this->table)->whereIn($this->_pk, $ids)->update($updateData);
    }
    public function multiDelete($ids){
        return DB::table($this->table)->whereIn($this->_pk, $ids)->delete();
    }
    



}