<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class AppVersionModel extends DB
{

    protected $table = 'app_version';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getCurrentVersion($platform){
        $where = [
            'type' => $platform,
            'enabled' => '1'
        ];
        return DB::table($this->table)->where($where)->orderBy('id','desc')->first();
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