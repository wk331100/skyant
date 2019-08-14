<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class UserInfoModel extends DB
{

    protected $table = 'user_info';
    private $_pk = 'uid';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getUserListByUids($uidArr, $filed =  []){
        if($filed){
            return DB::table($this->table)->whereIn($this->_pk, $uidArr)->select($filed)->get();
        }

        return DB::table($this->table)->whereIn($this->_pk, $uidArr)->get();
    }

    public function checkNickExist($nick){
        if(DB::table($this->table)->where('nick', $nick)->first()){
            return true;
        }
        return false;
    }

    public function searchUser($keyword){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        return DB::table($this->table)->where('nick', 'like', "%{$keyword}%")->orderBy('id','desc')->paginate($pageSize);
    }

    public function isAuth($code)
    {
        return DB::table($this->table)->where(['id_code'=>$code,'is_verified'=>1])->count();
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
    public function getInfoValue(int $uid ,string $field = 'nick')
    {
        return DB::table($this->table)->where('uid',$uid)->value($field);
    }



}