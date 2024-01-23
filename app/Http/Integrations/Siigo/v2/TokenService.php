<?php

namespace App\Http\Integrations\Siigo\v2;

use App\Http\Integrations\Siigo\v2\Adapters\TokenAdapter;
use App\Models\Siigo\Token;
use Exception;
use Carbon\Carbon;
use App\Exceptions\TokenInactiveException;

class TokenService
{
    private $tokenAdapter;
    public function __construct(TokenAdapter $tokenAdapter)
    {
        $this->tokenAdapter = $tokenAdapter;
    }

    public function get($apiVersion = 'v2')
    {        
        $token = Token::where('api_version', $apiVersion)->orderBy('created_at', 'desc')->firstOrNew();
        if($token->is_active === 0){
            throw new TokenInactiveException();
        }
        if($token->is_expired){
            $newToken = $this->tokenAdapter->get($apiVersion);
            if($newToken->ok())
            {
                $bodyToken = $newToken->json();
                $token->api_version = $apiVersion;
                $token->token = $bodyToken["access_token"];
                $token->token_type = $bodyToken["token_type"];
                $token->expires_in = Carbon::now()->addSeconds($bodyToken["expires_in"]);
                $token->id_user = 1;
                $token->save();
            }else{
                throw new Exception("error al crear token");
            }
        }

        return $token;
    }
}
