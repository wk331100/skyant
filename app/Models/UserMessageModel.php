<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class UserMessageModel extends DB
{

    protected $table = 'user_message';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function readAll($uid, $type = ''){
        $where = [
            'uid' => $uid
        ];
        if($type){
            $where['type'] = $type;
        }
        $updateData = [
            'is_read' => '1'
        ];
        return DB::table($this->table)->where($where)->update($updateData);
    }

    public function readMulti($uid, $typeArr){
        $where = [
            'uid' => $uid
        ];

        $updateData = [
            'is_read' => '1'
        ];
        return DB::table($this->table)->where($where)->whereIn('type', $typeArr)->update($updateData);
    }


    public function readMessage($messageId){
        $updateData = [
            'is_read' => '1'
        ];
        $where = [
            'id' => $messageId
        ];
        return DB::table($this->table)->where($where)->update($updateData);
    }

    public function getNewUserMessageCount($uid, $typeArr = []){
        $where = [
            'uid' => $uid,
            'is_read' => '0',
        ];

        $DB = DB::table($this->table)->where($where);

        if(!empty($typeArr)){
            $DB->whereIn('type', $typeArr);
        }
        return $DB->select(DB::raw('count(id) as count, type'))->groupBy('type')->get();
    }

    //===============以下为基本增删改查====================
    public function getList($typeArr, $uid){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        return DB::table($this->table)->whereIn('type', $typeArr)->where('uid', $uid)->orderBy('create_time','desc')->paginate($pageSize);
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