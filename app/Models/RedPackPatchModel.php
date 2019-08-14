<?php

namespace App\Models;

use App\Services\RedPackService;
use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class RedPackPatchModel extends DB
{

    protected $table = 'red_pack_patch';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    //批量插入
    public function createPatch($patchList){
        return DB::table($this->table)->insert($patchList);
    }

    public function checkUserGrabedRedPack($redPackId,$uid){
        $where = [
            'red_pack_id' => $redPackId,
            'receiver_uid' => $uid
        ];
        return  DB::table($this->table)->where($where)->first();
    }


    public function grabPatch($patchId, $uid){
        $updateData  = [
            'receiver_uid' => $uid,
            'status' => RedPackService::PATCH_STATUS_USED,
            'receive_time' => date('Y-m-d H:i:s')
        ];

        return DB::table($this->table)->where($this->_pk, $patchId)->update($updateData);
    }

    public function checkAllPatchGrab($redPackId){
        $where = [
            'red_pack_id' => $redPackId,
            'status' => RedPackService::PATCH_STATUS_USED
        ];
        $freePatch = DB::table($this->table)->where($where)->get();
        if(empty($freePatch)){
            return true;
        }
        return false;
    }

    public function cancelPatch($redPackId){
        $where = [
            'red_pack_id' => $redPackId,
        ];
        $update = [
            'status' => RedPackService::PATCH_STATUS_CANCEL
        ];
        DB::table($this->table)->where($where)->update($update);
    }

    public function getPatchList($redPackId){
        return DB::table($this->table)->where('red_pack_id', $redPackId)->get();
    }

    public function getRedPackPatchReceivedSum($uid){
        $where = [
            'receiver_uid' => $uid,
            'status' => '1'
        ];
        return DB::table($this->table)->where($where)->selectRaw("coin,sum(amount) as amount")->groupBy('coin')->get();
    }

    public function getGrabList($uid, $coin = ''){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $where = [
            'receiver_uid' => $uid,
            'status' => '1'
        ];
        if($coin){
            $where['coin'] = $coin;
        }
        return DB::table($this->table)->where($where)->orderBy('receive_time', 'desc')->paginate($pageSize)->getCollection()->toArray();
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