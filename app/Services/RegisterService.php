<?php
namespace App\Services;

use App\Libs\GmsSms;
use App\Libs\RedisKey;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\ServiceException;
use App\Libs\Util;
use App\Libs\MessageCode;
use App\Models\UserModel;

class RegisterService{



    public static function register($data){
        if(empty($data['type']) || empty($data['address']) || !in_array($data['type'], AccountService::$sendType)){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        //校验验证码是否匹配
        if(!AccountService::checkCode($data)){
            throw new ServiceException(MessageCode::CODE_NOT_MATCH);
        }

        //校验IP注册用户数量
        $userCount = AccountService::checkIpUserCount(Util::getIp());
        $userLimit = ConfigService::getConfig(ConfigService::IP_REGISTER_LIMIT);
        if($userCount >= $userLimit){
            throw new ServiceException(MessageCode::IP_REGISTER_LIMIT_ERROR);
        }

        $pid = 0;
        if(!empty($data['invite_code'])){
            $pid = self::getUidByInviteCode($data['invite_code']);
        }
        $pwdRand = Util::createPwdRand();

        $insertData = [
            'pid'       => $pid,
            'invite_code'   => self::makeInviteCode(),
            'pwd_rand'  => $pwdRand,
            'reg_platform' => $data['platform'],
            'type'      => AccountService::TYPE_NORMAL,
            'status'    => AccountService::USER_STATUS_ENABLED,
            'create_ip' => Util::getIp(),
            'create_time'   => date('Y-m-d H:i:s')
        ];

        if($data['type'] ==  AccountService::PHONE){
            $insertData['prefix'] = !empty($data['prefix']) ? $data['prefix'] : GmsSms::DEFAULT_PREFIX;
            $insertData['phone'] = $data['address'];
            $insertData['reg_type'] = AccountService::REG_TYPE_PHONE;
            $checkUserExist = UserModel::getInstance()->getUserInfoByPhone($insertData['prefix'], $data['address']);
        } elseif($data['type'] ==  AccountService::EMAIL){
            $insertData['email'] = $data['address'];
            $insertData['reg_type'] = AccountService::REG_TYPE_EMAIL;
            $checkUserExist = UserModel::getInstance()->getUserInfoByEmail($data['address']);
        }

        //检查用户是否存在
        if($checkUserExist){
            throw new ServiceException(MessageCode::USER_ALREADY_EXIST);
        }

        //用户插入数据库
        $uid = UserModel::getInstance()->create($insertData);
        if($uid){
            //异步初始化用户其他数据
            Artisan::call( "InitUserData", ['uid' => $uid]);
            return AccountService::forgetVerify($data, true);
        }
        return false;
    }

    /**
     * 计算用户加密后的密码
     * @param $password
     * @param $pwdRand
     * @return string
     */
    public static function makeUserPassword($password, $pwdRand){
        return md5($password . $pwdRand);
    }


    /**
     * 创建邀请码
     * @return string
     */
    public static function makeInviteCode(){
        $newCode = Util::createInviteCode();
        $uid = UserModel::getInstance()->getUidByInviteCode($newCode);
        if($uid){
            return self::makeInviteCode();
        }
        return $newCode;
    }



    /**
     * 根据邀请码获取UID
     * @param $inviteCode
     * @return int|mixed
     */
    public static function getUidByInviteCode($inviteCode){
        $info = UserModel::getInstance()->getUidByInviteCode($inviteCode);
        if($info){
            return $info->uid;
        }
        return 0;
    }

}