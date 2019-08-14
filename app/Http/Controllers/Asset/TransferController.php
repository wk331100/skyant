<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AccountController;

use App\Services\AssetService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferController extends AccountController
{

    public function list(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'coin'          => 'filled|string',
                'type'          => ['required',
                    Rule::in([AssetService::TYPE_TRANSFER_IN, AssetService::TYPE_TRANSFER_OUT])
                ],
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = AssetService::getTransferList($params);
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
                'token'         => 'required|string|size:32',
                'transfer_id'   => 'required|numeric'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = AssetService::getTransferInfo($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }




}