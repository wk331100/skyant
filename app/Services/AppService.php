<?php
namespace App\Services;



use App\Models\AppInfoModel;
use App\Models\AppVersionModel;

class AppService{

    /**
     * 获取当前版本号
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    public  static function getCurrentVersion($data){
        $result = AppVersionModel::getInstance()->getCurrentVersion($data['platform']);
        if(isset($result->version)){
            $result->version_code = self::calVersionCode($result->version);
        }
        return $result;
    }


    /**
     * 根据版本号计算应用市场需要的version code
     * @param $version
     * @return float|int
     */
    public static function calVersionCode($version){
        $versionArr = explode('.', $version);
        return $versionArr['0'] * 10000 + $versionArr['1'] * 100 + $versionArr['2'];
    }

    /**
     * 获取APP信息
     * @param $data
     * @return mixed
     */
    public static function getAppInfo($data){
        $version = self::getCurrentVersion($data);
        $result['version'] = isset($version->version) ? $version->version : '';
        $list = AppInfoModel::getInstance()->getList();
        if(!empty($list)){
            foreach ($list as $item){
                $result[$item->key] =  [
                    'title' => $item->title,
                    'value' =>  $item->value
                ];
            }
        }
        return $result;
    }

}