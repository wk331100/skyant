<?php

namespace App\Http\Controllers\Find;

use App\Http\Controllers\AccountController;
use App\Libs\Upload;
use App\Services\AccountService;
use App\Services\AppService;
use App\Services\FollowService;
use App\Services\MomentService;
use App\Services\RecommendService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecommendController extends AccountController
{

    public function recommendUser(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RecommendService::getRecommendUser($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function recommendMoment(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = RecommendService::getRecommendMoment($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }




}