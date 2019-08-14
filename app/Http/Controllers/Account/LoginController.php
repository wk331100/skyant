<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\AccountController;
use App\Libs\GmsSms;
use App\Libs\Util;
use App\Services\AccountService;
use App\Services\GmsService;
use App\Services\LoginService;
use App\Services\RegisterService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LoginController extends AccountController
{

    public function index(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'type'          => 'required|string|max:12',
                'username'       => 'required|string|max:128',
                'password'      => 'required|string|between:8,20',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if(!Util::verifyPassword($params['password'])){
                throw new ServiceException(MessageCode::PASSWORD_VERIFY_ERROR);
            }
            $loginData = [
                'type'     => $params['type'],
                'prefix'   => isset($params['prefix']) ? $params['prefix'] : GmsSms::DEFAULT_PREFIX,
                'platform' => isset($params['platform']) ? $params['platform'] : '',
                'username' => $params['username'],
                'password' => $params['password'],
            ];
            
            return Response::json(LoginService::Login($loginData));
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function register(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'address'       => 'required|string|max:128',
                'type'          => 'required|string|max:12',
                'code'          => 'required|alpha_num|between:6,6',
                'invite_code'   => 'filled|alpha_num|between:6,8',
                'platform'      => [
                    'required',
                    Rule::in(['android', 'ios'])
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $regData = [
                'invite_code'  => isset($params['invite_code']) ? $params['invite_code'] : '',
                'prefix'  => isset($params['prefix']) ? $params['prefix'] : '',
                'address' => $params['address'],
                'type'    => $params['type'],
                'platform' => $params['platform'],
                'code' => $params['code'],
            ];
            $data = [
                'token' => RegisterService::register($regData)
            ];
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function sendCode(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'address'       => 'required|string|max:128',
                'type'          => 'required|string|max:12',
                'send_type'     => 'required|int|max:12',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $sendData = [
                'prefix'  => isset($params['prefix']) ? $params['prefix'] : '',
                'address' => $params['address'],
                'type'    => $params['type'],
                'send_type'    => $params['send_type'],
                'ip'      => Util::getIp()
            ];
            $data = AccountService::sendCode($sendData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function modifyPwd(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'oldPassword'   => 'required|string|between:8,20',
                'newPassword'   => 'required|string|between:8,20',
                'rePassword'    => 'required|string|between:8,20',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if(!Util::verifyPassword($params['newPassword'])){
                throw new ServiceException(MessageCode::PASSWORD_VERIFY_ERROR);
            }

            $sendData = [
                'token'         => $params['token'],
                'oldPassword'   => $params['oldPassword'],
                'newPassword'   => $params['newPassword'],
                'rePassword'    => $params['rePassword'],
            ];
            $data = AccountService::modifyPwd($sendData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function forgetVerify(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'code'          => 'required|alpha_num|between:6,6',
                'address'       => 'required|string|max:128',
                'type'          => 'required|string|max:12',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $verifyData = [
                'prefix'  => isset($params['prefix']) ? $params['prefix'] : GmsSms::DEFAULT_PREFIX,
                'address' => $params['address'],
                'type'    => $params['type'],
                'code' => $params['code'],
            ];
            $data = [
                'token' => AccountService::forgetVerify($verifyData)
            ];
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function resetPwd(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'newPassword'   => 'required|string|between:8,20',
                'rePassword'    => 'required|string|between:8,20',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if(!Util::verifyPassword($params['newPassword'])){
                throw new ServiceException(MessageCode::PASSWORD_VERIFY_ERROR);
            }

            $resetData = [
                'token'         => $params['token'],
                'newPassword'   => $params['newPassword'],
                'rePassword'    => $params['rePassword'],
            ];
            $data = AccountService::resetPwd($resetData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function logout(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            } 
            $logoutData = [
                'token'         => $params['token'],
            ];
            $data = LoginService::logout($logoutData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function prefixList(){
        try {
            $data = GmsService::getPrefixList();
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    /*
    * 重置资金密码
    */
    public function resetAssetsPwd(Request $request)
    {
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'newPassword'   => 'required|alpha_num|between:6,6',
                'rePassword'    => 'required|alpha_num|between:6,6',
                'oldPassword'   => 'filled|alpha_num|between:6,6',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if(!Util::verifyPassword($params['newPassword'])){
                throw new ServiceException(MessageCode::PASSWORD_VERIFY_ERROR);
            }
            $data = AccountService::resetAssetsPwd($params);
            return Response::json($data);

        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }
    /*
    * 忘记资金密码  
    */
    public function forgetAssetsPwd(Request $request)
    {   
        try {
                $params = $request->all();
                $validator = Validator::make($params, [
                    'token'         => 'required|string|size:32',
                    'newPassword'   => 'required|alpha_num|between:6,6',
                    'rePassword'    => 'required|alpha_num|between:6,6',
                    'code'          => 'required|alpha_num|between:6,6',
                    'address'       => 'required|string|max:128',
                    'type'          => 'required|string|max:12',

                ]);
                if ($validator->fails()) {
                    throw new ServiceException(MessageCode::PARAMS_ERROR);
                }
                $params['prefix']  = $params['prefix'] ?? '';
                $data = AccountService::forgetAssetsPwd($params);
                return Response::json($data);
            } catch (ServiceException $e) {
                return Response::error($e);
        }
    }
}