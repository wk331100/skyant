<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class SmsPrefixModel extends DB
{
    protected $table = 'gms_sms_prefix';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    
    
    public function checkPrefix($prefix){
        $where = [
            'iso_code' => $prefix,
            'enabled'  => 1 
        ];
        return DB::table($this->table)->where($where)->first();
    }

    public function getNameByCode($code){
        $where = [
            'country_code' => $code,
        ];
        return DB::table($this->table)->where($where)->first();
    }


    //===============以下为基本增删改查====================
    public function getList($enabled = ''){
        if($enabled){
            return DB::table($this->table)->where('enabled' , '1')->get();
        }
        return DB::table($this->table)->get();
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