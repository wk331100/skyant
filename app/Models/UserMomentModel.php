<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Services\ConfigService;

class UserMomentModel extends DB
{

    protected $table = 'user_moment';
    private $_pk = 'id';

    private static $_instance;

    public static function getInstance(){
        if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function momentCount($uid){
        $where = [
            'uid' => $uid,
            'is_deleted' => '0'
        ];
        return DB::table($this->table)->where($where)->count();
    }

    public function increaseMomentCount($momentId, $event){
        switch ($event){
            case 'like' : $column = 'like_count'; break;
            case 'comment' : $column = 'comment_count'; break;
            case 'share' : $column = 'share_count'; break;
            case 'view' : $column = 'view_count'; break;
            default: $column = false;
        }

        if($column){
            return DB::table($this->table)->where($this->_pk, $momentId)->increment($column);
        }
        return false;
    }

    public function decreaseMomentCount($momentId, $event){
        switch ($event){
            case 'like' : $column = 'like_count'; break;
            case 'comment' : $column = 'comment_count'; break;
            case 'share' : $column = 'share_count'; break;
            case 'view' : $column = 'view_count'; break;
            default: $column = false;
        }
        if($column){
            return DB::table($this->table)->where($this->_pk, $momentId)->decrement($column);
        }
        return false;
    }

    public function getMomentList($uid = ''){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $where = [
            'is_deleted' => '0',
        ];
        if($uid){
            $where['uid'] = $uid;
        }
        return DB::table($this->table)->where($where)->orderBy('is_top','desc')->orderBy('create_time','desc')->paginate($pageSize);
    }

    public function getFollowMomentList($uidArr){
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $where = [
            'is_deleted' => '0'
        ];
        return DB::table($this->table)->where($where)->whereIn('uid', $uidArr)->orderBy('create_time','desc')->paginate($pageSize);
    }

    public function getRecommendMoment(){
        $recommendLimit = ConfigService::getConfig(ConfigService::RECOMMEND_MOMENT_LIMIT);
        $conditionShare = ConfigService::getConfig(ConfigService::CONDITION_SHARE);
        $conditionComment = ConfigService::getConfig(ConfigService::CONDITION_COMMENT);
        $conditionLike = ConfigService::getConfig(ConfigService::CONDITION_LIKE);
        return DB::table($this->table)->whereRaw("(like_count >= {$conditionLike}  or comment_count >= {$conditionComment} or share_count >= {$conditionShare} or is_recommend = '1') and is_deleted = '0' ")
            ->orderBy('id','desc')->paginate($recommendLimit);
    }



    //===============以下为基本增删改查====================


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
