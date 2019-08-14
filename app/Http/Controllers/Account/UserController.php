<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\AccountController;
use App\Libs\Upload;
use App\Libs\Util;
use App\Services\AccountService;
use App\Services\FollowService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\UserInfoModel;

class UserController extends AccountController
{

    public function getUserInfo(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = AccountService::getUserInfo($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function getNoticeStatus(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = AccountService::getUserNotice($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function modifyNotice(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $modifyData = [
                'token'             => $params['token'],
                'push_enabled'      => isset($params['push_enabled']) ? $params['push_enabled'] : '',
                'comment_push_enabled'   => isset($params['comment_push_enabled']) ? $params['comment_push_enabled'] : '',
                'concern_push_enabled'   => isset($params['concern_push_enabled']) ? $params['concern_push_enabled'] : '',
                'article_push_enabled'   => isset($params['article_push_enabled']) ? $params['article_push_enabled'] : '',
                'news_push_enabled'   => isset($params['news_push_enabled']) ? $params['news_push_enabled'] : '',
            ];
            $data = AccountService::updateUserNotice($modifyData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }
    
    public function identityVerify(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'real_name'     => 'required|string|max:24',
                'id_code'       => 'required|string|max:48',
                'id_type'       => 'required|integer|max:20'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if (!$uid = AccountService::checkUserLogin(['token'=>$params['token']])) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);   
            } else {

                if (!Util::isCreditNo($params['id_code'])) {
                    
                }
                if (UserInfoModel::getInstance()->isAuth($params['id_code'])) {
                    throw new ServiceException(MessageCode::USER_IS_AUTH);
                }
                $userInfo = UserInfoModel::getInstance()->getInfo($uid);
                if (-1 == $userInfo->is_verified) {
                    throw new ServiceException(MessageCode::USER_AUTH);
                }
                if (1 == $userInfo->is_verified) {
                    throw new ServiceException(MessageCode::PARAMS_ERROR);
                }
            }

            $upload = new Upload(Upload::TYPE_AUTH);
            foreach (['positive_image', 'aspect_image', 'back_image'] as $v) {
                if(isset($params[$v]) && $params[$v]->isValid()){
                    $params[$v] = $upload->uploadImage($params[$v]);
                } else {
                    throw new ServiceException(MessageCode::PARAMS_ERROR);
                }   
            }
            $data = AccountService::identityVerify($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function advice(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'type'          => 'required|integer|max:10',
                'contact_name'  => 'required|string|max:24',
                'contact_tel'   => 'required|string|max:48',
                'advice'        => 'required|string|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $adviceData = [
                'token'         => $params['token'],
                'type'          => $params['type'],
                'contact_name'  => $params['contact_name'],
                'contact_tel'   => $params['contact_tel'],
                'advice'        => $params['advice'],
            ];

            $upload = new Upload(Upload::TYPE_NORMAL_IMAGE);
            for ($i = 1; $i <= 5 ; $i ++){
                $file = 'image_' . $i;
                if(isset($params[$file]) && is_file($params[$file])){
                    $adviceData[$file] = $upload->uploadImage($params[$file]);
                } else {
                    break;
                }
            }
            $data = AccountService::advice($adviceData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function modifyUserInfo(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'nick'          => 'filled|alpha_dash|max:20',
                'sign'          => 'filled|string|max:250',
                'sex'           =>  [
                    'filled',
                    Rule::in(['1', '0','-1'])
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $infoData = [
                'token'         => $params['token'],
            ];

            if(isset($params['sign'])){
                $infoData['sign'] = $params['sign'];
            }

            if(isset($params['country'])){
                $infoData['country'] = $params['country'];
            }

            if(isset($params['nick'])){
                $infoData['nick'] = $params['nick'];
            }

            if(isset($params['sex'])){
                $infoData['sex'] = $params['sex'];
            }

            $data = AccountService::updateUserInfo($infoData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function uploadHeadImage(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'headImage'     =>  [
                    'required',
                    Rule::dimensions()->maxWidth(3000)->maxHeight(3000)->ratio(1.0),
                ]
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $upload = new Upload(Upload::TYPE_HEAD_IMAGE);

            if($params['headImage']->isValid()){
                $image = $upload->uploadImage($params['headImage']);
            }

            $updateData = [
                'token'         => $params['token'],
                'headImage'     => $image
            ];
            $data = AccountService::updateHeadImage($updateData);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function follow(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'followed_uid'  => 'required|int',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = FollowService::follow($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function unFollow(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'followed_uid'  => 'required|int',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = FollowService::unFollow($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function followList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'uid'           => 'filled|int',
                'page'          => 'filled|int|between:1,250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = FollowService::followList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function fansList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'uid'           => 'filled|int',
                'page'          => 'filled|int|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = FollowService::fansList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function searchUser(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'keyword'       => 'required|string|max:12',
                'page'          => 'filled|int|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $data = AccountService::searchUser($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

}