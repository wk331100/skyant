<?php
namespace App\Libs;

use App\Exceptions\ServiceException;

class Response{

    /**
     * 正常返回数据
     * @param $data
     * @return array
     */
    public static function json($data){
        if($data === false){
            return self::failed($data);
        }
        return self::success($data);
    }

    /**
     * 返回成功数据
     *
     * @param array $data
     * @param array $extends
     * @return array
     */
    public static function success($data = [], $extends = [] ){
        $result = [
            'code' => MessageCode::SUCCESS,
            'msg'  => MessageCode::getMessageByCode(MessageCode::SUCCESS),
            'data' => $data
        ];

        if (!empty($extends)) {
            return array_merge($result, $extends);
        }
        return $result;
    }

    /**
     * 返回失败数据
     * @param $data
     * @param array $extends
     * @return array
     */
    public static function failed($data = [], $extends = [] ){
        $result = [
            'code' => MessageCode::FAILED,
            'msg'  => MessageCode::getMessageByCode(MessageCode::FAILED),
            'data' => $data
        ];

        if (!empty($extends)) {
            return array_merge($result, $extends);
        }

        return $result;
    }

    /**
     * 返回异常数据
     * @param ServiceException $e
     * @param array $extends
     * @return array
     */
    public static function error($e, $extends = []){
        $results = [
            'code' => $e->getCode(),
            'msg'  => $e->getMessage()
        ];
        if (!empty($extends)) {
            return array_merge($results, $extends);
        }
        return $results;
    }

    /**
     * 根据状态码返回数据
     *
     * @param $code , $data
     * @return array
     */
    public static function getCode($code , $data = [] ){
         return [
            'code' => $code,
            'msg'  => MessageCode::getMessageByCode($code),
            'data' => $data,
        ];
    }

}