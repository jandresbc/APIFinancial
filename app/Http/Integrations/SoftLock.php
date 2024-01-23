<?php


namespace App\Http\Integrations;


use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;

class SoftLock
{

    /**
     * @throws GuzzleException
     */
    public function __construct()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . app_path() . '/Keys/softlock/credentials.json');
//        dd(env('GOOGLE_APPLICATION_CREDENTIALS'));

        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];

        $middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
        $stack = HandlerStack::create();
        $stack->push($middleware);

        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://www.googleapis.com',
            'auth' => 'google_auth'
        ]);

        $response = $client->get('drive/v2/files');

        dd(json_decode($response->getBody()));
    }

}
