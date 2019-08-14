<?php
namespace App\Services;

use App\Exceptions\ServiceException;
use App\Libs\GmsEmail;
use App\Libs\GmsSms;
use App\Libs\MessageCode;
use App\Libs\RedisKey;
use App\Libs\Util;
use App\Libs\Lang;
use App\Models\EmailTemplateModel;
use App\Models\SmsPrefixModel;
use App\Models\SmsTemplateModel;
use App\Models\UserAdviceModel;
use App\Models\UserConfigModel;
use App\Models\UserInfoModel;
use App\Models\UserModel;
use App\Models\UserNoticeModel;
use Illuminate\Support\Facades\Redis;
use App\Models\UserMomentModel;
use Illuminate\Support\Facades\DB;
class AccountService{

    const MINUTE    = '60';
    const EMAIL     = 'email';
    const PHONE     = 'phone';
    const USERNAME  = 'username';
    const TEMPLATE_SIGN_VERIFY = 'verify';

    const REG_TYPE_EMAIL    = '1';
    const REG_TYPE_PHONE    = '2';

    const SEND_CODE_REGISTER  = '1';
    const SEND_CODE_REPWD     = '2';

    const TYPE_NORMAL       = '1';
    const TYPE_PARTNER      = '2';

    const USER_STATUS_ENABLED = '1';
    const USER_STATUS_DISABLED = '0';

    const ID_TYPE_IDENTITY     = '1';
    const ID_TYPE_DRIVER       = '2';
    const ID_TYPE_PASSPORT     = '3';

    const ID_VERIFY_DEFAULT     = '0';
    const ID_VERIFY_NEW         = '-1';
    const ID_VERIFIED           = '1';

    const ADVICE_TYPE_FUNC      = '1';
    const ADVICE_TYPE_UE        = '2';
    const ADVICE_TYPE_CONTENT   = '3';
    const ADVICE_TYPE_OTHER     = '4';
    const TMP_TYPE              = [1=>'register',2=>'security',3=>'verify'];

    public static $sendType = [self::EMAIL,self::PHONE,self::USERNAME];
    public static $idType  = [self::ID_TYPE_IDENTITY,self::ID_TYPE_DRIVER,self::ID_TYPE_PASSPORT];
    public static $adviceType = [self::ADVICE_TYPE_FUNC,self::ADVICE_TYPE_UE,self::ADVICE_TYPE_CONTENT,self::ADVICE_TYPE_OTHER];

    //========================== 以下是账户体系 ====================================

    /**
     * 重置密码验证获取修改密码临时Token
     * @param $data
     * @return string
     * @throws ServiceException
     */
    public static function forgetVerify($data, $codeChecked = false){
        if(empty($data['type']) || empty($data['address']) || !in_array($data['type'], AccountService::$sendType)){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        //校验验证码是否匹配
        if(!$codeChecked && !self::checkCode($data)){
            throw new ServiceException(MessageCode::CODE_NOT_MATCH);
        }

        $data['username'] = $data['address'];
        $userInfo = LoginService::getLoginUserInfo($data);
        if(empty($userInfo)){
            throw new ServiceException(MessageCode::USER_NOT_EXIST);
        }

        $token = Util::createToken();
        $resetTokenKey = RedisKey::getResetPwdTokenKey($token);
        $resetTokenTime = ConfigService::getConfig(ConfigService::USER_RESET_TOKEN_TIME);
        Redis::setex($resetTokenKey, $resetTokenTime, $userInfo['uid']);
        return $token;
    }


    /**
     * 重置密码
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function resetPwd($data){
        if(empty($data['token']) ||  empty($data['newPassword']) || empty($data['rePassword']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户两次密码是否匹配
        if($data['newPassword'] != $data['rePassword']){
            throw new ServiceException(MessageCode::PASSWORD_NOT_MATCH);
        }

        //检查用户Token是否有效
        $resetToken = RedisKey::getResetPwdTokenKey($data['token']);
        $uid = Redis::get($resetToken);
        if(!$uid){
            throw new ServiceException(MessageCode::RESET_TOKEN_INVALID);
        }
//        Redis::del($resetToken); //清除本次Token

        //检查用户账户是否被冻结
        $userInfo = UserModel::getInstance()->getInfo($uid);
        if($userInfo->status == self::USER_STATUS_DISABLED){
            throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
        }

        $newPassword = RegisterService::makeUserPassword($data['newPassword'], $userInfo->pwd_rand);
        $updateData = [
            'password' => $newPassword,
            'update_ip' => Util::getIp()
        ];

        UserModel::getInstance()->update($updateData, $uid);
        $userInfo->password = $newPassword;
        $key = self::getUserRedisKey($userInfo);
        return Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
    }

    /*
    * 重置资金密码 
    */
    public static function resetAssetsPwd($data){
        if(empty($data['token']) ||  empty($data['newPassword']) || empty($data['rePassword']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户两次密码是否匹配
        if($data['newPassword'] != $data['rePassword']){
            throw new ServiceException(MessageCode::PASSWORD_NOT_MATCH);
        }

        //检查用户Token是否有效
        if(!$uid = self::checkUserLogin($data)){
            throw new ServiceException(MessageCode::RESET_TOKEN_INVALID);
        }

        //检查用户账户是否被冻结
        $userInfo = UserModel::getInstance()->getInfo($uid);
        
        if($userInfo->status == self::USER_STATUS_DISABLED){
            throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
        }

        if (isset($data['oldPassword'])) {
            // if ($data['oldPassword'] == $data['newPassword']) {
            //     throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
            // }
            if (RegisterService::makeUserPassword($data['oldPassword'], $userInfo->pwd_rand) != $userInfo->assets_password) {
                throw new ServiceException(MessageCode::OLD_PASSWORD_ERROR);
            }

        } else {
            if (!empty($userInfo->assets_password)) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }            
        }

        $newPassword = RegisterService::makeUserPassword($data['newPassword'], $userInfo->pwd_rand);
        $updateData = [
            'assets_password' => $newPassword,
            'update_ip' => Util::getIp()
        ];

        UserModel::getInstance()->update($updateData, $uid);
        $userInfo->assets_password = $newPassword;
        $key = self::getUserRedisKey($userInfo);
        return Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
    }


    /**
     * 忘记资金密码     
     */
    public static function forgetAssetsPwd($data)
    {   

        if(empty($data['token']) ||  empty($data['newPassword']) || empty($data['rePassword']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户两次密码是否匹配
        if($data['newPassword'] != $data['rePassword']){
            throw new ServiceException(MessageCode::PASSWORD_NOT_MATCH);
        }
        if(!$uid = self::checkUserLogin($data)){
            throw new ServiceException(MessageCode::RESET_TOKEN_INVALID);
        }

        //检查用户账户是否被冻结
        $userInfo = UserModel::getInstance()->getInfo($uid);
        
        if($userInfo->status == self::USER_STATUS_DISABLED){
            throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
        }
        if (!self::checkCode($data)) {
            throw new ServiceException(MessageCode::CODE_NOT_MATCH);
        }

        $newPassword = RegisterService::makeUserPassword($data['newPassword'], $userInfo->pwd_rand);
	    if ($userInfo->assets_password == $newPassword) {
	       return true;
        }
        $updateData = [
            'assets_password' => $newPassword,
            'update_ip' => Util::getIp()
        ];

        UserModel::getInstance()->update($updateData, $uid);
        $userInfo->assets_password = $newPassword;
        $key = self::getUserRedisKey($userInfo);
        return Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
    }

    /**
     * 修改密码
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function modifyPwd($data){
        if(empty($data['token']) || empty($data['oldPassword']) || empty($data['newPassword']) || empty($data['rePassword']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        //检查用户两次密码是否匹配
        if($data['newPassword'] != $data['rePassword']){
            throw new ServiceException(MessageCode::PASSWORD_NOT_MATCH);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查用户账户是否被冻结
        $userInfo = UserModel::getInstance()->getInfo($uid);
        if($userInfo->status == self::USER_STATUS_DISABLED){
            throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
        }

        //检查用户原始密码是否正确
        if(RegisterService::makeUserPassword($data['oldPassword'], $userInfo->pwd_rand) != $userInfo->password){
            throw new ServiceException(MessageCode::OLD_PASSWORD_ERROR);
        }

        $newPassword = RegisterService::makeUserPassword($data['newPassword'], $userInfo->pwd_rand);
        $userInfo->password = $newPassword;
        $updateData = [
            'password' => $newPassword,
            'update_ip' => Util::getIp()
        ];

        UserModel::getInstance()->update($updateData, $uid);
        $key = self::getUserRedisKey($userInfo);
        return Redis::hset(RedisKey::ACCOUNT_USER, $key, json_encode($userInfo));
    }



    /**
     * 根据注册类型获取redis的key
     * @param $userInfo
     * @return string
     */
    public static function getUserRedisKey($userInfo){
        if(is_object($userInfo)){
            $userInfo = Util::objToArray($userInfo);
        }
        if($userInfo['reg_type'] == AccountService::REG_TYPE_EMAIL){
            $key = $userInfo['email'];
        } elseif($userInfo['reg_type'] == AccountService::REG_TYPE_PHONE){
            $key = $userInfo['prefix'] . $userInfo['phone'];
        }
        return $key;
    }

    /**
     * 获取当前IP注册用户数
     * @param $ip
     * @return int
     */
    public static function checkIpUserCount($ip){
        return UserModel::getInstance()->getIpUserCount($ip);
    }


    /**
     * 初始化用户基本信息
     * @param $uid
     * @return int
     */
    public static function initUserInfo($uid){
        $insertData = [
            'uid' => $uid,
            'nick' => '用户' . $uid,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return UserInfoModel::getInstance()->create($insertData);
    }


    /**
     * 初始化用户通知
     * @param $uid
     * @return int
     */
    public static function initUserNotice($uid){
        $insertData = [
            'uid' => $uid,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return UserNoticeModel::getInstance()->create($insertData);
    }


    /**
     * 获取用户通知状态
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     * @throws ServiceException
     */
    public static function getUserNotice($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        return UserNoticeModel::getInstance()->getInfo($uid);
    }


    /**+
     * 修改用户通知
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function updateUserNotice($data){
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $updateData = [];
        if(isset($data['push_enabled'])){
            $updateData['push_enabled'] = $data['push_enabled'];
        }
        if(isset($data['comment_push_enabled'])){
            $updateData['comment_push_enabled'] = $data['comment_push_enabled'];
        }
        if(isset($data['concern_push_enabled'])){
            $updateData['concern_push_enabled'] = $data['concern_push_enabled'];
        }
        if(isset($data['article_push_enabled'])){
            $updateData['article_push_enabled'] = $data['article_push_enabled'];
        }
        if(isset($data['news_push_enabled'])){
            $updateData['news_push_enabled'] = $data['news_push_enabled'];
        }
        if(empty($updateData)){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        return UserNoticeModel::getInstance()->update($updateData, $uid);

    }
    
    /**
     * 初始化用户配置
     * @param $uid
     * @return int
     */
    public static function initUserConfig($uid){
        $insertData = [
            'uid'   => $uid,
            'default_lang' => Lang::$default,
            'create_time' => date('Y-m-d H:i:s')
        ];
        return UserConfigModel::getInstance()->create($insertData);
    }

    /**
     * 用户身份认证
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function identityVerify($data){
        if(empty($data['token']) || empty($data['real_name']) || empty($data['id_type']) || empty($data['id_code'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(!in_array($data['id_type'], self::$idType)){
            throw new ServiceException(MessageCode::USER_ID_TYPE_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $updateData = [
            'real_name' => $data['real_name'],
            'id_type'   => $data['id_type'],
            'id_code'   => $data['id_code'],
            'is_verified' => self::ID_VERIFY_NEW,
            'positive_image'=>$data['positive_image'], 
            'aspect_image'=>$data['aspect_image'], 
            'back_image'=>$data['back_image'],
        ];

        return UserInfoModel::getInstance()->update($updateData, $uid);
    }

    /**
     * 用户建议
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function advice($data){
        if(empty($data['token']) || empty($data['type']) || empty($data['contact_name']) || empty($data['contact_tel']) || empty($data['advice'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(!in_array($data['type'], self::$adviceType)){
            throw new ServiceException(MessageCode::USER_ADVICE_TYPE_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查用户建议频率
        if(!self::checkAdviceFreq($uid)){
            throw new ServiceException(MessageCode::USER_ADVICE_FREQ_ERROR);
        }

        $insertData = [
            'contact_name'  => $data['contact_name'],
            'type'          => $data['type'],
            'contact_tel'   => $data['contact_tel'],
            'advice'       => $data['advice'],
            'create_time'   => date('Y-m-d H:i:s')
        ];
        for ($i = 1; $i <= 5; $i ++){
            $image = 'image_' . $i;
            if(isset($data[$image])){
                $insertData[$image] = $data[$image];
            }
        }
        return UserAdviceModel::getInstance()->create($insertData);
    }

    /**
     * 检查用户建议频率
     * @param $uid
     * @return bool
     */
    public static function checkAdviceFreq($uid){
        $adviceFreqKey = RedisKey::getUserAdviceFreq($uid . date('YmdHi'));
        $configAdviceFreq = ConfigService::getConfig(ConfigService::USER_ADVICE_FREQUENCY);
        $redisValue = Redis::get($adviceFreqKey);
        if($redisValue >= $configAdviceFreq){
            return false;
        }
        if($redisValue){
            Redis::incr($adviceFreqKey);
        } else {
            Redis::setex($adviceFreqKey, self::MINUTE, 1);
        }
        return true;
    }

    /**
     * 获取用户基本信息
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     * @throws ServiceException
     */
    public static function getUserInfo($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }
        $info = UserInfoModel::getInstance()->getInfo($uid);
        $user = UserModel::getInstance()->getInfo($uid);

        $info->account = ($user->reg_type == self::REG_TYPE_EMAIL) ? $user->email : $user->phone;
            $info->country_name = !empty($info->country) ? SmsPrefixModel::getInstance()->getNameByCode($info->country) : (object)null;
        if(!empty($info->id_code)){
            $info->id_code = Util::hideCode($info->id_code);
        }
        if(!empty($info->real_name)){
            $info->real_name = $info->real_name;
        }
	    $is_redpack  = (bool)DB::table('red_pack_patch')->where(['receiver_uid'=>$uid,'status'=>1])->count();
        $momentCount = UserMomentModel::getInstance()->momentCount($uid);
            
        $followCount = DB::table('user_follow')->where(['uid'=>$uid])->count();
        $fansCount   = DB::table('user_follow')->where(['followed_uid'=>$uid])->count();
        $info->moment_count  = $momentCount;
        $info->follow_count  = $followCount;
        $info->fans_count    = $fansCount;
        $info->is_assets_pwd = $user->assets_password ? true : false;
	    $info->is_redpack = $is_redpack;
        return $info;
    }


    /**
     * 根据uid获取用户信息
     * @param $uid
     * @return mixed
     */
    public static function getUserInfoByUid($uid){
        $cache = Redis::hget(RedisKey::ACCOUNT_UID, $uid);
        if(empty($cache)){
            $data = UserModel::getInstance()->getInfo($uid);
            $data->info = UserInfoModel::getInstance()->getInfo($uid);
            $cache = json_encode($data);
            Redis::hset(RedisKey::ACCOUNT_UID, $uid, $cache);
        }
        return json_decode($cache, true);
    }


    /**
     * 获取用户信息列表
     * @param $uidArr
     * @return array
     */
    public static function getUserListInfo($uidArr){
        $list =  UserInfoModel::getInstance()->getUserListByUids($uidArr);
        $data = [];
        if(!empty($list)){
            foreach ($list as $item){
                $data[$item->uid] = $item;
            }
        }
        return $data;
    }


    /**
     * 更新用户基本信息
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function updateUserInfo($data){
        if(empty($data['token']) || (empty($data['nick']) && empty($data['country']) &&  !isset($data['sex'])  && empty($data['sign']))){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(empty($uid)){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $updateData = [];
        if(!empty($data['nick'])){
            $updateData['nick'] = $data['nick'];
            //检查昵称是否存在
            if(UserInfoModel::getInstance()->checkNickExist($data['nick'])){
                throw new ServiceException(MessageCode::USER_NICK_EXIST);
            }
            //检查用户设置昵称频率
            $config = ConfigService::getConfig(ConfigService::NICK_MODIFY_FREQ);
            $redisKey = RedisKey::getNickFreqKey($uid);
            if(Redis::get($redisKey)){
                throw new ServiceException(MessageCode::USER_NICK_MODIFY_FREQ);
            } else {
                Redis::setex($redisKey, $config * 86400, 1);
            }

        }

        if(!empty($data['sign'])){
            $updateData['sign'] = $data['sign'];
        }


        if(!empty($data['country'])){
            $updateData['country'] = $data['country'];
        }

        if(isset($data['sex'])){
            $updateData['sex'] = $data['sex'];
        }

        UserInfoModel::getInstance()->update($updateData, $uid);
        return Redis::hdel(RedisKey::ACCOUNT_UID, $uid);
    }


    /**
     * 上传用户头像
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function updateHeadImage($data){
        if(empty($data['token']) || empty($data['headImage'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = self::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $updateData = [
            'head_image' => $data['headImage']
        ];
        if(UserInfoModel::getInstance()->update($updateData, $uid)){
            Redis::hdel(RedisKey::ACCOUNT_UID, $uid);
            return $data['headImage'];
        }
        return false;
    }


    /**
     * 检查用户登陆Token
     * @param $data
     * @return bool
     * @throws ServiceException
     */
    public static function checkUserLogin($data){
        if(empty($data['token'])){
            return false;
        }
        //检查用户Token是否有效
        $tokenUserKey = RedisKey::getTokenUserKey($data['token']);
        $uid = Redis::get($tokenUserKey);
        if(!$uid){
            return false;
        }
        return $uid;
    }


    /**
     * 分页查找模糊匹配用户昵称
     * @param $data
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ServiceException
     */
    public static function searchUser($data){
        if(empty($data['keyword'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = self::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        }

        $data['keyword'] = trim($data['keyword']);
        if($data['keyword'] == ''){
            throw new ServiceException(MessageCode::USER_KEYWORD_NONE);
        } else {
	  if (in_array($data['keyword'],['%','_'])) {
		$data['keyword'] .= "\\";
          }
	}

        $list = UserInfoModel::getInstance()->searchUser($data['keyword']);
        $result = [];
        if(!empty($list)){
            foreach ($list as $item){
                $result[] = [
                    'uid' => $item->uid,
                    'nick' => $item->nick,
                    'head_image' => $item->head_image
                ];
            }
        }
        return $result;
    }



    //========================== 以下是发送验证码 ===================================

    /**
     * 注册登录发送验证码
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function sendCode($data){
        if(empty($data['type']) || empty($data['address']) || !in_array($data['type'], self::$sendType) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }
        //检查发送验证码锁
        if(self::checkSendCodeLocked($data)){
            throw new ServiceException(MessageCode::SEND_CODE_LOCKED);
        }
        //检查用户发送验证频率
        if(false == self::checkUserSendCodeFrequency($data['address'])){
            throw new ServiceException(MessageCode::USER_SEND_CODE_FREQ_ERRER);
        }
        //检查IP发送验证码频率
        if(false == self::checkIpSendCodeFrequency($data['ip'])){
            throw new ServiceException(MessageCode::IP_SEND_CODE_FREQ_ERRER);
        }

        //检查发送验证码间隔
        $interval = self::checkCodeInterval($data);
        if( $interval && $interval > 0){
            throw new ServiceException(MessageCode::SEND_CODE_INTERVAL, [$interval]);
        }

        $data['code'] = Util::createVerifyCode(); //生成新的验证码
        
        if($data['type'] == self::EMAIL){
            //从邮件模板中获取标题和内容
            $template = EmailTemplateModel::getInstance()->getTemplate(self::TMP_TYPE[3], Lang::$default);
            if(empty($template)){
                throw new ServiceException(MessageCode::TEMPLATE_NOT_EXIST);
            }
            $data['subject'] = $template->subject;
            $data['html']   = sprintf($template->html, $data['code']);
            $key = $data['to'] = $data['address'];

            $checkUserExist = UserModel::getInstance()->getUserInfoByEmail($data['address']);
            if($data['send_type'] == self::SEND_CODE_REGISTER && $checkUserExist){
                throw new ServiceException(MessageCode::USER_ALREADY_EXIST);
            } elseif($data['send_type'] == self::SEND_CODE_REPWD && !$checkUserExist){
                throw new ServiceException(MessageCode::USER_NOT_EXIST);
            }

            $email = new GmsEmail();
            $result = $email->send($data);
        } elseif($data['type'] ==  self::PHONE){
            //从邮件模板中获取标题和内容
            $template = SmsTemplateModel::getInstance()->getTemplate(self::TMP_TYPE[3], Lang::$default);
            if(empty($template)){
                throw new ServiceException(MessageCode::TEMPLATE_NOT_EXIST);
            }
            $data['template_code'] = $template->temp_code;
            $data['prefix'] = !empty($data['prefix']) ? $data['prefix'] : GmsSms::DEFAULT_PREFIX;
            $data['phone'] = $data['address'];

            $checkUserExist = UserModel::getInstance()->getUserInfoByPhone($data['prefix'], $data['address']);
            if($data['send_type'] == self::SEND_CODE_REGISTER && $checkUserExist){
                throw new ServiceException(MessageCode::USER_ALREADY_EXIST);
            } elseif($data['send_type'] == self::SEND_CODE_REPWD && !$checkUserExist){
                throw new ServiceException(MessageCode::USER_NOT_EXIST);
            }

            $key = $data['prefix'] . $data['address'];
            $sms = new GmsSms();
            $result = $sms->send($data);
        } else {
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //将验证码写入redis并且设置超时时间
        $verifyCodeLimit = ConfigService::getConfig(ConfigService::VERIFY_CODE_LIMIT);
        $verifyKey = RedisKey::getVerifyCodeKey($key);
        Redis::setex($verifyKey, $verifyCodeLimit ,$data['code']);

        //设置下一次验证码发送间隔
        $sendCodeInterval = ConfigService::getConfig(ConfigService::SEND_CODE_INTERVAL);
        $intervalKey = RedisKey::getSendCodeIntervalKey($key);
        Redis::setex($intervalKey, $sendCodeInterval ,$data['code']);
        return $result;
    }


    //检查重新发送验证码间隔
    public static function checkCodeInterval($data){
        if($data['type'] == self::EMAIL){
            $key = $data['address'];
        } elseif($data['type'] ==  self::PHONE){
            $key = $data['prefix'] . $data['address'];
        }
        $intervalKey = RedisKey::getSendCodeIntervalKey($key);
        return Redis::ttl($intervalKey);
    }

    /**
     * 检查用户发送的验证码
     * @param $data
     * @return bool
     */
    public static function checkCode($data){
        if(empty($data['type']) || empty($data['address']) || empty($data['code'])){
            return false;
        }
        $prefix = !empty($data['prefix']) ? $data['prefix'] : GmsSms::DEFAULT_PREFIX;
        $key = ($data['type'] ==  self::PHONE) ? $prefix . $data['address'] : $data['address'];
        $verifyKey = RedisKey::getVerifyCodeKey($key);
        $redisCode = Redis::get($verifyKey);
        if($data['code'] ==  $redisCode){
            //清除本次验证码
            $verifyKey = RedisKey::getVerifyCodeKey($key);
            Redis::del($verifyKey);
            return true;
        }
        return false;
    }


    /**
     * 检查发送验证码锁
     * @param $key
     * @return bool
     */
    public static function checkSendCodeLocked($list){
        $keyArr = [];
        if(isset($list['address'])){
            $keyArr[] = $list['address'];
        }
        if(isset($list['ip'])){
            $keyArr[] = $list['ip'];
        }
        if(!empty($keyArr)){
            foreach ($keyArr as $key){
                $codeLockKey = RedisKey::getSendCodeLockKey($key);
                $locked = Redis::get($codeLockKey);

                if($locked){
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * 锁定发送验证码
     * @param $key
     * @return bool
     */
    public static function lockSendCode($key){
        $lockTime = ConfigService::getConfig(ConfigService::SEND_CODE_LOCK_TIME);

        $lockKey = RedisKey::getSendCodeLockKey($key);

        if(Redis::get($lockKey)){
            return true;
        }
        return Redis::setex($lockKey, $lockTime, 1);
    }

    /**
     * 检查用户发送验证码频率
     */
    public static function checkUserSendCodeFrequency($address){
        $configUserSendCodeFreqLimit = ConfigService::getConfig(ConfigService::USER_SEND_CODE_FREQ);
        $redisKey = RedisKey::getUserSendCodeFreqKey($address);
        $number = Redis::get($redisKey);
        if(!$number){
            return Redis::setex($redisKey, self::MINUTE, 1);
        } elseif ($number < $configUserSendCodeFreqLimit) {
            return Redis::incr($redisKey);
        } else {
            self::lockSendCode($address);
            return false;
        }
    }

    /**
     * 检查IP发送验证码频率
     */
    public static function checkIpSendCodeFrequency($ip){
        $configIpSendCodeFreqLimit = ConfigService::getConfig(ConfigService::IP_SEND_CODE_FREQ);
        $redisKey = RedisKey::getIpSendCodeKey($ip);
        $number = Redis::get($redisKey);
        if(!$number){
            return Redis::setex($redisKey, self::MINUTE, 1);
        } elseif ($number < $configIpSendCodeFreqLimit) {
            return Redis::incr($redisKey);
        } else {
            self::lockSendCode($ip);
            return false;
        }
    }



}
