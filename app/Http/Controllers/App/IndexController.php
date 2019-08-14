<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\AccountController;
use App\Libs\Upload;
use App\Services\AccountService;
use App\Services\AppService;
use App\Services\FollowService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IndexController extends AccountController
{

    public function version(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'platform'      => [
                    'required',
                    Rule::in(['android', 'ios'])
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = AppService::getCurrentVersion($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function info(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'platform'      => [
                    'required',
                    Rule::in(['android', 'ios'])
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = AppService::getAppInfo($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }



}