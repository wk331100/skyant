<?php
namespace App\Services;


use App\Libs\Util;
use App\Models\UserInfoModel;
use App\Models\UserMomentModel;
use App\Models\UserRecommendModel;
use Illuminate\Support\Facades\Redis;
use App\Libs\RedisKey;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Models\UserMomentLikeModel;

class RecommendService{


    /**
     * 获取推荐用户
     * @param $data
     * @return array|bool
     * @throws \App\Exceptions\ServiceException
     */
    public static function getRecommendUser($data){
        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
            $recommendUserData = self::getRecommendUserCache($uid);
        } else {
            $recommendUserData = self::getRecommendUserCache();
        }

        $adminRecommendUserData = UserRecommendModel::getInstance()->getList();

        $adminArr = [];
        if(!empty($adminRecommendUserData)){
            foreach ($adminRecommendUserData as $item){
                $adminArr[] = $item->uid;
            }
        }

        if(isset($uid)){
            $adminArr = self::diffAdminRecommendUser($adminArr, $uid);
        }

        //合并去重后台设置的关注列表
        $uidArr = array_unique(array_merge($recommendUserData, $adminArr));
        if(isset($uid)){
            //去除自身uid
            $uidArr = array_diff($uidArr, [$uid]);
        }


        //获取配置项每次取用户数量
        $userNumber = ConfigService::getConfig(ConfigService::RECOMMEND_USER_LIMIT);

        $randUidArr = Util::randArray($uidArr, $userNumber);
        $UserList = [];
        if(!empty($randUidArr)){
            $UserList = UserInfoModel::getInstance()->getUserListByUids($randUidArr, ['uid','nick','head_image']);
        }
        return $UserList;
    }


    public static function getRecommendMoment($data){
        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        }

        $list = UserMomentModel::getInstance()->getRecommendMoment();

        foreach ($list as $key => $item){
            $userInfo = AccountService::getUserInfoByUid($item->uid);
            $list[$key]->user_type = $userInfo['type'];
            $isLiked = false;
            $relation = FollowService::RELATION_NONE;

            if(isset($uid)){
                $isLiked = UserMomentLikeModel::getInstance()->checkIsLiked($uid, $item->id);
                $relation = FollowService::checkUserRelation($item->uid, $uid);
            }

            $list[$key]->is_liked = $isLiked ? true : false;
            $list[$key]->relation = $relation;
            $list[$key]->nick = $userInfo['info']['nick'];
            $list[$key]->head_image = $userInfo['info']['head_image'];

            if($item->parent_id != '0'){
                $parent = UserMomentModel::getInstance()->getInfo($item->parent_id);
                $parentUserInfo = AccountService::getUserInfoByUid($parent->uid);
                if($parent->is_deleted){
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'content' =>  '',
                        'image_1' => '',
                        'nick'    => '',
                        'head_image' => '',
                        'is_deleted' => $parent->is_deleted
                    ];
                } else {
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'content' =>  $parent->content,
                        'image_1' => $parent->image_1,
                        'nick'    => $parentUserInfo['info']['nick'],
                        'head_image' => $parentUserInfo['info']['head_image'],
                        'is_deleted' => $parent->is_deleted
                    ];
                }

            }
        }

        return $list->getCollection()->toArray();
    }


    /**
     * 比对去除后台设置的推荐用户中，已经关注的
     * @param $adminUser
     * @param $uid
     * @return array
     */
    public static function diffAdminRecommendUser($adminUser, $uid){
        $userFollowKey = RedisKey::getFollowListKey($uid);
        $adminUserKey = RedisKey::RECOMMEND_ADMIN_USER;

        //判断有序集合缓存是否存在
        $adminCache = Redis::zcard($adminUserKey);
        if(!empty($adminUser) && empty($adminCache)){
            Redis::zadd($adminUserKey,  array_flip($adminUser));
        }

        //取用户关注zset和后台设置的zset的交集
        $tmpKey = 'recommend:admin:tmp:' . $uid;
        Redis::zinterstore($tmpKey, 2, $userFollowKey, $adminUserKey);
        $mixData = Redis::zrange($tmpKey, 0, -1);
        Redis::del($tmpKey);

        return array_diff($adminUser, $mixData);
    }




    /**
     *  设置redis缓存用户推荐列表
     * @param $uid
     * @param $followdUid
     * @return mixed
     */
    public static function setRecommendUserCache($uid){
        $key = RedisKey::RECOMMEND_USER;
        $userBasicData = MomentService::getUserBasicData($uid);

        $fansRate = ConfigService::getConfig(ConfigService::RECOMMEND_FANS_RATE);
        $momentRate = ConfigService::getConfig(ConfigService::RECOMMEND_MOMENT_RATE);

        $score = $userBasicData['fans_count'] * $fansRate;
        $score += $userBasicData['moment_count'] * $momentRate;

        if(Redis::zscore($key, $uid)){
            Redis::zrem($key, $uid);
        }

        return Redis::zadd($key,  $score, $uid);
    }


    /**
     * 获取推荐用户缓存列表
     * @param string $uid
     * @return array
     */
    public static function getRecommendUserCache($uid = ''){
        $key = RedisKey::RECOMMEND_USER;
        $mixData = [];
        if($uid){
            $followKey = RedisKey::getFollowListKey($uid);
            $tmpKey = 'recommend:tmp:' . $uid;
            Redis::zinterstore($tmpKey, 2, $followKey, $key);
            $mixData = Redis::zrange($tmpKey, 0, -1);
            Redis::del($tmpKey);
        }

        $data = Redis::zrange($key, 0, -1);

        if(!empty($mixData)){
            $data = array_diff($data, $mixData);
        }

        return $data;
    }







    /**
     * 获取redis缓存用户关注列表
     * @param $uid
     * @return array|mixed
     */
    public static function getFollowCacheRange($uid, $start = 0, $stop = -1){
        $key = RedisKey::getFollowListKey($uid);
        return Redis::zrange($key, $start, $stop);
    }



}