<?php

namespace App\Http\Integrations;

use App\Mail\AwsMailService;
use Illuminate\Support\Facades\Mail;
use Exception;

class AwsMessenger
{
    /**
     * @param $request / required object type StdClass and have in body: email, message and matter
     * @return void
     */
    public static function sendMail ($request): void
    {
        try {
            Mail::to($request->email)->locale($request->matter)->send(new AwsMailService($request));
        } catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * @param $request / required object type StdClass and have in body: phone, message and country_code
     * @return mixed
     */
    public static function sendSMS ($request): mixed
    {
        return \AWS::createClient('sns')->publish([
            'Message' => $request->message,
            'PhoneNumber' => $request->country_code . $request->phone
        ]);
    }
}
