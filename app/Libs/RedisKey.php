<?php

namespace App\Libs;

class RedisKey
{

    const CONFIG_KEY                = 'config:key';

    //用户发送验证码频率
    const USER_SEND_CODE_FREQ       = 'account:sendcode:user:%s';

    //IP发送验证码频率
    const IP_SEND_CODE_FREQ         = 'account:sendcode:ip:%s';

    //发送验证码锁
    const SEND_CODE_LOCKED          = 'account:sendcode:lock:%s';

    //发送验证码间隔
    const SEND_CODE_INTERVAL        = 'account:sendcode:interval:%s';

    //用户验证码
    const USER_VERIFY_CODE          = 'account:verifycode:%s';

    //短信模板
    const SMS_TEMPLATE              = 'gms:sms:template:%s';

    //邮件模板
    const EMAIL_TEMPLATE            = 'gms:email:template:%s';

    //短信队列
    const SMS_QUEUE                 = 'gms:sms:queue';

    //邮件发送队列
    const EMAIL_QUEUE               = 'gms:email:queue';

    //用户注册数据
    const ACCOUNT_USER              = 'account:user';

    //用户uid信息缓存
    const ACCOUNT_UID               = 'account:uid';

    //登陆冻结
    const USER_LOGIN_LOCK           = 'account:login:lock:%s';

    //密码错误统计
    const USER_WRONG_PWD_COUNT      = 'account:login:error:%s';

    //用户登陆Token
    const USER_LOGIN_TOKEN          = 'account:user:token:%s';

    //Token用户关系映射
    const TOKEN_USER                = 'account:token:user:%s';

    //重置密码临时Token
    const RESET_PWD_TOKEN           = 'account:token:reset:%s';

    //用户投诉建议频率统计
    const USER_ADVICE_FREQ          = 'user:advice:freq:%s';

    //我的关注
    const MY_FOLLOW_LIST            = 'follow:list:%s';

    //关注我的
    const FOLLOW_FANS_LIST          = 'follow:fans:%s';

    //互相关注
    const FOLLOW_BOTH               = 'follow:both';

    //用户评论评率限制
    const USER_COMMENT_FREQ         = 'user:comment:freq:%s';

    //用户昵称修改频率
    const NICK_MODIFY_FREQ          = 'user:nick:freq:%s';

    //用户设置缓存
    const USER_SETTING              = 'user:setting';

    //推荐用户缓存
    const RECOMMEND_USER            = 'recommend:user';

    //推荐用户动态缓存
    const RECOMMEND_MOMENT          = 'recommend:moment';

    //后台设置推荐用户缓存
    const RECOMMEND_ADMIN_USER      = 'recommend:admin:user';

    //子红包队列
    const RED_PACK_PATCH            = 'redpack:patch:%s';

    //抢红包锁
    const RED_PACK_LOCK             = 'redpack:lock:%s';

    //币信息缓存
    const COIN_LIST_CACHE           = 'coin:list';

    //币的价格
    const COIN_PRICE                = 'coin:price';



    /**
     * 单个用户抢红包锁
     * @param $key
     * @return string
     */
    public static function getRedPackLockKey($key){
        return sprintf(self::RED_PACK_LOCK, $key);
    }

    /**
     * 获取子红包队列
     * @param $key
     * @return string
     */
    public static function getRedPackQueueKey($key){
        return sprintf(self::RED_PACK_PATCH, $key);
    }

    /**
     * 获取粉丝列表KEY
     * @param $key
     * @return string
     */
    public static function getNickFreqKey($key){
        return sprintf(self::NICK_MODIFY_FREQ, $key);
    }



    /**
     * 获取粉丝列表KEY
     * @param $key
     * @return string
     */
    public static function getFansListKey($key){
        return sprintf(self::FOLLOW_FANS_LIST, $key);
    }


    /**
     * 获取关注列表KEY
     * @param $key
     * @return string
     */
    public static function getFollowListKey($key){
        return sprintf(self::MY_FOLLOW_LIST, $key);
    }


    /**
     * 用户评论评率统计key
     * @param $key
     * @return string
     */
    public static function getUserCommentFreqKey($key){
        return sprintf(self::USER_COMMENT_FREQ, $key);
    }

    /**
     * 获取互相关注Key
     * @param $key
     * @return string
     */
    public static function getFollowBothKey($key){
        return sprintf(self::FOLLOW_BOTH, $key);
    }


    /**
     * 用户建议频率key
     * @param $key
     * @return string
     */
    public static function getUserAdviceFreq($key){
        return sprintf(self::USER_ADVICE_FREQ, $key);
    }

    /**
     * 获取重置密码临时Token
     * @param $key
     * @return string
     */
    public static function getResetPwdTokenKey($key){
        return sprintf(self::RESET_PWD_TOKEN, $key);
    }


    /**
     * 获取Token和用户关系key
     * @param $key
     * @return string
     */
    public static function getTokenUserKey($key){
        return sprintf(self::TOKEN_USER, $key);
    }

    /**
     * 获取登陆Token
     * @param $key
     * @return string
     */
    public static function getLoginTokenKey($key){
        return sprintf(self::USER_LOGIN_TOKEN, $key);
    }


    /**
     * 登陆密码错误统计Key
     * @param $key
     * @return string
     */
    public static function getWrongPwdCountKey($key){
        return sprintf(self::USER_WRONG_PWD_COUNT, $key);
    }

    /**
     * 用户登陆被冻结Key
     * @param $key
     * @return string
     */
    public static function getLoginLockKey($key){
        return sprintf(self::USER_LOGIN_LOCK, $key);
    }


    /**
     * 获取用户验证码Key
     * @param $key
     * @return string
     */
    public static function getVerifyCodeKey($key){
        return sprintf(self::USER_VERIFY_CODE, $key);
    }


    /**
     * 获取短信模板Key
     * @param $key
     * @return string
     */
    public static function getSmsTemplateKey($key){
        return sprintf(self::SMS_TEMPLATE, $key);
    }


    /**
     * 获取邮件模板Key
     * @param $key
     * @return string
     */
    public static function getEmailTemplateKey($key){
        return sprintf(self::EMAIL_TEMPLATE, $key);
    }


    /**
     * 发送验证码间隔Key
     * @param $key
     * @return string
     */
    public static function getSendCodeIntervalKey($key){
        return sprintf(self::SEND_CODE_INTERVAL, $key);
    }

    /**
     * 获取发送验证码锁Key
     * @param $key
     * @return string
     */
    public static function getSendCodeLockKey($key){
        return sprintf(self::SEND_CODE_LOCKED, $key);
    }


    /**
     * 获取用户发送验证码频率Key
     * @param $address
     * @return string
     */
    public static function getUserSendCodeFreqKey($address){
        return sprintf(self::USER_SEND_CODE_FREQ, $address);
    }


    /**
     * 获取IP发送验证码Key
     * @param $ip
     * @return string
     */
    public static function getIpSendCodeKey($ip){
        return sprintf(self::IP_SEND_CODE_FREQ, $ip);
    }





}