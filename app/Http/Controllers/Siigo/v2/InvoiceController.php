<?php

namespace App\Http\Controllers\Siigo\v2;

use App\Exceptions\InvoiceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Siigo\Invoice;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceCollection;
use App\Http\Integrations\Siigo\v2\InvoiceService;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Storage;


class InvoiceController extends Controller
{
    public function __construct(InvoiceService $invoiceService, Invoice $invoiceModel)
    {
        $this->invoiceService = $invoiceService;
        $this->invoiceModel = $invoiceModel; 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $invoices = new InvoiceCollection(Invoice::limit(10)->orderBy('created_at', 'DESC')->get());
            return $this->respond(['items' => $invoices]);
        } catch (\Throwable $th) {
            dd($th->getMessage());
            return $this->respondWithError("Error", 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getType(Request $request)
    {
        try {
            $invoice = $this->invoiceService->getType($request->DocCode, $request->DocClass);
            return $this->respond($invoice);
        } catch (\Throwable $th) {
            dd($th->getMessage());
            return $this->respondWithError("Error", 500);
        }
    }
    /**
     * Create a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $payments = isset($request->payments) ? $request->payments : [];
        $options = isset($request->options) ? $request->options : [];
        try {
            $invoice = $this->invoiceService->create($request->customer, $request->loan, $request->items, $payments, $options);
            //dd($invoice['Status'],$invoice['Status'],$invoice['Errors'][0]['Message']);
            if(isset($invoice['Errors']))
            {
                return $this->respondWithError($invoice['Status'],$invoice['Status'],$invoice['Message'],$invoice['Errors']);
            }
            return $this->respond($invoice);
        } catch (\Throwable $th) {
            die($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);
            if(isset($invoice))
            {
                return $this->respond(new InvoiceResource($invoice));
            }else{
                throw new  InvoiceNotFoundException();
            }
        } catch (InvoiceNotFoundException $th) {
            return $this->respondWithError(100, 404,sprintf(\Lang::get('api.invoice.invoice_not_found_message'), $invoiceId));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    /**
     * Download Invoice.
     *
     * @return File
    */ 
    public function download($invoiceId, $base64 = '')
    {
        try {
            // Recupero los datos de la factura.
            $invoice = Invoice::find($invoiceId);
            // Lanzo una excepcion si no encuentra datos.
            if(!$invoice)
            {
                throw new Exception();
            }

            if(!isset($invoice->hash_id))
            {
                // Deberia ir a buscar los datos a Siigo y actualizarlos.
                throw new Exception();
            }
            return $this->downloadFromAdapter($invoice, $base64);
           
        } catch (\Throwable $th) {
            dd($th);
        }

    }

    public function downloadByNumber($numberInvoice, $base64 = '')
    {  
        try {
            // Recupero los datos de la factura.
            $invoice = Invoice::where(['number' => $numberInvoice])->first();

            if(!$invoice)
            {
                throw new Exception();
            }

            if(!isset($invoice->hash_id))
            {
                // Deberia ir a buscar los datos a Siigo y actualizarlos.
                throw new Exception();
            }

            return $this->downloadFromAdapter($invoice, $base64);

        } catch (\Throwable $th) {
            dd($th);
        }

    }

    private function downloadFromAdapter(Invoice $invoice, $base64 = '')
    {
        $fileInvoice = $this->invoiceService->download($invoice->hash_id);
        if(strtolower($base64) == 'base64')
        {
            return $fileInvoice->json();
        }
        // Armo el nombre del archivo.
        $fileName = $invoice->last_name.'_'.$invoice->identification.'_q_'.$invoice->quota.'_v_'.$invoice->due_date->format('Ymd').'_FC_'.$invoice->number.'.pdf';
        $path = public_path($fileName);
        $contents = base64_decode($fileInvoice['base64']);
        //Guardo el archivo temporal.
        file_put_contents($path, $contents);
        //Respondo con el archivo y lo elimino.
        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function listFromAdapter()
    {
        dd($this->invoiceService->listFromAdapter());
        return $this->invoiceService->listFromAdapter();
    }
}
