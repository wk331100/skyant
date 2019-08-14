<?php

namespace App\Http\Middleware;

use App\Libs\Aes;
use App\Libs\Lang;
use App\Libs\Util;
use Closure;

class BeforeMiddleware
{
    public $imagePath = [
        'moment/create',
        'account/uploadHeadImage',
	    'account/identityVerify',
    ];

    public function handle($request, Closure $next)
    {       

        if (!env('APP_ENCRYPTION')) {
            $data         = $request->all();
            $data['uuid'] = Util::createUuid();
            $data['lang'] = isset($data['lang']) ? Lang::validateLang($data['lang']) : Lang::$default;
            $request->replace($data);
            return $next($request);

        } else {
            if (in_array(Util::getIp() , explode(',', env('WALLET_IP'))) && in_array($request->path(), ['api/wallet/transferIn','api/wallet/confirm','api/wallet/out'])) {

                $data         = $request->all();
                $data['uuid'] = Util::createUuid();
                $data['lang'] = isset($data['lang']) ? Lang::validateLang($data['lang']) : Lang::$default;
                $request->replace($data);
                return $next($request);
            }

            $content          = $request->input('content');
            $data             = (new Aes())->decrypt($content);
            $data             = json_decode($data,true);
            $data['uuid']     = Util::createUuid();
            $data['lang']     = isset($data['lang']) ? Lang::validateLang($data['lang']) : Lang::$default;
            $data['key']      = $request->input('key');
            $data['aes_type'] = $request->input('aes_type');

            if(in_array($request->path(), $this->imagePath)){
                for ($i = 1; $i <= 9; $i++){
                    $image = $request->input('image_' . $i);
                    if(!empty($image)){
                        $data['image_' . $i] = $image;
                    }
                }
                foreach (['positive_image', 'aspect_image', 'back_image','headImage'] as $v) {
                    $tmp = $request->input($v);
                    if(!empty($tmp)){
                        $data[$v] = $tmp;
                    }
                }
            }
            $request->replace($data);
            return $next($request);
        }
    }
}
