<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class UserModel extends DB
{

    protected $table = 'user';
    private $_pk = 'uid';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getIpUserCount($ip){
        return DB::table($this->table)->where('create_ip', $ip)->count();
    }

    public function getUserInfoByUsername($username){
        return DB::table($this->table)->where('username', $username)->first();
    }

    public function getUserInfoByPhone($prefix, $phone){
        $where = [
            'prefix' => $prefix,
            'phone'  => $phone
        ];
        return DB::table($this->table)->where($where)->first();
    }

    public function getUserInfoByEmail($email){
        return DB::table($this->table)->where('email', $email)->first();
    }

    public function getUidByInviteCode($inviteCode){
        return DB::table($this->table)->where('invite_code', $inviteCode)->first();
    }

    //===============以下为基本增删改查====================
    public function getList($status = ''){
        $model = DB::table($this->table);
        if($status){
            $model->where('status', $status);
        }
        return $model->get();
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