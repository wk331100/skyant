<?php
namespace App\Services;


use App\Libs\Util;
use App\Models\UserLoginModel;
use App\Models\UserModel;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Redis;
use App\Libs\RedisKey;

class LoginService{

    public static function Login($data){
        if(empty($data['type']) || empty($data['username']) || !in_array($data['type'], AccountService::$sendType)
            || empty($data['password']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户是否存在
        $userInfo = self::getLoginUserInfo($data);
        if(empty($userInfo)){
            throw new ServiceException(MessageCode::USER_NOT_EXIST);
        }

        //检查用户是否被冻结
        if($userInfo['status'] < 1){
            throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
        }

        //检查是否被登陆冻结
        $loginLockKey = RedisKey::getSendCodeLockKey($userInfo['uid']);
        if(Redis::get($loginLockKey)){
            $ttl = Redis::ttl($loginLockKey);
            $minutes = ceil($ttl / 60);
            throw new ServiceException(MessageCode::USER_LOGIN_LOCKED,[$minutes]);
        }
        
        //检查用户密码是否正确
        $encryptPwd = RegisterService::makeUserPassword($data['password'], $userInfo['pwd_rand']);
        if($encryptPwd !== $userInfo['password']){
            $loginErrorKey = RedisKey::getWrongPwdCountKey($userInfo['uid']);
            $errorCount = Redis::get($loginErrorKey);
            if($errorCount > 0){ //累加错误次数
                Redis::incr($loginErrorKey);
            } else { //初始化错误次数
                $configInterval = ConfigService::getConfig(ConfigService::USER_WRONG_PWD_INTERVAL);
                Redis::setex($loginErrorKey, $configInterval, 1);
            }

            //判断错误次数，超过阀值，冻结改用户登陆
            $configErrorLimit = ConfigService::getConfig(ConfigService::USER_WRONG_PWD_LIMIT);
            if($errorCount + 1 >=  $configErrorLimit){

                $configLockTime = ConfigService::getConfig(ConfigService::USER_LOGIN_LOCK_TIME);
                Redis::setex($loginLockKey, $configLockTime, 1);
                $minutes = ceil($configLockTime / 60);
                throw new ServiceException(MessageCode::USER_LOGIN_LOCKED,[$minutes]);
            }
            throw new ServiceException(MessageCode::USER_PASSWORD_ERROR,[$configErrorLimit - $errorCount - 1,$configErrorLimit]);
        }

        //创建登陆Token
        $token = Util::createToken();
        $tokenKey = RedisKey::getLoginTokenKey($userInfo['uid']);
        $tokenUserKey = RedisKey::getTokenUserKey($token);
        $tokenTime = ConfigService::getConfig(ConfigService::USER_LOGIN_TOKEN_TIME);

        self::clearLogin($userInfo['uid']); //清除上次登陆的状态
        Redis::setex($tokenKey, $tokenTime, $token);  //设置登陆token
        Redis::setex($tokenUserKey, $tokenTime, $userInfo['uid']);  //设置token和uid关系

        //记录登陆日志
        $loginLogData = [
            'uid'   => $userInfo['uid'],
            'ip'    => Util::getIp(),
            'platform' => isset($data['platform']) ? $data['platform'] :'',
            'create_time'   => date('Y-m-d H:i:s')
        ];
        UserLoginModel::getInstance()->create($loginLogData);
        return ['token'=>$token,'uid'=>$userInfo['uid']];
    }

    /**
     * 清除用户登陆状态
     * @param $uid
     * @return bool
     */
    public static function clearLogin($uid){
        $tokenKey = RedisKey::getLoginTokenKey($uid);
        $token = Redis::get($tokenKey);
        if($token){
            $tokenUserKey = RedisKey::getTokenUserKey($token);
            Redis::del($tokenUserKey);
        }
        Redis::del($tokenKey);
        return true;
    }

    /**
     * 退出登录
     * @param $data
     * @return bool
     * @throws ServiceException
     */
    public static function logout($data){
        if(empty($data['token']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $tokenUserKey = RedisKey::getTokenUserKey($data['token']);
        $uid = Redis::get($tokenUserKey);
        if(empty($uid)){
            throw new ServiceException(MessageCode::INVALID_TOKEN);
        }
        return self::clearLogin($uid);
    }


    /**
     * 获取登陆用户信息
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|mixed|null|object|static
     */
    public static function getLoginUserInfo($data){
        if($data['type'] == AccountService::EMAIL){
            $key = $data['username'];
            $redisData = Redis::hget(RedisKey::ACCOUNT_USER, $key);
            if($redisData){
                $userInfo = json_decode($redisData, true);
            } else {
                $userInfo = UserModel::getInstance()->getUserInfoByEmail($data['username']);
                if(!empty($userInfo)){
                    Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
                }
            }
        } elseif($data['type'] == AccountService::PHONE){
            $key = $data['prefix'] . $data['username'];
            $redisData = Redis::hget(RedisKey::ACCOUNT_USER, $key);

            if(!empty($redisData)){
                $userInfo = json_decode($redisData, true);
            } else {
                $userInfo = UserModel::getInstance()->getUserInfoByPhone($data['prefix'], $data['username']);
                if(!empty($userInfo)){
                    Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
                }
            }
        } elseif ($data['type'] ==  AccountService::USERNAME){
            $key = $data['username'];
            $redisData = Redis::hget(RedisKey::ACCOUNT_USER, $key);
            if($redisData){
                $userInfo = json_decode($redisData, true);
            } else {
                $userInfo = UserModel::getInstance()->getUserInfoByUsername($data['username']);
                if(!empty($userInfo)){
                    Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
                }
            }
        }
        return Util::objToArray($userInfo);
    }

}