<?php

namespace App\Http\Controllers\Moment;

use App\Http\Controllers\AccountController;
use App\Models\UserMomentModel;
use App\Libs\Upload;
use App\Services\AccountService;
use App\Services\AppService;
use App\Services\FollowService;
use App\Services\MomentService;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Libs\Util;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IndexController extends AccountController
{

    public function create(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'content'       => 'filled|string|max:250',
                'type'          => ['filled',
                    Rule::in([MomentService::TYPE_NORMAL, MomentService::TYPE_RED_PACK])
                ],
                'red_pack_id'   => 'filled|string'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            $createData = [
                'content' => isset($params['content']) ? $params['content'] :'',
                'token'     => $params['token'],
                'type'      => isset($params['type']) ? $params['type'] : MomentService::TYPE_NORMAL,
                'red_pack_id'      => isset($params['red_pack_id']) ? $params['red_pack_id'] : '',
            ];

            $upload = new Upload(Upload::TYPE_NORMAL_IMAGE);
            for ($i = 1; $i <= 9 ; $i ++){
                $file = 'image_' . $i;
                if(isset($params[$file]) && $params[$file]->isValid()){
                    $createData[$file] = $upload->uploadImage($params[$file]);
                } else {
                    break;
                }
            }
            $data = MomentService::createMoment($createData);
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
                'token'         => 'filled|string|size:32',
                'moment_id'     => 'required|int'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::getMomentInfo($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function like(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'moment_id'     => 'required|int'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::like($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function share(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'moment_id'     => 'required|int',
                'content'       => 'filled|string|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::share($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function comment(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'moment_id'     => 'required|int',
                'comment'       => 'required|string|max:255',
                'comment_id'    => 'filled|string|max:255'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::comment($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function commentList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'moment_id'     => 'required|int',
                'p'             => 'filled|int|max:255'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::getCommentList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function cancelLike(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'moment_id'     => 'required|int'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::cancelLike($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function cancelComment(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'comment_id'     => 'required|int',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::cancelComment($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function delete(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'moment_id'     => 'required|int',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::delete($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }


    public function profile(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'uid'           => 'filled|int',
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::profile($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function followedList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'required|string|size:32',
                'uid'           => 'filled|int|min:1000000',
                'page'          => 'filled|int|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::getFollowedMomentList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function momentList(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'page'          => 'filled|int|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::getMomentList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    public function userMoment(Request $request){
        try {
            //第一步，检查参数
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'page'          => 'filled|int|max:250'
            ]);
            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $data = MomentService::getUserMomentList($params);
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }

    /* 
     * 举报列表
    */
    public function reportList(Request $request)
    {
        try {
            $data = DB::table('report_config')->select('cn','en','id')->where(['is_enable'=>1])->orderByDesc('sort')->get();
            return Response::json($data);
        } catch (ServiceException $e) {
            return Response::error($e);
        }

    }
    /*
    *  用户举报
    */
    public function report(Request $request)
    {
        try {
            $params = $request->all();
            $validator = Validator::make($params, [
                'token'         => 'filled|string|size:32',
                'moment_id'     => 'required|int',
                'report_id'     => 'required|int',
            ]);

            if ($validator->fails()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }

            if (!$uid = AccountService::checkUserLogin(['token'=>$params['token']])) {  
                throw new ServiceException(MessageCode::RESET_TOKEN_INVALID);
            }

            $momentInfo = UserMomentModel::getInstance()->getInfo($params['moment_id']);

            if(empty($momentInfo) || $momentInfo->status == 0 || $momentInfo->is_deleted == '1'){
                throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
            }

            if (!DB::table('report_config')->where(['id'=>$params['report_id'],'is_enable'=>1])->first()) {
                throw new ServiceException(MessageCode::PARAMS_ERROR);
            }
            $where = ['uid'=>$uid,'moment_id'=>$params['moment_id'],'report_id'=>$params['report_id']];
            if (!DB::table('user_moment_report')->where($where)->first()) {
                $where['create_time'] = date('Y-m-d H:i:s');
                $where['create_ip'] = Util::getIp();
                if (DB::table('user_moment_report')->insertGetId($where)) {
                    return Response::success([]);                    
                }
                return Response::error([]);
            }
            throw new ServiceException(MessageCode::USER_MOMENT_REPORT);
        } catch (ServiceException $e) {
            return Response::error($e);
        }
    }
}