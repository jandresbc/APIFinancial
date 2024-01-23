<?php

namespace App\Http\Controllers;

use App\Models\CrCore\Ombu_cc_prestamos;
use App\Models\CrCore\Ombu_solicitudes;
use Illuminate\Http\Request;
use App\Models\CrCore\prod_model;
use App\Models\CrCore\prod_product_shop;
use App\Models\CrCore\prod_products;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProduct(Request $request): JsonResponse
    {
        try{
            if(count($request->all()) > 0){
                $brand = prod_model::where("marca_id", "=", $request->id_brand)->get();
                $loan =  Ombu_cc_prestamos::where("id","=", $request->id_prestamo)->first();
                $solicitude = Ombu_solicitudes::where("id", "=", $loan->solicitud_id)->first();
                $quota_initial = json_decode($solicitude->data, false, 512, JSON_THROW_ON_ERROR);
                $list = [];
                foreach($brand as $data_brand){
                    $prod = prod_products::where("modelo_id", "=", $data_brand->id)->first();
                    if($prod){
                        $shop = prod_product_shop::where("id_producto","=", $prod->id)->where("id_tienda", "=", $quota_initial->tienda_id)->first();
                        if($shop){
                            $list[] = [
                                'price' => (int)$shop->precio,
                                'name' => $prod->nombre,
                                'id_prod' => $prod->id,
                                'brand' => $data_brand->marca_id,
                                'model' => $data_brand->id,
                                'quotaI' => (int)$quota_initial->cuota_inicial/100
                            ];
                        }
                    }
                }
                return response()->json([
                    'data' => $list,
                    'message' => 'success',
                    'code' => 200,
                    'status' => true
                ]);
            }

            return response()->json([
                'message' => 'data null',
                'code' => 400,
                'status' => false
            ], 400);
        }catch(Exception $e){
            return response()->json([
                'message' => 'A system error has occurred, please try again later.',
                'errorFile' => $e->getFile(),
                'errorMessage' => $e->getMessage(),
                'errorLine' => $e->getLine(),
                'status' => false
            ], 500);
        }
    }
}
