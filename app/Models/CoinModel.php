<?php

namespace App\Models;

use App\Services\AssetService;
use Illuminate\Support\Facades\DB;

class CoinModel extends DB
{

    protected $table = 'coin';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getCoinByName($coin){
        return DB::table($this->table)->where('name', $coin)->first();
    }


    public function getRedPackCoin(){
        $where = [
            'enabled' => '1',
            'red_pack_enabled' => '1'
        ];
        return DB::table($this->table)->where($where)->orderBy('sort','desc')->get();
    }

    public function getEnabledCoinArr(){
        $list = $this->getList();
        $coin = [];
        if(!empty($list)){
            foreach ($list as $item){
                $coin[] = $item->name;
            }
        }
        return $coin;
    }

    public function checkCoinEnabled($coin){
        $where = [
            'enabled' => '1',
            'name' => $coin
        ];
        if(DB::table($this->table)->where($where)->first()){
            return true;
        }
        return false;
    }

    public function getTransferCoinList($type){
        $where = [
            'enabled' => '1',
        ];

        if($type == AssetService::TYPE_TRANSFER_IN){
            $where['transfer_in_enable'] = '1';
        } elseif($type == AssetService::TYPE_TRANSFER_OUT){
            $where['transfer_out_enable'] = '1';
        }

        $column = [
            'name', 'full_name','icon','transfer_in_enable','transfer_out_enable','enabled','transfer_fee',
            'transfer_out_min','block_confirm','red_pack_min','red_pack_enabled','transfer_out_max'
        ];
        return DB::table($this->table)->where($where)->select($column)->orderBy('sort','desc')->get();
    }


    //===============以下为基本增删改查====================
    public function getList($all = false){
        if($all){
            return DB::table($this->table)->get();
        }
        return DB::table($this->table)->where('enabled', '1')->orderBy('sort','desc')->get();
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