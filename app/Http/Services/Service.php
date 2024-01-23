<?php

namespace App\Http\Services;

use Illuminate\Http\JsonResponse;
use JetBrains\PhpStorm\ArrayShape;

class Service
{

    #[ArrayShape(['status' => "bool", 'code' => "int", 'message' => "", 'data' => "array"])] public function Ok ($message, $data = []): array
    {
        return [
            'status' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data
        ];
    }

    #[ArrayShape(['status' => "bool", 'code' => "int|mixed", 'message' => "", 'data' => "array"])] public function Error ($message, $data = [], $code = 500): array
    {
        return [
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
    }

    #[ArrayShape(['status' => "bool", 'code' => "int", 'message' => "", 'data' => "bool[]"])] public function RiskOk ($message, $document, $score = 0): array
    {
        return [
            'status' => true,
            'code' => 200,
            'message' => 'success',
            'data' => [
                'message' => $message,
                'validation' => true,
                'document' => $document,
                'score' => $score
            ]
        ];
    }

    #[ArrayShape(['status' => "bool", 'code' => "int", 'message' => "", 'data' => "bool[]"])] public function RiskRejected ($message, $document): array
    {
        return [
            'status' => true,
            'code' => 200,
            'message' => 'success',
            'data' => [
                'message' => $message,
                'validation' => false,
                'document' => $document
            ]
        ];
    }

}
