<?php

namespace App\Http\Controllers\Announcement;

use App\Http\Controllers\AnnouncementController;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Models\AnnouncementModel;


class IndexController extends AnnouncementController
{   
    public function list(Request $request)
    {	
    	try {
    		$limit = (int)$request->input('limit', 10);
	    	$page  = abs($request->input('page', 1));
	    	$res   = AnnouncementModel::getInstance()->list($limit , $page);
            return Response::success($res);
        } catch (ServiceException $e) {

            return Response::error($e);
        }	
    }
    
}