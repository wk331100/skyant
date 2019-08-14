<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class UserLoginModel extends DB
{

    protected $table = 'user_login';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
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
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function create($insertData){
        return DB::table($this->table)->insertGetId($insertData);
    }

    public function update($updateData, $id){
        return DB::table($this->table)->where('id', $id)->update($updateData);
    }

    public function delete($id){
        return DB::table($this->table)->where('id', $id)->delete();
    }

    public function multiUpdate($updateData, $ids){
        return DB::table($this->table)->whereIn('id', $ids)->update($updateData);
    }
    public function multiDelete($ids){
        return DB::table($this->table)->whereIn('id', $ids)->delete();
    }
    



}