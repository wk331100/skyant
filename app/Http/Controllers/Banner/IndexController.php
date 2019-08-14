<?php

namespace App\Http\Controllers\Banner;

use App\Http\Controllers\BannerController;
use Illuminate\Http\Request;
use App\Libs\Response;
use App\Exceptions\ServiceException;
use App\Libs\MessageCode;
use App\Models\BannerModel;


class IndexController extends BannerController
{   
    public function index(Request $request)
    {	
    	try {
    		$limit = (int)$request->input('limit', 10);
	    	$page  = abs($request->input('page', 1));
	    	$res   = BannerModel::getInstance()->list($limit , $page , 0);
            return Response::success($res);
        } catch (ServiceException $e) {

            return Response::error($e);
        }	
    }

    public function findDynamic(Request $request)
    {
        try {
            $limit = (int)$request->input('limit', 10);
            $page  = abs($request->input('page', 1));
            $res   = BannerModel::getInstance()->list($limit , $page , 1);
            return Response::success($res);
        } catch (ServiceException $e) {

            return Response::error($e);
        }   
    }
    
}