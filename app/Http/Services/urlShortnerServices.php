<?php
namespace  App\Http\Services;

use Illuminate\Support\Str;
use Exception;
use App\Models\CrCore\UrlShort;

class urlShortnerServices{
    public function generateURL($request){
        try{
            $randomKey = Str::random(9);
            $urlShort = new UrlShort();
            while($urlShort->where('url_key', $randomKey)->exists()){
                $randomKey = Str::random(9);
            }

            $new_url = app()->make('url')->to('/api/code/'.$randomKey);
            $urlShort->to_url = $request->link;
            $urlShort->url_key = $randomKey;
            $urlShort->new_url = $new_url;
            $urlShort->token = 0;
            $urlShort->save();

            return [
                'message' => 'succes',
                'new_url' => $new_url,
                'code' => 200,
                'status' => true,
            ];
        }catch(Exception $e){
            return [
                'message' => 'Error al procesar la información, vuelva a intentarlo.',
                'errorMessage' => $e->getMessage(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'code' => 500,
                'status' => false
            ];
        }
    }
}
