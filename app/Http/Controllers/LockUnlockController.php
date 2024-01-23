<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Integrations\AppSecurity;

class LockUnlockController extends Controller
{
    /**
     * @throws \JsonException
     */
    public function lockUnlock (): string
    {
        print_r('<=============================== INICIO DE EJECUCIÓN ===============================>');
        $appSecurity = new AppSecurity();

        $credits = DB::connection('mysql')->table('ombu_cc_prestamos')
            ->join('ombu_solicitudes', 'ombu_cc_prestamos.solicitud_id', '=', 'ombu_solicitudes.id')
            ->select('ombu_cc_prestamos.*', 'ombu_solicitudes.data as data_solicitud')
            ->where('ombu_cc_prestamos.estado', '!=', 'CANC')
            ->where('ombu_cc_prestamos.estado', '!=', 'ANUL')
            ->whereNotNull('ombu_cc_prestamos.nuovo_id')
            ->get();

        foreach ($credits as $item) {
            print_r('<=============================== INICIO REGISTRO ===============================>');
            $item->data_solicitud = json_decode($item->data_solicitud, true, 512, JSON_THROW_ON_ERROR);
            $quota = DB::connection('mysql')->table('ombu_cc_cuotas')
                ->where('prestamo_id', '=', $item->id)
                ->where('fecha_venc', '<=', date("Y-m-d",time()))
                ->whereNotNull('fecha_venc')
                ->whereNotNull('cuota_nro')
                ->get();

            if (count($quota) > 0){
                $payments = DB::connection('mysql')->table('pagos')
                    ->where('documento', '=', $item->data_solicitud['nro_doc'])
                    ->sum('monto');
                $totalMountQuota = 0;
                $currentQuota = 0;
                $mainQuota = 0;

                foreach ($quota as $quotaClient) {
                    $quotaClient->monto_mora = (float)number_format($quotaClient->monto_mora, 2);
                    $quotaClient->monto_cuota = (float)number_format($quotaClient->monto_cuota, 2, ',', '');
                    $totalMountQuota += $quotaClient->monto_mora + $quotaClient->monto_cuota;
                    $currentQuota = $quotaClient->cuota_nro;
                    $mainQuota = $currentQuota;
                }
                $currentQuota++;
                $newLockDate = DB::connection('mysql')->table('ombu_cc_cuotas')
                    ->where('cuota_nro', '=', $currentQuota)
                    ->where('prestamo_id', '=', $item->id)
                    ->get();

                $totalMountQuotaMin = $totalMountQuota * 0.9;
                $totalMountQuotaMin = (float)number_format($totalMountQuotaMin, 2, ',', '');
                $balance = $totalMountQuota - $payments;
                $result = '';
                if ($currentQuota >= 2) {
                    if ($payments >= $totalMountQuotaMin) {
                        $info_device = $appSecurity->infoDevice($item->nuovo_id);
                        if (isset($info_device['device_info']['locked']) && count($info_device) > 0 && $info_device['device_info']['locked']) {
                            if (isset($newLockDate[0])) {
                                echo $newLockDate[0]->fecha_venc;
                                $newLockDate[0]->fecha_venc = date('Y-m-d', strtotime($newLockDate[0]->fecha_venc.'+ 1 days'));
                                echo $newLockDate[0]->fecha_venc;
                                $result = $appSecurity->unlock($item->nuovo_id, $balance, 'Nuovo', $newLockDate[0]->fecha_venc);
                            } else {
                                $result = $appSecurity->unlock($item->nuovo_id, $balance, 'Nuovo');
                            }
                        }
                    } else {
                        print_r('VALOR PAGADO NO ES SUFICIENTE');
                    }
                } else if ($mainQuota === 1) {
                    $result = $appSecurity->unlock($item->nuovo_id, $balance, 'Nuovo', $newLockDate[0]->fecha_venc);
                }
                print_r($result);
                print_r('</br>');
                print_r([
                    'nuovo_id' => $item->nuovo_id,
                    'documento_cliente' => $item->data_solicitud['nro_doc'],
                    'Superior_90%' => $payments >= $totalMountQuotaMin ? 'true':'false',
                    'Pagado_al_dia' => $payments,
                    'Total_deuda' => $totalMountQuota,
                    'Diferencia' => $balance
                ]);
                print_r('<=============================== FIN REGISTRO ===============================>');
            }
        }

        print_r('<=============================== TERMINO LA EJECUCIÓN ===============================>');

        return 'success';
    }
}
