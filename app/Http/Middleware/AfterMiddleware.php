<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use App\Libs\Aes;
use App\Libs\Util;


class AfterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    private $_unsetList = [
       'uuid' , 'password','newPassword','rePassword'
    ];

    public function handle($request, Closure $next)
    {
        if (!env('APP_ENCRYPTION')) {
            $response = $next($request);
            return $response;
        } else {
            if (in_array(Util::getIp() , explode(',', env('WALLET_IP'))) && in_array($request->path(), ['api/wallet/transferIn','api/wallet/confirm','api/wallet/out'])) {
                $response = $next($request);
                return $response;
            }

            $response = $next($request);
            $uuid     = $request->input('uuid');
            $data     = $request->input();
            foreach ($this->_unsetList as $item){
                if(isset($data[$item])){
                    unset($data[$item]);
                }
            }
            $logMsg = $uuid . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $request->path() .' - request:' . json_encode($data) . ' - response:' . json_encode($response->original);
            if(isset($response->original['code'])){
                Log::channel('request')->info($logMsg);
            } elseif(!empty($response->exception)) {
                Log::info($logMsg);
            }
            $encode = (new Aes())->encrypt($response->getContent());
            $response->setContent($encode);
            return $response;    
        }
    }
}
