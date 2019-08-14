<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;


class BannerModel extends DB
{

    protected $table = 'banner';
    private $_pk = 'id';
    private   static $_instance;


    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function list(int $limit = 10 , int $page = 1 , int $type):array
    {   
        
		$res =  DB::table($this->table)
		        ->where('is_del',0)
                ->where('type' , $type)
		        ->orderByDesc('sort')
		        ->orderByDesc('id')
                ->offset($page == 1 ? 0 : ($limit * ($page - 1)))
		        ->limit($limit)
		        ->get()
		        ->toArray();
        if (!$res) {
            return [];
        }
        return array_map('get_object_vars', $res);   
    }
}
