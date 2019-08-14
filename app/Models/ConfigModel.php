<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;


class ConfigModel extends DB
{

    protected $table = 'app_config';
    private $_pk = 'id';
    private   static $_instance;


    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function getValueByKey($key){
        $where = [
            'key' => $key,
            'enabled' => '1'
        ];
        return DB::table($this->table)->where($where)->first();
    }

    public function getList($all = false)
    {
        $model = DB::table($this->table);
        if(!$all){
            $model->where('enabled', 1);
        }
        return $model->get()->toArray();
    }

    public function getInfo($id , $type = true)
    {   
        if ($type) {
            return (array)DB::table($this->table)->where('id', $id)->first();
        }
        return DB::table($this->table)->where($this->_pk, $id)->first();
    }

    public function create($insertData)
    {
        return DB::table($this->table)->insertGetId($insertData);
    }

    public function update($updateData, $id)
    {
        return DB::table($this->table)->where($this->_pk, $id)->update($updateData);
    }

    public function delete($id)
    {
        return DB::table($this->table)->where($this->_pk, $id)->delete();
    }

    public function multiUpdate($updateData, $ids)
    {
        return DB::table($this->table)->whereIn($this->_pk, $ids)->update($updateData);
    }

    public function multiDelete($ids)
    {
        return DB::table($this->table)->whereIn($this->_pk, $ids)->delete();
    }

    
}
