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
use App\Services\{AccountService,RegisterService,ConfigService};
use App\Models\{UserInfoModel,CoinModel,UserModel};
use App\Libs\Util;

class AddressController extends AccountController
{
    public function create(Request $request)
    {
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'coin'          => 'required|string|max:16',
                'title'         => 'required|string|max:32',
                'address'       => 'required|string|max:128',
                'pwd'          => 'required|alpha_num|between:6,6',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if (!$uid = AccountService::checkUserLogin($params)) {
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }

            if (!CoinModel::getInstance()->getCoinByName($params['coin'])) {
                throw new ServiceException(MessageCode::ASSET_COIN_NOT_EXIST);
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
            
            $number = ConfigService::getConfig(ConfigService::USER_BIND_ADDRESS);

            if (DB::table('user_out_address')->where(['coin'=>$params['coin']])->count() >= $number) {
                throw new ServiceException(MessageCode::USER_OUT_ADDRESS,[$number]);
            }

            if(!DB::table('user_out_address')->insertGetId([
                'uid'   => $uid,
                'coin'  =>strtolower($params['coin']),
                'title' => $params['title'],
                'address'=>$params['address'],
                'create_time'=>date('Y-m-d H:i:s'),
                'create_ip' => Util::getIp(),
            ])) {
                return Response::json(false);
            }
            return Response::json([]);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function list(Request $request)
    {
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'coin'          => 'filled|string|max:16',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if (!$uid = AccountService::checkUserLogin($params)) {
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
            if (!empty($params['coin'])) {
                $list = DB::table('user_out_address')->select('title','address','coin')->where(['uid'=>$uid,'coin'=>$params['coin'],'is_enable'=>1])->get();
            } else {
                $list = DB::table('user_out_address')->select('title','address','coin')->where(['uid'=>$uid,'is_enable'=>1])->get();
            }            
            return Response::json($list);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function del(Request $request)
    {
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'id'            => 'required|int|max:16',
            ]);

            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if (!$uid = AccountService::checkUserLogin($params)) {
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }

            if (!$addressInfo = DB::table('user_out_address')->where(['uid'=>$uid,'id'=>$params['id'],'is_enable'=>1])->first()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if (!DB::table('user_out_address')->where(['id'=>$addressInfo->id])->update(['is_enable'=>0])) {
                return Response::json(false);
            }
            return Response::json([]);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

}
