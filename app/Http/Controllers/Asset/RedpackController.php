<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AccountController;
use App\Services\RedPackService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class RedpackController extends AccountController
{

    public function coin(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::getRedPackCoinList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function create(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'coin'          => 'required|string',
                'amount'        => 'required|numeric',
                'number'        => 'required|integer',
                // 'pwd'           => 'required|alpha_num|between:6,6',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::create($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function config(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::getRedPackConfig($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function cancel(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'red_pack_id'   => 'required|numeric',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::cancelRedPack($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function list(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'coin'          => 'required|string',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::list($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function coinSum(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'type'          => ['required',
                    Rule::in([RedPackService::RED_PACK_RECEIVE, RedPackService::RED_PACK_SEND])
                ],
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::coinSum($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function grabList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::grabList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function flow(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = RedPackService::flow($params);
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
                'red_pack_id'   => 'required|numeric',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::info($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function rules(){
        return View::make('redpack.rules');
    }

    public function active(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [

            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RedPackService::activeRedPack($params['red_pack_id'], $params['uid']);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }
}
