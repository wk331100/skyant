<?php

namespace App\Models;

use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;

class UserFollowModel extends DB
{

    protected $table = 'user_follow';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function checkFollowed($uid, $followedUid){
        $where = [
            'uid' => $uid,
            'followed_uid' => $followedUid
        ];
        return DB::table($this->table)->where($where)->first();
    }


    public function unFollowed($uid, $followedUid){
        $where = [
            'uid' => $uid,
            'followed_uid' => $followedUid
        ];
        return DB::table($this->table)->where($where)->delete();
    }

    public function unBothFollowStatus($uid, $followedUid){
        $updateData = [
            'followed_both' => '0'
        ];
        $where = [
            'uid' => $uid,
            'followed_uid' => $followedUid
        ];
        return DB::table($this->table)->where($where)->update($updateData);
    }

    public function getFollowList($uid, $all = false){
        $where = [
            'uid' => $uid
        ];
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        if($all){
            return DB::table($this->table)->where($where)->orderBy('id','desc')->get();
        } else {
            return DB::table($this->table)->where($where)->orderBy('id','desc')->paginate($pageSize);
        }
    }

    public function getFansList($uid, $all = false){
        $where = [
            'followed_uid' => $uid
        ];
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        if($all){
            return DB::table($this->table)->where($where)->orderBy('id','desc')->get();
        } else {
            return DB::table($this->table)->where($where)->orderBy('id','desc')->paginate($pageSize);
        }

    }

    public function getFansCount($uidArr){
        return DB::table($this->table)->whereIn('followed_uid', $uidArr)->groupBy('followed_uid')->select(DB::raw('count(uid) as count, followed_uid'))->get();
    }


    public function getFollowCount($uidArr){
        return DB::table($this->table)->whereIn('uid', $uidArr)->groupBy('uid')->count();
    }

    public function getFollowUidGroup(){
        return DB::table($this->table)->groupBy('uid')->select('uid')->get();
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