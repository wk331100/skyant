<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class UserTransferModel extends DB
{

    protected $table = 'user_transfer';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getInfoByTxid($txid){
        return DB::table($this->table)->where('txid', $txid)->first();
    }

    public function checkTxidDuplicated($txid, $coin){
        $where = [
            'txid' => $txid,
            'coin' => $coin
        ];
        if(DB::table($this->table)->where($where)->first()){
            return true;
        }
        return false;
    }

    public function getTransferList($uid, $type, $coin = ''){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $where = [
            'uid' => $uid,
            'type' => $type
        ];

        if(!empty($coin)){
            $where['coin'] = $coin;
        }
        $list = DB::table($this->table)->where($where)->orderBy('create_time','desc')->paginate($pageSize)->getCollection()->toArray();
        return $list;
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