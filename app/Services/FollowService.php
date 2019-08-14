<?php
namespace App\Services;


use App\Libs\Util;
use App\Models\UserFollowModel;
use App\Models\UserLoginModel;
use App\Models\UserModel;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Services\AccountService;
use Illuminate\Support\Facades\Redis;
use App\Libs\RedisKey;
use App\Models\UserInfoModel;
use App\Models\UserMessageModel;
use Illuminate\Support\Facades\DB;

class FollowService{
    const FOLLOWED_BOTH_TRUE = '1';
    const FOLLOWED_BOTH_FALSE = '0';

    const RELATION_FOLLOWED = 'followed';
    const RELATION_FANS     = 'fans';
    const RELATION_BOTH     = 'both';
    const RELATION_NONE     = 'none';
    const RELATION_SELF     = 'self';

    public static function follow($data){

        if(empty($data['token']) || empty($data['followed_uid'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查用户是否存在
        if(!UserModel::getInstance()->getInfo($data['followed_uid'])){
            throw new ServiceException(MessageCode::USER_NOT_EXIST);
        }
        //检查用户是否为自己
        if($uid == $data['followed_uid']){
            throw new ServiceException(MessageCode::USER_FOLLOWED_SELF);
        }

        //检查是否已关注
        if(UserFollowModel::getInstance()->checkFollowed($uid, $data['followed_uid'])){
            throw new ServiceException(MessageCode::USER_ALREADY_FOLLOWED);
        }

        //检查是否互相关注
        $followed = UserFollowModel::getInstance()->checkFollowed($data['followed_uid'],$uid);
        if($followed){
            $followedBoth = self::FOLLOWED_BOTH_TRUE;
            //设置redis用户互相关注
            $followBothValue = implode('_', array_sort(([$uid, $data['followed_uid']])));
            Redis::hset(RedisKey::FOLLOW_BOTH, $followBothValue, 1);
            UserFollowModel::getInstance()->update(['followed_both' => $followedBoth], $followed->id);
        } else {
            $followedBoth = self::FOLLOWED_BOTH_FALSE;
        }
        $insertData = [
            'uid'           =>$uid,
            'followed_uid'  => $data['followed_uid'],
            'followed_both' => $followedBoth,
            'create_time'   => date('Y-m-d H:i:s')
        ];

        self::setFollowCache($uid, $data['followed_uid']); //设置redis缓存用户关注
        self::setFansCache($uid, $data['followed_uid']); //设置粉丝缓存

        if(UserFollowModel::getInstance()->create($insertData)){
            $userInfo =  UserInfoModel::getInstance()->getInfo($uid);
            $messageData = [
                'uid' => $data['followed_uid'],
                'op_nick' =>  $userInfo->nick,
                'op_image' =>  $userInfo->head_image,
                'type' => MessageService::TYPE_FAN,
                'content' => MessageCode::KEY_FOLLOWED,
                'create_time' => $insertData['create_time']
            ];
            UserMessageModel::getInstance()->create($messageData);
        }
        return self::checkUserRelation($data['followed_uid'], $uid);
    }

    /**
     *  设置redis缓存用户关注列表
     * @param $uid
     * @param $followdUid
     * @return mixed
     */
    public static function setFollowCache($uid, $followedUid, $score = ''){
        $key = RedisKey::getFollowListKey($uid);
        if(!$score){
            $score = Util::createScore();
        }
        return Redis::zadd($key,  $score, $followedUid);
    }

    /**
     * 获取redis缓存关注用户
     * @param $uid
     * @return array|mixed
     */
    public static function getFollowCache($uid, $followedUid){
        $key = RedisKey::getFollowListKey($uid);
        return Redis::zscore($key, $followedUid);
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



    /**
     * 设置粉丝缓存
     * @param $uid
     * @param $followdUid
     * @return mixed
     */
    public static function setFansCache($uid, $followdUid, $score = ''){
        $key = RedisKey::getFansListKey($followdUid);
        if(!$score){
            $score = Util::createScore();
        }
        return Redis::zadd($key, $score, $uid);
    }

    /**
     * 查询用户的粉丝score
     * @param $uid
     * @return array|mixed
     */
    public static function getFansCache($uid, $fansUid){
        $key = RedisKey::getFansListKey($uid);
        return Redis::zscore($key, $fansUid);
    }

    /**
     * 获取用户的粉丝列表
     * @param $uid
     * @return array|mixed
     */
    public static function getFansCacheRange($uid, $start = 0, $stop = -1){
        $key = RedisKey::getFansListKey($uid);
        return Redis::zrange($key, $start, $stop);
    }


    /**
     * 取消关注
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function unFollow($data){

        if(empty($data['token']) || empty($data['followed_uid'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //判断是否已经关注
        $followInfo = UserFollowModel::getInstance()->checkFollowed($uid, $data['followed_uid']);
        if(!$followInfo){
            throw new ServiceException(MessageCode::USER_NOT_FOLLOWED);
        }

        //判断是否互相关注，互相关注，清除对方互相关注状态
        if($followInfo->followed_both){
            UserFollowModel::getInstance()->unBothFollowStatus($data['followed_uid'], $uid);
            //清除redis用户互相关注
            $followBothKey = implode('_', array_sort(([$uid, $data['followed_uid']])));
            Redis::hdel(RedisKey::FOLLOW_BOTH, RedisKey::getFollowBothKey($followBothKey));
        }

        //清除redis缓存用户关注
        $followKey = RedisKey::getFollowListKey($uid);
        $fansKey = RedisKey::getFansListKey($data['followed_uid']);
        Redis::zrem($followKey, $data['followed_uid']);
        Redis::zrem($fansKey, $uid);

        UserFollowModel::getInstance()->unFollowed($uid, $data['followed_uid']);
        return self::checkUserRelation($data['followed_uid'], $uid);
    }

    /**
     * $fansKey
     * @param $data
     * @return array|mixed
     * @throws ServiceException
     */
    public static function followList($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = $myUid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //默认查询登录用户自己的粉丝，当传递了指定uid用户，则查询指定用户的粉丝
        if(isset($data['uid']) &&  UserModel::getInstance()->getInfo($data['uid'])){
            $uid = $data['uid'];
        }

        /*$p = isset($data['page']) ? $data['page'] : 1;  //获取分页
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $start = ($p - 1) * $pageSize;
        $stop = $start + $pageSize - 1;
        $list = self::getFollowCacheRange($uid,  $start, $stop);*/
        $page   = $data['page'] ?? 1;
        $limit  = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $list = DB::table('user_follow')
        ->where(['uid'=>$uid])
        ->select('followed_uid')
        ->offset($page == 1 ? 0 : ($limit * ($page - 1)))
        ->limit($limit)
        ->get()
        ->toArray();
        $list = $list ? array_column(array_map('get_object_vars', $list), 'followed_uid') : [];

        if(empty($list)){
            $rows = UserFollowModel::getInstance()->getFollowList($uid);
            if(!empty($rows)){
                foreach ($rows as $item){
                    $list[] = $item->followed_uid;
                    
                    
                }
            }
        }

        $result = [];
        if(!empty($list)){
            //获取用户粉丝数： 先从缓存中获取，没有的话从数据库中获取
            $fansCount = self::getFansCountByUids($list);
            $userInfoList = AccountService::getUserListInfo($list);
            foreach ($list as $item){
                $result[] = [
                    'uid' => $item,
                    'nick' => isset($userInfoList[$item]->nick) ? $userInfoList[$item]->nick : '',
                    'head_image' => isset($userInfoList[$item]->head_image) ? $userInfoList[$item]->head_image : '',
                    'fans_count' => isset($fansCount[$item]) ? $fansCount[$item] : 0,
                    'relation' => self::checkUserRelation($item, $myUid)
                ];
            }

        }
        return $result;
    }


    /**
     * 根据Uid列表获取粉丝数
     * @param $uidArr
     * @return array
     */
    public static function getFansCountByUids($uidArr){
        $countList = UserFollowModel::getInstance()->getFansCount($uidArr);
        $data = [];
        if(!empty($countList)){
            foreach ($countList as $item){
                $data[$item->followed_uid] = $item->count;
            }
        }
        return $data;
    }


    /**
     * 获取粉丝列表
     * @param $data
     * @return array|mixed
     * @throws ServiceException
     */
    public static function fansList($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = $myUid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //默认查询登录用户自己的粉丝，当传递了指定uid用户，则查询指定用户的粉丝
        if(isset($data['uid']) &&  UserModel::getInstance()->getInfo($data['uid'])){
            $uid = $data['uid'];
        }

        //先从缓存中取，如果取不到，再从数据库取
/*        $p = isset($data['page']) ? $data['page'] : 1;  //获取分页
        $pageSize = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $start = ($p - 1) * $pageSize;
        $stop = $start + $pageSize - 1;

        $list = self::getFansCacheRange($uid, $start, $stop);*/

        $page   = $data['page'] ?? 1;
        $limit  = ConfigService::getConfig(ConfigService::PAGE_SIZE);
        $list = DB::table('user_follow')
        ->where(['followed_uid'=>$uid])
        ->select('uid')
        ->offset($page == 1 ? 0 : ($limit * ($page - 1)))
        ->limit($limit)
        ->get()
        ->toArray();
        $list = $list ? array_column(array_map('get_object_vars', $list), 'uid') : [];
        if(empty($list)){
            $rows = UserFollowModel::getInstance()->getFansList($uid);
            if(!empty($rows)){
                foreach ($rows as $item){
                    $list[] = $item->uid;
                    
                }
            }
        }
        $result = [];
        if(!empty($list)){
            //获取用户粉丝数： 先从缓存中获取，没有的话从数据库中获取
            $userInfoList = AccountService::getUserListInfo($list);
            foreach ($list as $item){
                $result[] = [
                    'uid' => $item,
                    'nick' => isset($userInfoList[$item]->nick) ? $userInfoList[$item]->nick : '',
                    'head_image' => isset($userInfoList[$item]->head_image) ? $userInfoList[$item]->head_image : '',
                    'relation' => self::checkUserRelation($userInfoList[$item]->uid, $myUid)
                ];
            }

        }
        return $result;
    }

    /**
     * 检查用户的关系
     * @param $uid
     * @param $selfUid
     * @return string
     */
    public static function checkUserRelation($uid, $selfUid){
        if($uid == $selfUid){
            return self::RELATION_SELF;
        }
        $followed = self::checkUserFollowed($uid, $selfUid);
        $fans = self::checkUserFans($uid, $selfUid);
        if($followed && $fans){
            return self::RELATION_BOTH;
        } elseif($followed) {
            return self::RELATION_FOLLOWED;
        } elseif($fans){
            return self::RELATION_FANS;
        }
        return self::RELATION_NONE;
    }

    /**
     * 查询使用户是否是我的专注
     * @param $uid
     * @param $selfUid
     * @return bool
     */
    public static function checkUserFollowed($uid, $selfUid){
        $score = self::getFollowCache($selfUid, $uid);
        if(!$score){
            return UserFollowModel::getInstance()->checkFollowed($selfUid, $uid);
        }
        return true;
    }


    /**
     * 查询用户是否是我的粉丝
     * @param $uid
     * @param $selfUid
     * @return bool
     */
    public static function checkUserFans($uid, $selfUid){
        $score = self::getFansCache($selfUid, $uid);
        if(!$score){
            return UserFollowModel::getInstance()->checkFollowed($uid, $selfUid);
        }
        return true;
    }


}