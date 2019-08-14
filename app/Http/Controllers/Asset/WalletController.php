<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AccountController;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\{AccountService,RegisterService,ConfigService,AssetService,WalletService};
use App\Models\{UserModel,CoinModel,UserCoinModel};
use App\Libs\Util;

class WalletController extends AccountController
{
    public function create(Request $request)
    {
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'coin'          => 'required|string|max:16',
                'token'         => 'required|string|size:32',    
                'code'          => 'required|alpha_num|between:6,6',
                'address'       => 'required|string|max:128',
                'wallet'        => 'required|string|max:128',
                'type'          => 'required|string|max:12',
                'pwd'			=> 'required|alpha_num|between:6,6',
                'amount'        => 'required|numeric',
            ]);

            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $params['prefix']  = $params['prefix'] ?? '';

            if (!$uid = AccountService::checkUserLogin($params)) {
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }

            if (1 != DB::table('user_info')->where(['uid'=>$uid])->value('is_verified')) {
                throw new ServiceException(MessageCode::USER_ID_NOT_VERIFIED);   
            }

           	$userInfo = UserModel::getInstance()->getInfo($uid);
            if (empty($userInfo->assets_password)) {
             # 请完善资金密码
                throw new ServiceException(MessageCode::USER_ASSETS_PWD);
            } 
            if ($userInfo->assets_password != RegisterService::makeUserPassword($params['pwd'],$userInfo->pwd_rand)){
            # 资金密码错误
                throw new ServiceException(MessageCode::USER_ASSETS_ERROR);
            }

            # 检测是否冻结
            if (0 == $userInfo->status) {
            	throw new ServiceException(MessageCode::ACCOUNT_IS_FROZEN);
            }
            
            if (!$coinInfo = CoinModel::getInstance()->getCoinByName($params['coin'])) {
                throw new ServiceException(MessageCode::ASSET_COIN_NOT_EXIST);
            }

            if (0 == $coinInfo->transfer_out_enable) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if ($coinInfo->transfer_out_min > $params['amount']) {
                throw new ServiceException(MessageCode::ASSET_OUT_MIN , [floatval($coinInfo->transfer_out_min).' '.strtoupper($params['coin'])]);
            }

            if ($coinInfo->transfer_out_max < $params['amount']) {
                throw new ServiceException(MessageCode::ASSET_OUT_MAX , [floatval($coinInfo->transfer_out_max).' '.strtoupper($params['coin'])]);
            }

            $balance = UserCoinModel::getInstance($params['coin'])->getAsset($uid);

	        if($balance['balance'] < $params['amount']){
	           throw new ServiceException(MessageCode::ASSET_NOT_ENOUGH);
	        }
            
            if(!AccountService::checkCode($params)){
                throw new ServiceException(MessageCode::CODE_NOT_MATCH);
            } 

        	if(UserCoinModel::getInstance($params['coin'])->outAsset($uid,$params['wallet'],$params['amount'])) {
        		return Response::json([]);
        	}
        	return Response::json([false]);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function out(Request $request)
    {   
        try {
            $params = $request->all();            
            
            if (empty($params) || !$params) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if (empty($params['address']) || empty($params['coin']) || empty($params['number']) || empty($params['timestamp']) || empty($params['txid']) || empty($params['uid']))  {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            
            $data = WalletService::out($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function cancel(Request $request)
    {   
        try {
            $params = $request->all();
            
            if (empty($params) || !$params) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if (empty($params['token']) || empty($params['id']))  {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = WalletService::cancel($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }

    }
}