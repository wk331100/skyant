<?php

namespace App\Libs;

use App\Models\EmailSendModel;
use Illuminate\Support\Facades\Redis;

class GmsEmail{

    

    public function send($data){

        if(empty($data['subject']) || empty($data['html']) || empty($data['to'])){
            return false;
        }

        $sendData = [
            'to'        => $data['to'],
            'code'      => $data['code'],
            'subject'   => $data['subject'],
            'html'      => $data['html'],
            'from'      => env('EMAIL_FROM'),
            'create_time' => date('Y-m-d H:i:s')
        ];
        $sendData['id'] = EmailSendModel::getInstance()->create($sendData);
        $redisData = json_encode($sendData);
        return Redis::lpush(RedisKey::EMAIL_QUEUE, $redisData);
    }
}