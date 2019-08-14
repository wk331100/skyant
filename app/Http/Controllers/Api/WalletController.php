<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AccountController;
use App\Libs\Upload;
use App\Services\AccountService;
use App\Services\AppService;
use App\Services\FollowService;
use App\Services\MomentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use App\Libs\Aes;

class WalletController extends AccountController
{

    public function transferIn(Request $request){
        try {

            //第一步，检查参数
            $params = $request->all();
            
            if (empty($params) || !$params) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if (empty($params['address']) || empty($params['coin']) || empty($params['number']) || empty($params['timestamp']) || empty($params['txid']))  {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = WalletService::transferIn($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function confirm(Request $request){
        try {
            $params = $request->all();
            
            if (empty($params) || !$params) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            if (empty($params['address']) || empty($params['coin']) || empty($params['confirm']) || empty($params['number']) || empty($params['timestamp']) || empty($params['txid']))  {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = WalletService::confirm($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function test(){
        return View::make('test.wallet');
    }

    public function aes(Request $request){
        $key = $request->input('key');
        $type = $request->input('type');
        $data = [];
        if(!empty($key)){
            $AES = new Aes();
            if($type == 'decode'){
                return Response::json($AES->decrypt($key));
            } elseif($type == 'encode'){
                return Response::json($AES->encrypt($key));
            }
        } else {
            return View::make('test.aes', $data);
        }

    }



}