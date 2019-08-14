<?php

namespace App\Models;

use App\Services\RedPackService;
use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class RedPackModel extends DB
{

    protected $table = 'red_pack';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function addGrabPatch($redPackId, $receivedAmount){
        $where = [
            'id' => $redPackId
        ];
        DB::table($this->table)->where($where)->increment('received_amount', $receivedAmount);
        return DB::table($this->table)->where($where)->increment('receiver_count');
    }


    public function getMyRedPackList($uid, $coin = ''){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $where = [
            'uid'=> $uid
        ];
        if($coin){
            $where['coin'] = $coin;
        }
        return DB::table($this->table)->where($where)->orderBy('create_time','desc')->paginate($pageSize)->getCollection()->toArray();
    }

    public function getRedPackSendSum($uid){
        return DB::table($this->table)->where('uid', $uid)->selectRaw("coin,sum(amount) as amount, sum(return_amount) as return_amount")->groupBy('coin')->get();
    }



    public function getTimeOutRedPack(){
        $dateTime = date('Y-m-d H:i:s', time() - 86400);
        return DB::table($this->table)->where('create_time', '<=' , $dateTime)->whereIn('status', [RedPackService::STATUS_DEFAULT, RedPackService::STATUS_ACTIVE])->get();
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