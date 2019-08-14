<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\AccountController;
use App\Services\AppService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IndexController extends AccountController
{


    public function setting(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'share_notify'  => 'filled|boolean',
                'comment_notify'  => 'filled|boolean',
                'like_notify'  => 'filled|boolean',
                'fan_notify'  => 'filled|boolean',
                'system_notify'  => 'filled|boolean',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::setting($params);
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
                'type'      => [
                    'filled',
                    Rule::in(MessageService::$typeArray)
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::getMessageList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function commentAndReply(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::getCommentAndReply($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function count(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'type'      => [
                    'filled',
                    Rule::in(MessageService::$typeArray)
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::getNewMessageCount($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function read(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'id'            => 'required|int|min:1'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::readMessage($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function readAll(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'type'            => [
                    'filled',
                    Rule::in(MessageService::$typeArray)
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MessageService::readAll($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }



}