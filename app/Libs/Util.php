<?php
namespace App\Libs;

class Util{


    //生成网站web_code
    public static function makeCode($num = 1, $length = 16, $uppercase = false) {
        // 字符集
        if($uppercase){
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        } else {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }
        $code = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            $code .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $code;
    }

    /**
     * 创建API KEY
     * @return mixed
     */
    public static function createApiKey(){
        return  self::makeCode(1,32);
    }

    /**
     * 创建Secret Key
     * @return mixed
     */
    public static function createSecretKey(){
        return self::makeCode(1,64);
    }

    /**
     * 创建验证码，默认6位
     * @param int $lenth
     * @return string
     */
    public static function createVerifyCode($lenth = 6){
        $chars = '0123456789';
        $randNum = '';
        for($i = 0; $i < $lenth; $i++){
            $randNum .= $chars[rand(0,9)];
        }
        return $randNum;
    }

    public static function createScore($time = ''){
        if($time){
            $score = $time - 1537446071;
        } else {
            $score = time() - 1537446071;
        }
        $microTime = explode(' ', microtime());
        return $score + $microTime['0'];
    }


    /**
     * 密码格式校验
     * @param $password
     * @return bool
     */
    public static function verifyPassword($password){
        $pattern = '/^[0-9a-zA-Z#-_*%!@]{6,20}$/';
        if(preg_match($pattern, $password)){
            return true;
        }
        return false;
    }


    /**
     * 创建随机码
     * @param int $lenth
     * @return string
     */
    public static function createRandChar($lenth = 8){
        return self::makeCode(1, $lenth);
    }

    /**
     * 创建密码随机码
     * @param int $lenth
     * @return string
     */
    public static function createPwdRand($lenth = 16){
        return self::makeCode(1, $lenth);
    }

    /**
     * 创建邀请码
     * @param int $lenth
     * @return string
     */
    public static function createInviteCode($lenth = 6){
        return self::makeCode(1, $lenth, true);
    }

    /**
     * 创建登陆Token
     * @param int $lenth
     * @return string
     */
    public static function createToken($lenth = 32){
        return self::makeCode(1, $lenth);
    }


    public static function createUuid($lenth = 20){
        $chars = '0123456789';
        $loop = $lenth - 10;
        $randNum = '';
        for($i = 0; $i < $loop; $i++){
            $randNum .= $chars[rand(0,9)];
        }
        return time() . $randNum;
    }

    #将数据库对象 转 数组
    public static function objToArray($obj = ""){
        return json_decode(json_encode($obj),true);
    }


    #判断该地址是否是钱包地址
    public static function isYbcoin($wallet){
        if(Validate::az09($wallet) && strlen($wallet) == 34 && substr($wallet, 0, 1) == 'Y'){
            return  true;
        }
        return false;
    }

    /**
     * 获取ip地址,无需判断代理ip等情况
     * @return mixed
     */
    public static function getIp(){
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return ($ip == '::1') ? '127.0.0.1' : $ip;
    }

    # 文件重命名
    public static function fileRename($name){
        $suffix_index = strrpos($name, '.');
        $code = rand(1000000000,9999999999);

        return date('Ymd').md5(substr($name, 0, $suffix_index).$code) . '.' . strtolower(substr($name, $suffix_index+1));
    }

    /**
     * 数组按某字段排序
     * @return mixed
     */
    public static function arrayOrderBy()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
    *$array:需要排序的数组
    *$keys:需要根据某个key排序
    *$sort:倒叙还是顺序
    */
    public static function arraySort($array,$keys,$sort='asc') {
        $newArr = $valArr = array();
        foreach ($array as $key=>$value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ?  asort($valArr) : arsort($valArr);//先利用keys对数组排序，目的是把目标数组的key排好序
        reset($valArr); //指针指向数组第一个值
        foreach($valArr as $key=>$value) {
            $newArr[$key] = $array[$key];
        }
        return $newArr;
    }


    /**
     * 返回格式化后的小数位数
     *
     * @param $number
     * @param int $limit
     * @return string
     */
    public static function getFormatFloatNumber($number, $limit = 8)
    {
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 8;
        }
        $formatString = sprintf("%%.%df", $limit);
        return sprintf($formatString, $number);
    }


    /**
     * 生成随机字符串
     * @return string
     */
    public static function genGuid() {
        static $i = 0;
        $i or $i = mt_rand ( 1, 0x7FFFFF );

        return sprintf ( "%08x%06x%04x%06x",
            /* 4-byte value representing the seconds since the Unix epoch. */
            time () & 0xFFFFFFFF,

            /* 3-byte machine identifier.
             *
             * On windows, the max length is 256. Linux doesn't have a limit, but it
             * will fill in the first 256 chars of hostname even if the actual
             * hostname is longer.
             *
             * From the GNU manual:
             * gethostname stores the beginning of the host name in name even if the
             * host name won't entirely fit. For some purposes, a truncated host name
             * is good enough. If it is, you can ignore the error code.
             *
             * crc32 will be better than Times33. */
            crc32 ( substr ( ( string ) gethostname (), 0, 256 ) ) >> 16 & 0xFFFFFF,

            /* 2-byte process id. */
            getmypid () & 0xFFFF,

            /* 3-byte counter, starting with a random value. */
            $i = $i > 0xFFFFFE ? 1 : $i + 1 );
    }


    /**
     * 从指定数组中，随机固定个数的值
     * @param $array
     * @param $num
     * @return array|bool
     */
    public static function randArray($array, $num){
        $count = count($array);
        if($count < $num){
            return $array;
        }

        $array = array_values($array);
        $result = [];
        $maxIndex = $count - 1;
        for ($i=0; $i < $num; $i++){
            $index = rand(0, $maxIndex);
            $result[] = $array[$index];
            unset($array[$index]);
            $maxIndex--;
            $array = array_values($array);
        }
        return $result;
    }


    /**
     * 隐藏号码中间的部分
     * @param $code
     * @return string
     */
    public static function hideCode($str, $replacement = '*', $start = 2){

            $len = mb_strlen($str,'utf-8');
            $length = $len-2*$start;
            if ($len > intval($start+$length)) {
                $str1 = mb_substr($str,0,$start,'utf-8');
                $str2 = mb_substr($str,intval($start+$length),NULL,'utf-8');
            } else {
                $str1 = mb_substr($str,0,1,'utf-8');
                $str2 = mb_substr($str,$len-1,1,'utf-8');    
                $length = $len - 2;        
            }
            $new_str = $str1;
            for ($i = 0; $i < $length; $i++) { 
                $new_str .= $replacement;
            }
            $new_str .= $str2;
            return $new_str;        
    }

    /**
     * 验证身份证号
     * @param $vStr
     * @return bool
     */
    public static function  isCreditNo($vStr)
    {
        $vCity = [
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91'
        ];
     
        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) {
             return false;
        }
     
        if (!in_array(substr($vStr, 0, 2), $vCity)) {
             return false;
        }
     
        $vStr     = preg_replace('/[xX]$/i', 'a', $vStr);
        $vLength = strlen($vStr);
     
        if ($vLength == 18) {
            $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
        } else {
            $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
        }
     
        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) {
             return false;
        }
        if ($vLength == 18) {
            $vSum = 0;
     
            for ($i = 17; $i >= 0; $i--) {
                $vSubStr = substr($vStr, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr, 11));
            }
     
            if ($vSum % 11 != 1) {
                return false;
            }
        }
     
        return true;
    }


}
