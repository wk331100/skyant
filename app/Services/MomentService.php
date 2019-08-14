<?php
namespace App\Services;

use App\Libs\MessageCode;
use App\Exceptions\ServiceException;
use App\Libs\RedisKey;
use App\Libs\Util;
use App\Models\UserFollowModel;
use App\Models\UserInfoModel;
use App\Models\UserMessageModel;
use App\Models\UserModel;
use App\Models\UserMomentCommentModel;
use App\Models\UserMomentLikeModel;
use App\Models\UserMomentModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class MomentService{

    const STATUS_NO = '0';
    const STATUS_YES = '1';

    const TYPE_NORMAL = '1';
    const TYPE_RED_PACK = '2';


    /**
     * 发布动态
     * @param $data
     * @return int
     * @throws ServiceException
     */
    public static function createMoment($data){

        if(empty($data['token']) || empty($data['type'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(empty($data['content']) && empty($data['image_1'])){
            throw new ServiceException(MessageCode::CONTENT_IMAGE_EMPTY);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        $insertData = [
            'uid' => $uid,
            'type' => $data['type'],
            'red_pack_id' => isset($data['red_pack_id']) ? $data['red_pack_id'] : 0,
            'content' => self::filterBadWord($data['content']),
            'create_time' => date("y-m-d H:i:s")
        ];

        for ($i = 1; $i <= 9; $i ++){
            $image = 'image_' . $i;
            if(isset($data[$image])){
                $insertData[$image] = $data[$image];
            }
        }
        $momentId = UserMomentModel::getInstance()->create($insertData);

        if($data['type'] ==  self::TYPE_RED_PACK){
            if (empty($data['red_pack_id'])) {
                throw new ServiceException(MessageCode::RED_PACK_ID_EMPTY);
            }

            if (!DB::table('red_pack')->where('id',$data['red_pack_id'])->first()) {
                throw new ServiceException(MessageCode::RED_PACK_ID_EMPTY);
            }

            $result = RedPackService::activeRedPack($data['red_pack_id'], $uid, $momentId);
            if(!$result){
                throw new ServiceException(MessageCode::RED_PACK_ACTIVATE_FAILED);
            }
        }
        return $momentId;
    }


    /**
     * 动态详情
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     * @throws ServiceException
     */
    public static function getMomentInfo($data){
        if(empty($data['moment_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted == '1'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        $momentInfo = Util::objToArray($momentInfo);
        if($momentInfo['parent_id'] != 0){
            for ($i = 1; $i <= 9; $i++){
                $key = 'image_' . $i;
                unset($momentInfo[$key]);
            }
            $originMoment = UserMomentModel::getInstance()->getInfo($momentInfo['parent_id']);
            $originUser = AccountService::getUserInfoByUid($originMoment->uid);
            $originMoment->nick = $originUser['info']['nick'];
            $originMoment->head_image = $originUser['info']['head_image'];
            if($originMoment->is_deleted == '0'){
                $momentInfo['origin_moment'] = $originMoment;
            } else {
                $momentInfo['origin_moment']['is_deleted'] = $originMoment->is_deleted;
            }
        }

        $momentInfo['comment_list'] = self::getCommentList($data);

        $userInfo = AccountService::getUserInfoByUid($momentInfo['uid']);
        $momentInfo['user_type'] = $userInfo['type'];
        $momentInfo['nick'] = $userInfo['info']['nick'];
        $momentInfo['head_image'] = $userInfo['info']['head_image'];
        if(isset($uid)){
            $momentInfo['relation'] = FollowService::checkUserRelation($momentInfo['uid'], $uid);
            $isLiked = UserMomentLikeModel::getInstance()->checkIsLiked($uid, $momentInfo['id']);
            $momentInfo['is_liked'] = $isLiked ? true : false;
        } else {
            $momentInfo['relation'] = FollowService::RELATION_NONE;
            $momentInfo['is_liked']  = false;
        }
        return $momentInfo;
    }

    /**
     * 获取评论列表
     * @param $data
     * @return array
     * @throws ServiceException
     */
    public static function getCommentList($data){
        if(empty($data['moment_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted == '1'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        $list =   UserMomentCommentModel::getInstance()->getCommentList($data['moment_id']);
        $result = [];
        if(!empty($list)){
            foreach ($list as $item){
                $userInfo = AccountService::getUserInfoByUid($item->comment_uid);
                $item->head_image = $userInfo['info']['head_image'];
                $item->comment_nick = $userInfo['info']['nick'];
                if($item->parent_uid){
                    $parentUserInfo = AccountService::getUserInfoByUid($item->parent_uid);
                    $item->parent_nick = $parentUserInfo['info']['nick'];
                }
                $result[] = $item;
            }
        }
        return $result;
    }


    /**
     * 点赞
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function like($data){
        if(empty($data['token']) || empty($data['moment_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted = '0'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        //检查是否已经点赞
        if(UserMomentLikeModel::getInstance()->checkIsLiked($uid, $momentInfo->id)){
            throw new ServiceException(MessageCode::MOMENT_ALREADY_LIKED);
        }

        $insertData = [
            'uid' => $uid,
            'moment_id' => $momentInfo->id,
            'create_time'   => date('Y-m-d H:i:s')
        ];

        $id = UserMomentLikeModel::getInstance()->create($insertData);
        if($id){
            UserMomentModel::getInstance()->increaseMomentCount($momentInfo->id, 'like');
            if($uid != $momentInfo->uid){
                $userInfo =  UserInfoModel::getInstance()->getInfo($uid);
                $messageData = [
                    'uid' => $momentInfo->uid,
                    'target_id' =>  $momentInfo->id,
                    'op_nick' =>  $userInfo->nick,
                    'op_image' =>  $userInfo->head_image,
                    'type' => MessageService::TYPE_LIKE,
                    'moment_id' => $momentInfo->id,
                    'moment_image' => $momentInfo->image_1,
                    'moment' =>$momentInfo->content,
                    'content' => '',
                    'create_time' => $insertData['create_time']
                ];
                return UserMessageModel::getInstance()->create($messageData);
            }
        }
        return $id;
    }


    /**
     * 分享动态
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function share($data){
        if(empty($data['token']) || empty($data['moment_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted == '1'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        //检查动态是否属于自己的
        if($momentInfo->uid == $uid){
            throw new ServiceException(MessageCode::MESSAGE_NOT_MATCH);
        }

        $insertData = [
            'parent_id' => $momentInfo->id,
            'uid' => $uid,
            'content' => !empty($data['content']) ? $data['content']: '',
            'create_time'   => date('Y-m-d H:i:s')
        ];

        $id = UserMomentModel::getInstance()->create($insertData);
        if($id){
            UserMomentModel::getInstance()->increaseMomentCount($momentInfo->id, 'share');
            if($momentInfo->uid != $uid){
                $userInfo =  UserInfoModel::getInstance()->getInfo($uid);
                $messageData = [
                    'uid' => $momentInfo->uid,
                    'target_id' =>  $id,
                    'op_nick' =>  $userInfo->nick,
                    'op_image' =>  $userInfo->head_image,
                    'type' => MessageService::TYPE_SHARE,
                    'moment_id' => $momentInfo->id,
                    'moment_image' => $momentInfo->image_1,
                    'moment' =>$momentInfo->content,
                    'content' =>  $insertData['content'],
                    'create_time' => $insertData['create_time']
                ];
                UserMessageModel::getInstance()->create($messageData);
            }
            //如果是红包动态执行抢红包
            if($momentInfo->type == self::TYPE_RED_PACK){
                try{
                    $grab = RedPackService::grabRedPack($uid, $momentInfo->red_pack_id, AssetService::EVENT_RED_PACK,MessageCode::KEY_SHARE_RED_PACK);

                    $result =  [
                        'status' => 1,
                        'amount' => $grab['amount'],
                        'coin' => $grab['coin'],
                        'desc' => 'success'
                    ];
                } catch (ServiceException $e){
                    $result =  [
                        'status' => $e->getCode(),
                        'amount' => 0.00000000,
                        'coin' => '',
                        'desc' => $e->getMessage()
                    ];
                }
                return $result;
            }
            return true;
        }
        return false;
    }

    /**
     * 评论
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function comment($data){
        if(empty($data['token']) || empty($data['moment_id']) || empty($data['comment'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted = '0'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        //评论评率限制
        $commentLimit = ConfigService::getConfig(ConfigService::USER_COMMENT_FREQ);
        $freqKey = RedisKey::getUserCommentFreqKey($uid . '_' . date('ymdHi'));
        $commentCount = Redis::get($freqKey);
        if(!$commentCount) {
            Redis::setex($freqKey, AccountService::MINUTE, 1);
        } elseif($commentCount >= $commentLimit){
            throw new ServiceException(MessageCode::COMMENT_TOO_FREQ);
        } else {
            Redis::incr($freqKey);
        }

        $userInfo = UserInfoModel::getInstance()->getInfo($uid);
        $insertData = [
            'comment_uid' => $uid,
            'moment_id' => $momentInfo->id,
            'comment_nick' => $userInfo->nick,
            'comment'   => self::filterBadWord($data['comment']),
            'create_time'   => date('Y-m-d H:i:s')
        ];

        $type = MessageService::TYPE_COMMENT;
        $messageUid = $momentInfo->uid;
        if(!empty($data['comment_id'])){
            $commentInfo = UserMomentCommentModel::getInstance()->getInfo($data['comment_id']);
            $insertData['parent_id'] = $data['comment_id'] ;
            $insertData['parent_uid'] = $commentInfo->comment_uid;
            $insertData['parent_nick'] = $commentInfo->comment_nick;
            $type = MessageService::TYPE_REPLY;
            $messageUid = $commentInfo->comment_uid;
        }

        $id = UserMomentCommentModel::getInstance()->create($insertData);
        if($id){
            UserMomentModel::getInstance()->increaseMomentCount($momentInfo->id, 'comment');
            if($messageUid != $uid || $uid != $momentInfo->uid
                || (isset($commentInfo) && $uid != $commentInfo->comment_uid)){
                $userInfo =  UserInfoModel::getInstance()->getInfo($uid);
                $messageData = [
                    'uid' => $messageUid,
                    'target_id' =>  $momentInfo->id,
                    'op_nick' =>  $userInfo->nick,
                    'op_image' =>  $userInfo->head_image,
                    'type' => $type,
                    'moment_id' => $momentInfo->id,
                    'moment_image' => $momentInfo->image_1,
                    'moment' =>$momentInfo->content,
                    'content' => $insertData['comment'],
                    'create_time' => $insertData['create_time']
                ];
                return UserMessageModel::getInstance()->create($messageData);
            }
        }
        return true;
    }

    /**
     * 取消点赞
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function cancelLike($data){
        if(empty($data['token']) || empty($data['moment_id'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查动态是否存在
        $momentInfo = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(empty($momentInfo) || $momentInfo->status == self::STATUS_NO || $momentInfo->is_deleted = '0'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        //检查是否已经点赞
        if(!UserMomentLikeModel::getInstance()->checkIsLiked($uid, $momentInfo->id)){
            throw new ServiceException(MessageCode::MOMENT_NOT_LIKED);
        }

        $isCanceled = UserMomentLikeModel::getInstance()->cancelLike($uid, $data['moment_id']);
        if($isCanceled){
            return UserMomentModel::getInstance()->decreaseMomentCount($momentInfo->id, 'like');
        }
        return false;
    }


    /**
     * 取消评论
     * @param $data
     * @return bool|int
     * @throws ServiceException
     */
    public static function cancelComment($data){
        if(empty($data['token']) || empty($data['comment_id']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查评论是否存在
        $comment = UserMomentCommentModel::getInstance()->getInfo($data['comment_id']);
        if(!$comment || $comment->is_deleted == '1'){
            throw new ServiceException(MessageCode::COMMENT_NOT_EXIST);
        }


        //判断是否是自己的评论
        if($comment->comment_uid != $uid){
            throw new ServiceException(MessageCode::COMMENT_NOT_MATCH_UID);
        }

        $momentInfo = UserMomentModel::getInstance()->getInfo($comment->moment_id);

        $updateData = [
            'is_deleted' => '1'
        ];
        $isCanceled = UserMomentCommentModel::getInstance()->update($updateData, $data['comment_id']);
        if($isCanceled){
            return UserMomentModel::getInstance()->decreaseMomentCount($momentInfo->id, 'comment');
        }
        return false;

    }


    /**
     * 删除动态
     * @param $data
     */
    public static function delete($data){
        if(empty($data['token']) || empty($data['moment_id']) ){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        //检查动态是否存在
        $moment = UserMomentModel::getInstance()->getInfo($data['moment_id']);
        if(!$moment || $moment->is_deleted == '1'){
            throw new ServiceException(MessageCode::MOMENT_NOT_EXIST);
        }

        //判断是否是自己的动态
        if($moment->uid != $uid){
            throw new ServiceException(MessageCode::MOMENT_NOT_MATCH);
        }

        $updateData = [
            'is_deleted' => '1'
        ];
        return UserMomentModel::getInstance()->update($updateData, $data['moment_id']);
    }

    /**
     * 用户主页
     * @param $data
     */
    public static function profile($data){
        $relation = FollowService::RELATION_NONE;
        if(!empty($data['token'])){
            //检查用户Token是否有效
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
            $relation = FollowService::RELATION_SELF;
        }

        if(!empty($data['uid'])){
            $info =  UserModel::getInstance()->getInfo($data['uid']);
            if(!$info){
                throw new ServiceException(MessageCode::USER_NOT_EXIST);
            }

            if(isset($uid)){
                $relation = FollowService::checkUserRelation($data['uid'], $uid);
            }
            $uid = $data['uid'];
        }

        $userBasicData = self::getUserBasicData($uid);
        $userInfo = AccountService::getUserInfoByUid($uid);

        $result = [
            'head_image'    => $userInfo['info']['head_image'],
            'nick'          => $userInfo['info']['nick'],
            'sign'          => $userInfo['info']['sign'],
            'user_type'     => $userInfo['type'],
            'moment_count'  => $userBasicData['moment_count'],
            'follow_count'  => $userBasicData['follow_count'],
            'fans_count'    => $userBasicData['fans_count'],
            'combination_count' => 0,
            'relation' => $relation,
            'moment_list' => self::getUserMomentList($data)
        ];

        return $result;
    }

    /**
     * 获取用户基础数据统计
     * @param $uid
     * @return array
     */
    public static function getUserBasicData($uid){
        $momentCount = UserMomentModel::getInstance()->momentCount($uid);
        $followCount = DB::table('user_follow')->where(['uid'=>$uid])->count();
        $fansCount   = DB::table('user_follow')->where(['followed_uid'=>$uid])->count();
        return [
            'moment_count' => $momentCount,
            'follow_count' => $followCount,
            'fans_count'   => $fansCount
        ];
    }


    /**
     * 获取自己动态列表
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
    public static function getUserMomentList($data){

        if(!empty($data['token'])){
            //检查用户Token是否有效
            $originUid = $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        } else {
            $originUid = 0;
        }

        if(!empty($data['uid'])){

            $info =  UserModel::getInstance()->getInfo($data['uid']);
            if(!$info){
                throw new ServiceException(MessageCode::USER_NOT_EXIST);
            }
            $uid = $data['uid'];
        }



        $list = UserMomentModel::getInstance()->getMomentList($uid);

        foreach ($list as $key => $item){
            $userInfo = AccountService::getUserInfoByUid($item->uid);
            $isLiked = UserMomentLikeModel::getInstance()->checkIsLiked($originUid, $item->id);
            $list[$key]->user_type = $userInfo['type'];
            $list[$key]->relation = FollowService::checkUserRelation($item->uid, $originUid);
            $list[$key]->nick = $userInfo['info']['nick'];
            $list[$key]->head_image = $userInfo['info']['head_image'];
            $list[$key]->is_liked = $isLiked ? true : false;
            if($item->parent_id != '0'){
                $parent = UserMomentModel::getInstance()->getInfo($item->parent_id);
                $parentUserInfo = AccountService::getUserInfoByUid($parent->uid);
                if($parent->is_deleted){
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'content' =>  '',
                        'image_1' => '',
                        'nick'    => '',
                        'head_image' => '',
                        'is_deleted' => $parent->is_deleted
                    ];
                } else {
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'uid'     => $parent->uid,
                        'content' =>  $parent->content,
                        'type'    => $parent->type,
                        'image_1' => $parent->image_1,
                        'nick'    => $parentUserInfo['info']['nick'],
                        'head_image' => $parentUserInfo['info']['head_image'],
                        'is_deleted' => $parent->is_deleted
                    ];
                }
            }
        }

        return $list->getCollection()->toArray();
    }

    /**
     * 获取我关注的用户列表
     * @param $data
     * @return mixed
     * @throws ServiceException
     */
    public static function getFollowedMomentList($data){
        if(empty($data['token'])){
            throw new ServiceException(MessageCode::PARAMS_ERROR);
        }

        //检查用户Token是否有效
        $uid = AccountService::checkUserLogin($data);
        if(!$uid){
            throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
        }

        if(!empty($data['uid'])){
            $info =  UserModel::getInstance()->getInfo($data['uid']);
            if(!$info){
                throw new ServiceException(MessageCode::USER_NOT_EXIST);
            }
            $uid = $data['uid'];
        }

        $uidArr = FollowService::getFollowCacheRange($uid);
        //加入自己的动态
        $uidArr[] = $uid;
        $list = UserMomentModel::getInstance()->getFollowMomentList($uidArr);
        foreach ($list as $key => $item){
            $userInfo = AccountService::getUserInfoByUid($item->uid);
            $isLiked = UserMomentLikeModel::getInstance()->checkIsLiked($uid, $item->id);
            $list[$key]->user_type = $userInfo['type'];
            $list[$key]->relation = FollowService::checkUserRelation($item->uid, $uid);
            $list[$key]->nick = $userInfo['info']['nick'];
            $list[$key]->head_image = $userInfo['info']['head_image'];
            $list[$key]->is_liked = $isLiked ? true : false;
            if($item->parent_id != '0'){
                $parent = UserMomentModel::getInstance()->getInfo($item->parent_id);
                $parentUserInfo = AccountService::getUserInfoByUid($parent->uid);
                if($parent->is_deleted){
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'content' =>  '',
                        'image_1' => '',
                        'nick'    => '',
                        'head_image' => '',
                        'is_deleted' => $parent->is_deleted
                    ];
                } else {
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'uid'     => $parent->uid,
                        'content' =>  $parent->content,
                        'type'    => $parent->type,
                        'image_1' => $parent->image_1,
                        'nick'    => $parentUserInfo['info']['nick'],
                        'head_image' => $parentUserInfo['info']['head_image'],
                        'is_deleted' => $parent->is_deleted
                    ];
                }
            }
        }

        return $list->getCollection()->toArray();
    }

    /**
     * 获取全部动态列表
     * @return mixed
     */
    public static function getMomentList($data){
        //检查用户Token
        if(!empty($data['token'])){
            $uid = AccountService::checkUserLogin($data);
            if(!$uid){
                throw new ServiceException(MessageCode::LOGIN_TIMEOUT);
            }
        }

        $list = UserMomentModel::getInstance()->getMomentList();
        
        if (empty($list)) {
            return [];
        }
        foreach ($list as $key => $item){
            $userInfo = AccountService::getUserInfoByUid($item->uid);
            $list[$key]->user_type = $userInfo['type'];
            $isLiked = false;
            $relation = FollowService::RELATION_NONE;

            if(isset($uid)){
                $isLiked = UserMomentLikeModel::getInstance()->checkIsLiked($uid, $item->id);
                $relation = FollowService::checkUserRelation($item->uid, $uid);
            }

            $list[$key]->is_liked = $isLiked ? true : false;
            $list[$key]->relation = $relation;
            $list[$key]->nick = $userInfo['info']['nick'];
            $list[$key]->head_image = $userInfo['info']['head_image'];

            if($item->parent_id != '0'){
                $parent = UserMomentModel::getInstance()->getInfo($item->parent_id);
                $parentUserInfo = AccountService::getUserInfoByUid($parent->uid);
                if($parent->is_deleted){
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'content' =>  '',
                        'image_1' => '',
                        'nick'    => '',
                        'head_image' => '',
                        'is_deleted' => $parent->is_deleted
                    ];
                } else {
                    $list[$key] -> parent = [
                        'id'      => $parent->id,
                        'uid'     => $parent->uid,
                        'content' =>  $parent->content,
                        'type'    => $parent->type,
                        'image_1' => $parent->image_1,
                        'nick'    => $parentUserInfo['info']['nick'],
                        'head_image' => $parentUserInfo['info']['head_image'],
                        'is_deleted' => $parent->is_deleted
                    ];
                }

            }
        }
        return $list->getCollection()->toArray();
    }


















    /**
     * 违禁词处理
     * @param $content
     * @return mixed
     */
    public static function filterBadWord($content){
        return $content;
    }


}
