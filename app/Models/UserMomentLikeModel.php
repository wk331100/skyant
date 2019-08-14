<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class UserMomentLikeModel extends DB
{

    protected $table = 'user_moment_like';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function checkIsLiked($uid, $momentId){
        $where = [
            'uid' => $uid,
            'moment_id' => $momentId
        ];
        return DB::table($this->table)->where($where)->first();
    }

    public function cancelLike($uid, $momentId){
        $where = [
            'uid' => $uid,
            'moment_id' => $momentId
        ];
        return DB::table($this->table)->where($where)->delete();
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