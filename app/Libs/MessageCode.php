<?php
namespace App\Libs;


class MessageCode {

    public static $count = 0;
    public static $translate = [];

    # 正常
    const UNDEFINED_ERROR               = 100;
    const SUCCESS                       = 200;
    const SYSTEM_ERROR                  = 250;  # 系统错误 请联系管理员
    const USER_NO_LOGIN                 = 300;  # 未登录
    const ILLEGAL_PARAMETER             = 400;  # 非法参数
    const ILLEGAL_REQUEST               = 404;  # 非法请求
    const FAILED                        = 201;  # 并非请求接口失败,表示数据为空
    const FREQUENT_OPERATION            = 500;

    #  ============== 业务层自定义Code ===================
    const PARAMS_ERROR                  = 3001;
    const SIGN_ERROR                    = 3002;
    const SELECT_DB_ERROR               = 3003;
    const SELECT_DB_WITH_NO_DATA        = 3004;
    const NO_DATA                       = 3005;
    const NO_PERMISSION                 = 3006;
    const ACTION_ABNORMAL               = 3007;
    const DB_ERROR                      = 3008;
    const DECODE_FAILED                 = 3009;

    #  ============== 用户中心 4000 - 4499 ===================
    const USER_SEND_CODE_FREQ_ERRER     = 4000;
    const IP_SEND_CODE_FREQ_ERRER       = 4001;
    const SEND_CODE_LOCKED              = 4002;
    const TEMPLATE_NOT_EXIST            = 4003;
    const CODE_NOT_MATCH                = 4004;
    const PASSWORD_NOT_MATCH            = 4005;
    const SEND_CODE_INTERVAL            = 4006;
    const USER_NOT_EXIST                = 4007;
    const USER_PASSWORD_ERROR           = 4008;
    const USER_LOGIN_FREQ_ERROR         = 4009;
    const USER_LOGIN_LOCKED             = 4010;
    const USER_ALREADY_EXIST            = 4011;
    const IP_REGISTER_LIMIT_ERROR       = 4012;
    const ACCOUNT_IS_FROZEN             = 4013;
    const PREFIX_LIST_EMPTY             = 4014;
    const OLD_PASSWORD_ERROR            = 4015;
    const LOGIN_TIMEOUT                 = 4016;
    const RESET_TOKEN_INVALID           = 4017;
    const USER_ID_ALREADY_VERIFIED      = 4018;
    const USER_ID_NOT_VERIFIED          = 4019;
    const USER_ID_TYPE_ERROR            = 4020;
    const USER_ADVICE_TYPE_ERROR        = 4021;
    const USER_ADVICE_FREQ_ERROR        = 4022;
    const INVALID_TOKEN                 = 4023;
    const USER_ALREADY_FOLLOWED         = 4024;
    const USER_NOT_FOLLOWED             = 4025;
    const USER_FOLLOWED_SELF            = 4026;
    const USER_NICK_MODIFY_FREQ         = 4027;
    const USER_NICK_EXIST               = 4028;
    const USER_KEYWORD_NONE             = 4029;
    const PASSWORD_VERIFY_ERROR         = 4030;
    const USER_AUTH                     = 4031;
    

    #  ============== 动态 4500 - 4999 ===================
    const CONTENT_IMAGE_EMPTY           = 4500;
    const MOMENT_NOT_EXIST              = 4501;
    const MOMENT_USER_NOT_FOLLOWED      = 4502;
    const MOMENT_ALREADY_LIKED          = 4503;
    const COMMENT_TOO_FREQ              = 4504;
    const MOMENT_NOT_LIKED              = 4505;
    const COMMENT_NOT_EXIST             = 4506;
    const COMMENT_NOT_MATCH_UID         = 4507;
    const MOMENT_SHARE_SELF             = 4508;
    const CONTENT_IMAGE_INVALID         = 4509;
    const IMAGE_SIZE_TOO_LARGE          = 4510;
    const MOMENT_NOT_MATCH              = 4511;
    const USER_MOMENT_REPORT            = 4512;
    const USER_ASSETS_PWD               = 4032;
    const USER_ASSETS_ERROR             = 4033;
    const USER_OUT_ADDRESS              = 4034;
    const USER_IS_AUTH                  = 4035;

    #  ============== 消息 5000  - 5499 ===================
    const MESSAGE_NOT_EXIST             = 5000;
    const MESSAGE_NOT_MATCH             = 5001;



    #  ============== 用户资产 5500 - 5999 ===================
    const ASSET_PARAMS_ERROR            = 5500;
    const ASSET_UPDATE_ERROR            = 5501;
    const ASSET_COIN_DISABLED           = 5502;
    const ASSET_NOT_ENOUGH              = 5503;
    const ASSET_COIN_NOT_EXIST          = 5504;
    const ASSET_DEDUCT_FAILED           = 5505;
    const ASSET_BALANCE_INSUFFICIENT    = 5506;
    const ASSET_OUT_MIN                 = 5507;
    const ASSET_OUT_MAX                 = 5508;








    const RED_PACK_MIN_ERROR            = 5900;
    const RED_PACK_ID_EMPTY             = 5901;
    const RED_PACK_NOT_EXIST            = 5902;
    const RED_PACK_ACTIVATE_FAILED      = 5903;
    const RED_PACK_UID_NOT_MATCH        = 5904;
    const RED_PACK_STATUS_ERROR         = 5905;
    const RED_PACK_PATCH_ERROR          = 5906;
    const RED_PACK_NUM_ERROR            = 5907;
    const RED_PACK_OVER                 = 5908;
    const RED_PACK_UID_LOCKED           = 5909;
    const RED_PACK_TIMEOUT              = 5910;
    const RED_PACK_ALREADY_GRAB         = 5911;

    #  ============== 钱包 6000 - 6999 ===================
    const WALLET_PARAMS_ERROR           = 6000;
    const WALLET_SIGN_NOT_MATCH         = 6001;
    const WALLET_ADDRESS_ERROR          = 6002;
    const TRANSFER_CONFIRM_FAILED       = 6003;
    const TXID_DUPLICATED               = 6004;
    const TRANSFER_NOT_EXIST            = 6005;
    const TRANSFER_NOT_MATCH_UID        = 6006;



    #  ============== 其他翻译 10000 - 11000 ===================
    const KEY_SHARE_RED_PACK            = 'share_get_red_pack';
    const KEY_TRANSFER_IN               = 'transfer_in';
    const KEY_TRANSFER_OUT              = 'transfer_out';
    const KEY_MOMENT_RED_PACK_COST      = 'moment_red_pack_cost';
    const KEY_MOMENT_RED_PACK_INCOME    = 'moment_red_pack_income';
    const KEY_MOMENT_RED_PACK_RETURN    = 'moment_red_pack_return';
    const KEY_MOMENT_RED_PACK_CANCEL    = 'moment_red_pack_cancel';
    const KEY_FOLLOWED                  = 'followed';


    #  ============== 业务层自定义Message ================
    
    

    public static function getMessageByCode($code , $lang = ''){

        $lang    = empty($lang) ? Lang::$default : $lang;
        $dir     = base_path() . '/app' . '/Libs/Lang/' .$lang. '.php';
        $list    = require_once $dir;
        if(isset($list[$code])){
            return $list[$code];
        }
        return $list[100];
    }


    public static function getTranslate($key, $lang = ''){
        $lang    = empty($lang) ? Lang::$default : $lang;
        if(isset(self::$translate[$lang])){
            $list = self::$translate[$lang];
        } else {
            $dir     = base_path() . '/app' . '/Libs/Lang/' .$lang. '.php';
            $list    = require_once $dir;
            self::$translate[$lang] = $list;
        }



        
        if(isset($list[$key])){
            return $list[$key];
        }
        return $key;
    }


}
