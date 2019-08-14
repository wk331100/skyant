<?php
namespace App\Services;



use App\Exceptions\ServiceException;
use App\Libs\Curl;
use App\Libs\MessageCode;
use App\Libs\RedisKey;
use App\Libs\Util;
use App\Models\CoinModel;
use App\Models\UserAssetLogModel;
use App\Models\UserCoinModel;
use App\Models\UserTransferModel;
use App\Models\UserWalletAddressModel;
use Illuminate\Support\Facades\Redis;

class AssetService{

    const EVENT_RED_PACK = 'red_pack';
    const EVENT_TRANSFER_IN = 'transfer_in';
    const EVENT_TRANSFER_OUT = 'transfer_out';
    const TRANSFER_INNER     = 'transfer_inner';
    const TYPE_TRANSFER_IN   = '1';
    const TYPE_TRANSFER_OUT  = '2';

    const BZ_PRICE_URL = 'https://apiv2.bitz.com/Market/currencyCoinRate';


    /**
     * 获取币种价格
     * @param $coin
     * @return mixed
     */
    public static function getCoinPrice($coin){
        $priceKey = RedisKey::COIN_PRICE;
        $price = Redis::hget($priceKey, $coin);
        return json_decode($price , true);
        

    }


    /**
     * 添加资产[来自于钱包]
     * @param $data
     * @return bool
     * @throws ServiceException
     */
    public static function addAsset($data, $uid){
        if(empty($data['coin'] || empty($data['number']) || $data['event']) ){
            throw new ServiceException(MessageCode::TRANSFER_CONFIRM_FAILED);
        }
        $desc = isset($data['desc']) ? $data['desc'] : '';
        return  UserCoinModel::getInstance($data['coin'])->addAsset($uid, $data['number'], $data['event'], $desc);
    }


    public static function freezeAsset(){

    }


    /**
     * 扣除用户资产
     * @param $data
     * @param $uid
     * @return bool
     * @throws ServiceException
     */
    public static function deductAsset($data, $uid){
        if(empty($data['coin'] || empty($data['number']) || $data['event'])){
            throw new ServiceException(MessageCode::TRANSFER_CONFIRM_FAILED);
        }
        $desc = isset($data['desc']) ? $data['desc'] : '';
        return UserCoinModel::getInstance($data['coin'])->deductAsset($uid, $data['number'], $data['event'], $desc);
    }

    public static function deductFrozenAsset(){

    }

    /**
     * 获取用户资产
     * @param $data
     * @return array
     * @throws ServiceException
     */
    public static function getBalance($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        if(!empty($data['coin']) && !CoinModel::getInstance()->checkCoinEnabled($data['coin'])){
            throw new ServiceException(MessageCode::TXID_DUPLICATED);
        }

        //没有指定币种，则使用全部激活的币种
        if(empty($data['coin'])){
            $coinList = CoinModel::getInstance()->getEnabledCoinArr();
        } else {
            $coinList = [$data['coin']];
        }

        $balance = [
            'cny_total'=>0,
            'usd_total'=>0,
        ];
        if(!empty($coinList)){
            foreach ($coinList as $coin){
                $asset = UserCoinModel::getInstance($coin)->getAsset($uid);
                $coinPrice = self::getCoinPrice($coin);
                $asset['usd'] = bcmul($coinPrice['usd'], $asset['balance'], 8);
                $asset['cny'] = bcmul($coinPrice['cny'], $asset['balance'], 8);
                $coinInfo = self::getCoinInfo($asset['coin']);
                $asset['icon'] = $coinInfo['icon'];
                $asset['full_name'] = $coinInfo['full_name'];
                $balance['list'][] = $asset;
                $balance['cny_total'] = bcadd($asset['cny'], $balance['cny_total'], 8);
                $balance['usd_total'] = bcadd($asset['usd'], $balance['usd_total'], 8);
            }
        }
        return $balance;
    }


    /**
     * 获取币种信息
     * @param $coin
     * @return \Illuminate\Database\Eloquent\Model|mixed|null|object|static
     */
    public static function getCoinInfo($coin){
        $coinListKey = RedisKey::COIN_LIST_CACHE;
        $coinCache = Redis::hget($coinListKey, $coin);

        if(!empty($coinCache)){
            return json_decode($coinCache, true);
        }

        $coinInfo = CoinModel::getInstance()->getCoinByName($coin);
        Redis::hset($coinListKey, $coin, json_encode($coinInfo));
        return Util::objToArray($coinInfo);
    }


    /**
     * 获取用户充币地址
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
    public static function getAddress($data){
        if(empty($data['token']) || empty($data['coin'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $addressInfo = UserWalletAddressModel::getInstance()->getAddress($uid, $data['coin']);
        if($addressInfo){
            $address = $addressInfo->address;
        } else {
            //调用钱包服务
            $walletUrl = env('WALLET_URL').'index/getaddress';
            $curl = new Curl();
            $params = [
                'uid' => $uid,
                'coin' => $data['coin']
            ];
            $curl->setParams($params);
            $result = $curl->post($walletUrl);
            $result = json_decode($result, true);
            if($result && isset($result['code']) && $result['code'] == 200){
                $address = $result['data']['address'];
                $insertData = [
                    'uid' => $uid,
                    'coin' => $data['coin'],
                    'address' => $address,
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                UserWalletAddressModel::getInstance()->create($insertData);
            }
        }

        $coinInfo = AssetService::getCoinInfo($data['coin']);
        return [
            'uid'       => $uid,
            'coin'      => $data['coin'],
            'address' => $address,
            'icon'      => $coinInfo['icon'],
            'full_name' => $coinInfo['full_name']
        ];

    }


    /**
     * 获取冲提币列表
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
    public static function getTransferList($data){
        if(empty($data['token']) || empty($data['type'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        if(!empty($data['coin'])){
            return UserTransferModel::getInstance()->getTransferList($uid, $data['type'], $data['coin']);
        }

        return UserTransferModel::getInstance()->getTransferList($uid, $data['type']);
    }


    /**
     * 获取冲提币详情
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     * @throws ServiceException
     */
    public static function getTransferInfo($data){
        if(empty($data['token']) || empty($data['transfer_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $info = UserTransferModel::getInstance()->getInfo($data['transfer_id']);
        if(empty($info)){
            throw new ServiceException(MessageCode::TRANSFER_NOT_EXIST);
        }

        if($info->uid != $uid){
            throw new ServiceException(MessageCode::TRANSFER_NOT_MATCH_UID);
        }

        $coinInfo = AssetService::getCoinInfo($info->coin);
        if($coinInfo){
            $info->full_name = $coinInfo['full_name'];
            $info->icon = $coinInfo['icon'];
        }
        return $info;
    }


    /**
     * 获取用户账单，目前是所有的流水
     * @return mixed
     * @throws ServiceException
     */
    public static function bill($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }
        $result = UserAssetLogModel::getInstance()->getUserAssetLog($uid);
        if(!empty($result)){
            foreach ($result as $key => $item){
                $result[$key]->desc = MessageCode::getTranslate($item->desc);
            }
        }
        return $result;
    }


    /**
     * 获取币种列表
     * @param $data
     * @return \Illuminate\Support\Collection
     * @throws ServiceException
     */
    public static function getCoinList($data){
        if(empty($data['token']) || empty($data['type'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }
        $list = CoinModel::getInstance()->getTransferCoinList($data['type']);
        if ($list) {
            foreach ($list as &$v) {
                $tmp  = UserCoinModel::getInstance($v->name)->getAsset($uid);
                $v->balance = $tmp['balance'];
            }
        }
        return $list;
    }



}