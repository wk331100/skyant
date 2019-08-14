<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;


class AnnouncementModel extends DB
{

    protected $table = 'announcement';
    private $_pk = 'id';
    private   static $_instance;


    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function list(int $limit = 10 , int $page = 1):array
    {   
		$res =  DB::table($this->table)->select('title','address_source','id','create_time','exchange')
		        ->where('is_del',0)
		        ->where('status',1)
		        ->orderByDesc('create_time')
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
