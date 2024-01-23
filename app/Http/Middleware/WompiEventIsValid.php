<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WompiEventIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $event = $request->all();

        $wompiChecksum = $event['signature']['checksum'];

        $localChecksum = '';

        $concatChecksum = '';

        for ($i=0; $i < count($event['signature']['properties']); $i++) {
            $keys = explode('.', $event['signature']['properties'][$i]);
            $concatChecksum = $concatChecksum . $event['data'][$keys[0]][$keys[1]];
        }

        $concatChecksum = $concatChecksum . $event['timestamp'] . env('WOMPI_EVENT_KEY');
        
        $localChecksum = hash("sha256", $concatChecksum);

        if ($localChecksum !== $wompiChecksum) {
            return response()->json('You are not authorized to make this request', 401);
        }

        return $next($request);
    }
}
