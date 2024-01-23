<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\CrCore\Ombu_solicitudes;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class RequestsController extends Controller
{
    public function index (Request $request): JsonResponse {
        $search = trim($request->get('search'));
        $result = DB::table('ombu_solicitudes')
            ->where(DB::raw('JSON_EXTRACT(`data`, "$.paso")'), '=', 'enrolar')
            ->orWhere(DB::raw('JSON_EXTRACT(`data`, "$.paso")'), '=', 'reconocerId')
            ->orWhere(DB::raw('JSON_EXTRACT(`data`, "$.paso")'), '=', 'enrolarQR')
            ->orWhere(DB::raw('JSON_EXTRACT(`data`, "$.paso")'), '=', 'dataCredito')
            ->select('data', 'id', 'fechahora', 'estado_solicitud')
            ->orderByDesc('fechahora')
            ->limit(500)
            ->get();
        try {
            foreach ($result as $item) {
                $item->data = json_decode($item->data, true, 512, JSON_THROW_ON_ERROR);
            }
            $requestData = $result->toArray();

            $requestData = array_map(static function ($item) {
                $item->data['imei'] = array_key_exists('imei', $item->data) ? $item->data['imei'] : '';
                return $item;
            }, $requestData);

            if ($search !== '') {
                $requestData = array_filter($requestData, static function ($item) use ($search) {
                    return $item->data['nro_doc'] === $search;
                });
            }
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Success data charged',
                'data' => [
                    'result' => $requestData
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }

    /**
     * @throws \JsonException
     */
    public function skipStep (Request $request): JsonResponse
    {
        $id = trim($request->get('id'));
        $data = json_decode(trim($request->get('data')), true, 512, JSON_THROW_ON_ERROR);
        try {
            $result = Ombu_solicitudes::find($id);
            switch ($data['paso']) {
                case 'dataCredito':
                    $data['paso'] = 'reconocerId';
                    break;
                case 'reconocerId':
                    $data['paso'] = 'enrolar';
                    break;
            }
            $result->data = json_encode($data, JSON_THROW_ON_ERROR);
            $result->save();

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Success data charged',
                'data' => [
                    $result
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }

    }

    /**
     * @throws \JsonException
     */
    public function cancelRequest (Request $request): JsonResponse
    {
        $id = trim($request->get('id-status'));
        try {
            $result = Ombu_solicitudes::find($id);
            $result->estado_solicitud = 'RECH';
            $result->save();
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Request cancel request is successful.',
                'data' => [
                    $result
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }

    /**
     * @throws \JsonException
     */
    public function updateImei (Request $request): JsonResponse
    {
        $id = trim($request->get('idImei'));

        try {
            $result = Ombu_solicitudes::find($id);
            $imei = trim($request->get('imei'));
            $dataRequest = $result->toArray();
            $dataRequest['data'] = json_decode($dataRequest['data'], true, 512, JSON_THROW_ON_ERROR);
            $dataRequest['data']['imei'] = $imei;
            $result->data = json_encode($dataRequest['data'], JSON_THROW_ON_ERROR);
            $result->save();

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Request cancel request is successful.',
                'data' => [
                    $result
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'A system error has occurred, the transaction has been denied, please try again later.',
                'errorDescription' => $e->getMessage(),
                'errorData' => [
                    'error' => $e
                ]
            ], 500);
        }
    }
}
