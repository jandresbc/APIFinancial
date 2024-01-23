<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Http\Integrations\MessengerService;
use Illuminate\Http\JsonResponse;

class HablameController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException|GuzzleException
     */
    public function sendMessage (Request $request): JsonResponse {
        $messengerService = new MessengerService();
        $data = $request->all();
        $sendData = json_encode([
            'flash' => $data['flash'],
            'sc' => 890202,
            'request_dlvr_rcpt' => 0,
            'bulk' => $data['bulk']
        ], JSON_THROW_ON_ERROR);

        $result = $messengerService->sendMessageHab($sendData);

        return response()->json([
            'message' => 'success operation',
            'result' => $result
        ]);
    }
}
