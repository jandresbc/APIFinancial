<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckIpAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //$ip = $_SERVER['REMOTE_ADDR'];
        //Otra opción0
        $ipList = array('190.145.13.38', '127.0.0.1', '186.84.91.41');
        $ip = request()->ip();
        if(!in_array($ip, $ipList)){ // si es null, es porque no existe y retornamos la ruta
            return redirect()->route('filedIp');
        }
        //caso contrario seguimos con petición
        return $next($request);
    }
}
