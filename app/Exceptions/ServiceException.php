<?php

namespace App\Exceptions;
use App\Libs\MessageCode;
use Exception;

class ServiceException extends Exception {


    public function __construct($code = 3001, $params = [])
    {
        $message = MessageCode::getMessageByCode($code);
        if(!empty($params)){
            $message = vsprintf($message, $params);
        }
        parent::__construct($message, $code);
    }

}
