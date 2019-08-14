<?php
namespace App\Services;

use App\Libs\MessageCode;
use App\Exceptions\ServiceException;
use App\Libs\RedisKey;
use App\Libs\Util;
use App\Models\UserConfigModel;
use App\Models\UserMessageModel;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Redis;

class MessageService{

    const TYPE_SHARE = 'share';
    const TYPE_COMMENT = 'comment';
    const TYPE_LIKE = 'like';
    const TYPE_FAN = 'fans';
    const TYPE_SYSTEM = 'system';
    const TYPE_REPLY  = 'reply';
    const TYPE_COMPLY = 'comply';


    public static $typeArray = [
        self::TYPE_SHARE,
        self::TYPE_COMMENT,
        self::TYPE_LIKE,
        self::TYPE_FAN,
        self::TYPE_SYSTEM,
        self::TYPE_COMPLY
    ];

    public static function setting($data){
        if(empty($data['token']) || (!isset($data['share_notify']) && !isset($data['comment_notify']) && !isset($data['like_notify'])
                && !isset($data['fan_notify']) && !isset($data['system_notify']))){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $updateData = [];
        if(isset($data['share_notify'])){
            $updateData['share_notify'] = ($data['share_notify']) ? '1' : '0';
        }
        if(isset($data['comment_notify'])){
            $updateData['comment_notify'] = ($data['comment_notify']) ? '1' : '0';
        }
        if(isset($data['like_notify'])){
            $updateData['like_notify'] = ($data['like_notify']) ? '1' : '0';
        }
        if(isset($data['fan_notify'])){
            $updateData['fan_notify'] = ($data['fan_notify']) ? '1' : '0';
        }
        if(isset($data['system_notify'])){
            $updateData['system_notify'] = ($data['system_notify']) ? '1' : '0';
        }

        $userConfig = UserConfigModel::getInstance()->getInfo($uid);
        if(empty($userConfig)){
            $updateData['uid'] = $uid;
            return UserConfigModel::getInstance()->create($updateData);
        }

        return UserConfigModel::getInstance()->update($updateData, $uid);
    }


    /**
     * 获取用户设置
     * @param $uid
     * @return bool|\Illuminate\Database\Eloquent\Model|mixed|null|object|static
     */
    public static function getUserSetting($uid){
        $redisKey = RedisKey::USER_SETTING;
        $config = Redis::hget($redisKey, $uid);
        if($config){
            return json_decode($config, true);
        }

        $data = UserConfigModel::getInstance()->getInfo($uid);
        if($data){
            Redis::hset($redisKey, $uid, json_encode($data));
            return Util::objToArray($data);
        }
        return false;
    }


    /**
     * 获取消息列表
     * @param $data
     * @return \Illuminate\Support\Collection
     * @throws ServiceException
     */
    public static function getMessageList($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $typeArr = [];
        if(!empty($data['type']) && in_array($data['type'], self::$typeArray)){
            if($data['type'] == self::TYPE_COMPLY){
                $typeArr = [
                    self::TYPE_COMMENT,
                    self::TYPE_REPLY
                ];
            } else{
                $typeArr[] = $data['type'];
            }
        } else {
            $typeArr = self::$typeArray;
        }



        $filter = self::checkUserNotifySetting($uid, $typeArr);
        $result = UserMessageModel::getInstance()->getList($filter, $uid);
        $newCountList = UserMessageModel::getInstance()->getNewUserMessageCount($uid, $filter);
        $newCount = 0;
        if(!empty($newCountList)){
            foreach ($newCountList as $item){
                $newCount+= $item->count;
            }
        }
        $data = [
            'list' => $result->getCollection(),
            'new_count' => $newCount
        ];
        return $data;
    }


    /**
     * 检查用户消息设置
     * @param $uid
     * @param $typeArr
     * @return array
     */
    public static function checkUserNotifySetting($uid, $typeArr){
        $userSetting = self::getUserSetting($uid);
        $filter = [];
        if(empty($data['type'])){
            if($userSetting['share_notify']){
                $filter[] = self::TYPE_SHARE;
            }
            if($userSetting['comment_notify']){
                $filter[] = self::TYPE_COMMENT;
            }
            if($userSetting['fan_notify']){
                $filter[] = self::TYPE_FAN;
            }
            if($userSetting['like_notify']){
                $filter[] = self::TYPE_LIKE;
            }
            if($userSetting['reply_notify']){
                $filter[] = self::TYPE_REPLY;
            }
            if($userSetting['system_notify']){
                $filter[] = self::TYPE_SYSTEM;
            }
        }
        //合并用户设置 和 传递参数的 交集
        return array_intersect($filter, $typeArr);
    }




    /**
     * 查询新消息个数
     * @param $data
     * @return array
     * @throws ServiceException
     */
    public static function getNewMessageCount($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $typeArr = [];
        if(isset($data['type']) && in_array($data['type'], self::$typeArray)){
            if($data['type'] == self::TYPE_COMPLY){
                $typeArr = [
                    self::TYPE_COMMENT,
                    self::TYPE_REPLY
                ];
            } else{
                $typeArr[] = $data['type'];
            }
        }

        $data = [
            'list' => UserMessageModel::getInstance()->getNewUserMessageCount($uid, $typeArr),
            'total' => 0
        ];

        if(!empty($data['list'])){
            foreach ($data['list'] as $item){
                $data['total']+= $item->count;
            }
        }
        return $data;
    }


    /**
     * 阅读消息
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function readMessage($data){
        if(empty($data['token']) || empty($data['id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查message是否存在
        $message = UserMessageModel::getInstance()->getInfo($data['id']);
        if(empty($message)){
            throw new ServiceException(MessageCode::MESSAGE_NOT_EXIST);
        }

        //检查message是否属于当前用户
        if(!isset($message->uid) || $message->uid != $uid){
            throw new ServiceException(MessageCode::MESSAGE_NOT_MATCH);
        }
        return UserMessageModel::getInstance()->readMessage($data['id']);
    }

    /**
     * 批量阅读消息
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function readAll($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $type = '';
        if(isset($data['type']) && in_array($data['type'], self::$typeArray)){
            $type = $data['type'];
            if($type == self::TYPE_COMPLY){
                $typeArr = [
                    self::TYPE_COMMENT,
                    self::TYPE_REPLY
                ];
                return UserMessageModel::getInstance()->readMulti($uid, $typeArr);
            }
        }
        return UserMessageModel::getInstance()->readAll($uid, $type);
    }


}