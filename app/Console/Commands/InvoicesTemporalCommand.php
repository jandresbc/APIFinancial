<?php

namespace App\Console\Commands;

use App\Models\Siigo\City;
use App\Models\Municipio;
use App\Models\Siigo\Invoice;
use App\Models\Loan;
use App\Models\Siigo\QuotaTemporal;
use App\Http\Integrations\Siigo\v2\InvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InvoicesTemporalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices_temporal_creation:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correr la facturacion de forma temporal con los datos que estan cargados en la tabla ombu_cc_cuotas_facturacion';

    private $loanModel;

    /**
     * Create a new command instance.
     *
     * @return void
     */

   public function __construct(InvoiceService $invoiceService,
                               Invoice $invoice,
                               Municipio $geoCity,
                               City $city)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
        $this->invoice = $invoice;
        $this->geoCity = $geoCity;
        $this->city = $city;

        $this->duplicate = true;
    }

    public function handle()
    {
        return $this->alternative();
    }

    public function alternative()
    {
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Inicio del proceso ALTERNATIVO de creacion de facturas');
        $consecutiveNumber = Invoice::max('number');
        //$consecutiveNumber = 49869;
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Ultima factura: '.$consecutiveNumber);
        // Fechas en las que deben estar las cuotas
        list($start, $end) = $this->getDateStartAndEnd();

        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'. ' Fecha de facturacion: | '.$start.' -> '.$end);

        $quotas = QuotaTemporal::whereBetween('fecha_venc', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                                //->where('prestamo_id', 1)
                                // ->where('abono','>', 0)
                                ->where('facturado','=',0 )
                                //->groupBy('cedula')
                                ->orderBy('cuota_nro', 'ASC')
                                //->limit(200)
                                ->get();
        foreach ($quotas as $quota)
        {
            $this->interest_of_late_payment = 0;
            $this->monto_deuda = 0;

            $invoice = $this->invoice->where('loan_id', $quota->prestamo_id)->where('quota', $quota->cuota_nro)->where('identification', $quota->cedula)->first();

            // Si ya existe una factura continua con el proximo prestamo.
            if(isset($invoice)){
                // $this->line('['.Carbon::now()->format('Y-m-d H:i:s').']'.' El prestamo: '.$quota->prestamo_id. '- cuota: '.$quota->cuota_nro . '- Ya tiene una factura creada: '. $invoice->number);
                $this->line($invoice->identification.'|'.$invoice->last_name.'|'.$invoice->first_name.'|'.$quota->prestamo_id.'|'.$quota->cuota_nro.'|'.$invoice->due_date.'|'.$invoice->total_value.'|'.$invoice->number);
		        if($this->duplicate){
                    $invoice->update(['deleted_at' => Carbon::now()]);
                }else{
                    continue;
                }
            }
            // // Obtengo las cuotas previas que estan en mora.
            ($quota->cuota_nro == 1) ? $previousQuota = [] : $previousQuota = QuotaTemporal::where('cuota_nro', '<', $quota->cuota_nro)->where('prestamo_id', 1)->where('cedula', $quota->cedula)->where('estado', 'PEND')->get();

            $quota->nro_doc = $quota->cedula;
            $quota->tel_movil = $quota->telefono;
            $city = $this->setHeaderAccount($quota, $quota);
            //Si no hay ciudad sigue al proximo prestamo
            if(!$city) continue;

            $payload['options']['number'] = ++$consecutiveNumber;
            $payload['options']['date'] = '2022-02-11'; //Carbon::now()->format('Y-m-d');
            $payload['options']['observations'] = ($this->monto_deuda > 500) ? 'Queda un saldo pendiente de $'.number_format($this->monto_deuda, 2, ',', '.').'. ' . 'Si ya realizó su pago, favor hacer caso omiso.' : 'Si ya realizó su pago, favor hacer caso omiso.';
            $payload['items'] = $this->setDetailsItems($quota, $previousQuota);
            $payload['customer']['identification'] = $quota->cedula;
            $payload['customer']['first_name'] = $quota->nombre;
            $payload['customer']['last_name'] = $quota->apellido;
            $payload['customer']['email'] = $quota->email;

            $payload['loan']['id'] = $quota->prestamo_id;
            $payload['loan']['quota'] = $quota->cuota_nro;

            $payload['payments'] = [];

            $invoice = $this->invoiceService->create($payload['customer'], $payload['loan'], $payload['items'], $payload['payments'], $payload['options']);
            if(isset($invoice['Status']) && $invoice['Status'] != 200)
            {
                foreach ($invoice['Errors'] as $key => $error)
                {
                    $this->error('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Error al crear la factura: '. $quota->nombre .' '.$quota->apellido.' | '.$quota->nro_doc.' | prestamo: '.$quota->prestamo_id. '| cuota: '.$quota->cuota_nro .' | Error: ' .$error['Message']);
                    if( isset($error['Code']) && $error['Code'] == "already_exists")
                    {
                        return;
                    }
                    continue;
                }
            }
            $this->line($quota->cedula.'|'.$quota->apellido.'|'.$quota->nombre.'|'.$quota->prestamo_id.'|'.$quota->cuota_nro.'|'.$quota->fecha_venc.'|'.$invoice['payments'][0]['value'].'|'.$invoice['name']);

            dd($quota,$invoice);
        }
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Fin del proceso');

        dd($consecutiveNumber);

    }

    // SETTERS

    private function setHeaderAccount($client, $request)
    {
        $city = $this->getCityCodes($request->ciudad);
        if(!$city)
        {
            return false;
        }
        $response['Identification'] = $client->nro_doc;
        $response['FirstName'] = $client->nombre;
        $response['LastName'] = $client->apellido;
        $response['StateCode'] = $city->StateCode;
        $response['CityCode'] = $city->CityCode;
        $response['Address'] = $request->direccion;
        $response['Number'] = $client->tel_movil;
        $response['EMail'] = $client->email;
        $response['Indicative'] = config('Siigo.siigo_phone_indicative');

        return $response;
    }

    // GETTERS
    private function getCityCodes($cityName)
    {
        $geo_city = $this->geoCity->where('nombre', $cityName)->first();
        if(!isset($geo_city)){
            $this->error('Error al consultar la ciudad: '. $cityName . ' en la tabla geo_ciudades');
            return false;
        }
        $city = $this->city->where('CityName', 'like', '%' . $geo_city->nombre . '%')->where('StateName', 'like', '%' . $geo_city->nombre_region . '%')->first();
        return $city;
    }

    private function getDateStartAndEnd(): array
    {
        // Calculo las fechas en las que deben estar las cuotas
        //$start = Carbon::now()->startOfMonth();
        //$end = Carbon::now()->addDay(5)->endOfDay();
        //$end = Carbon::now()->endOfMonth();
        // $start = new Carbon('first day of last month');
         $start =  Carbon::createFromFormat('Y-m-d H:i:s',  '2021-05-01 00:00:00');
        // $end = new Carbon('last day of last month');
	$end = Carbon::now()->endOfMonth();

        return [$start, $end];
    }

    private function setDetailsItems($quota, $previousQuotas = []) : array
    {
        $this->monto_deuda = 0;
        //['product_code' => 'CR100001'], ['name' => 'Interes Corriente', 'factory_reference' => 'Servicios Financieros']);
        //['product_code' => 'CR200001'], ['name' => 'Abono a Capital', 'inventory_group' => 'Abonos a Capital']);
        //['product_code' => 'CR300001'], ['name' => 'Intereses de Mora', 'inventory_group' => 'Intereses de Mora']);
        //['product_code' => 'SG100001'], ['name' => 'Seguros', 'inventory_group' => 'Seguros']);
        //['product_code' => 'FZ100001'], ['name' => 'Fianzas', 'inventory_group' => 'Fianzas']);
        //['product_code' => 'FZ100002'], ['name' => 'Recobro Impuesto Fianza']);
        //['product_code' => 'CA100001'], ['name' => 'Cargos Adicionales', 'inventory_group' => 'Cargos Adicionales']);
        //['product_code' => 'CR200002'], ['name' => 'Abono');

        foreach ($previousQuotas as $key => $previousQuota)
        {
            $this->interest_of_late_payment = $previousQuota->monto_mora;
            $response[]= ['code' => 'CR200001', 'description' => 'Capital cuota N° '. $previousQuota->cuota_nro, 'price' => $previousQuota->amortizacion ];
            $response[]= ['code' => 'CR100001', 'description' => 'Interes cuota N° '. $previousQuota->cuota_nro,'price'=>$previousQuota->interes_cuota ]; //Intereses Corrientes
            $response[]= ['code' => 'FZ100001', 'description' => 'Fianza cuota N° '. $previousQuota->cuota_nro, 'price' => $previousQuota->fianza];
            $response[]= ['code' => 'FZ100002', 'description' => 'IVA fianza cuota N° '. $previousQuota->cuota_nro,'price'=>$previousQuota->iva_fianza]; // Recobro Impuesto Fianza

        }
        if($quota->pagado > 0 && $quota->abono == 0){
            //$this->monto_deuda = $quota->monto_cuota - $quota->pagado;
            $this->monto_deuda = ($quota->amortizacion + $quota->interes_cuota + $quota->fianza + $quota->iva_fianza) - $quota->pagado;
		 $response[]= ['code' => 'CR200001', 'description' => 'Capital cuota N° '. $quota->cuota_nro,'price'=> $quota->amortizacion - $this->monto_deuda]; //Abono a Capital
        }else if($quota->abono > 0){
            $response[]= ['code' => 'CR200002', 'description' => 'Abono', 'price'=>$quota->abono]; //Abono a Capital
            $response[]= ['code' => 'CR200001', 'description' => 'Capital cuota N° '. $quota->cuota_nro,'price'=>($quota->pagado - $quota->interes_cuota - $quota->fianza - $quota->iva_fianza - $quota->abono) ]; //Abono a Capital

        }else{
            $response[]= ['code' => 'CR200001', 'description' => 'Capital cuota N° '. $quota->cuota_nro,'price'=>$quota->amortizacion]; //Abono a Capital
        }

        $response[]= ['code' => 'CR100001', 'description' => 'Interes cuota N° '. $quota->cuota_nro,'price'=>$quota->interes_cuota]; //Intereses Corrientes
        $response[]= ['code' => 'FZ100001', 'description' => 'Fianza cuota N° '. $quota->cuota_nro, 'price'=>$quota->fianza ]; //Fianzas
        $response[]= ['code' => 'FZ100002', 'description' => 'IVA fianza cuota N° '. $quota->cuota_nro,'price'=>$quota->iva_fianza]; // Recobro Impuesto Fianza

        return $response;
    }

	 private function setDetailsItemsAjuste($quota, $previousQuotas = []) : array
    {
        $this->monto_deuda = 0;
        //['product_code' => 'CR100001'], ['name' => 'Interes Corriente', 'factory_reference' => 'Servicios Financieros']);
        //['product_code' => 'CR200001'], ['name' => 'Abono a Capital', 'inventory_group' => 'Abonos a Capital']);
        //['product_code' => 'CR300001'], ['name' => 'Intereses de Mora', 'inventory_group' => 'Intereses de Mora']);
        //['product_code' => 'SG100001'], ['name' => 'Seguros', 'inventory_group' => 'Seguros']);
        //['product_code' => 'FZ100001'], ['name' => 'Fianzas', 'inventory_group' => 'Fianzas']);
        //['product_code' => 'FZ100002'], ['name' => 'Recobro Impuesto Fianza']);
        //['product_code' => 'CA100001'], ['name' => 'Cargos Adicionales', 'inventory_group' => 'Cargos Adicionales']);
        //['product_code' => 'CR200002'], ['name' => 'Abono');

        //if(isset($previousQuotas) && $previousQuotas->quota_record_last->monto_mora > 0)

        if($quota->pagado > 0 && $quota->abono == 0){
            //$this->monto_deuda = $quota->monto_cuota - $quota->pagado;
            $this->monto_deuda = ($quota->amortizacion + $quota->interes_cuota + $quota->fianza + $quota->iva_fianza) - $quota->pagado;
            ($quota->amortizacion - $this->monto_deuda) > 0 ? $response[]= ['code' => 'CR200001', 'description' => 'Capital (Ajuste facturación)','price'=> $quota->amortizacion - $this->monto_deuda] : '';
        }else if($quota->abono > 0){
            ($quota->abono) > 0 ?  $response[]= ['code' => 'CR200002', 'description' => 'Abono', 'price'=>$quota->abono] : ''; //Abono a Capital
            $cap = $quota->pagado - $quota->interes_cuota - $quota->fianza - $quota->iva_fianza - $quota->abono;
            ($cap) > 0 ? $response[]= ['code' => 'CR200001', 'description' => 'Capital (Ajuste facturación)','price'=>($cap) ] : ''; //Abon>

        }else{
            ($quota->amortizacion > 0) ? $response[]= ['code' => 'CR200001', 'description' => 'Capital (Ajuste facturación)','price'=>$quota->amortizacion] : ''; //Abono a Capital
        }

        ($quota->interes_cuota > 0) ? $response[]= ['code' => 'CR100001', 'description' => 'Interes (Ajuste facturación)','price'=>$quota->interes_cuota] : ''; //Intereses Corrientes
        ($quota->fianza > 0) ? $response[]= ['code' => 'FZ100001', 'description' => 'Fianza (Ajuste facturación)', 'price'=>$quota->fianza ] : ''; //Fianzas
        ($quota->iva_fianza > 0) ? $response[]= ['code' => 'FZ100002', 'description' => 'IVA fianza (Ajuste facturación)','price'=>$quota->iva_fianza] : ''; // Recobro Impuesto Fianza

        return $response;
    }
}
