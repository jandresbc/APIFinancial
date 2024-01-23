<?php

namespace App\Http\Integrations\Siigo\v2;

use App\Http\Integrations\Siigo\v2\Adapters\InvoiceAdapter;
use App\Http\Integrations\Siigo\v2\TokenService;
use App\Http\Integrations\Siigo\v2\SellerService;
use App\Http\Integrations\Siigo\v2\PaymentService;
use App\Http\Integrations\Siigo\v2\DocumentTypeService;
use App\Models\Siigo\Invoice;
use App\Models\Siigo\SiigoLog;
use Carbon\Carbon;
use Exception;


class InvoiceService
{
    private $invoiceAdapter;
    private $sellerService;
    private $tokenService;
    private $documentService;

    public function __construct(InvoiceAdapter $invoiceAdapter,
                                SellerService $sellerService,
                                TokenService $tokenService,
                                PaymentService $paymentService,
                                DocumentTypeService $documentService)
    {
        $this->invoiceAdapter = $invoiceAdapter;
        $this->tokenService = $tokenService;
        $this->sellerService = $sellerService;
        $this->paymentService = $paymentService;
        $this->documentService = $documentService;

    }

    public function download($hash_id)
    {
        try {
            $token = $this->tokenService->get();
            $fileInvoices = $this->invoiceAdapter->download($token['token'], $hash_id);
            if(!$fileInvoices->ok())
            {
                throw new Exception();
            }

            return $fileInvoices;

        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }

    public function listFromAdapter($page = 1)
    {
        $token = $this->tokenService->get();
        $invoices = $this->invoiceAdapter->list($token['token'], $page);
        return $invoices->json();
    }

    public function searchByParams($params)
    {
        $token = $this->tokenService->get();
        $invoices = $this->invoiceAdapter->searchByParam($token['token'], $params);
        return $invoices->json();
    }

    public function getById($id)
    {
        $token = $this->tokenService->get();
        $invoices = $this->invoiceAdapter->getById($token['token'], $id);
        return $invoices->json();
    }

    public function create($customer, $loan, $items, $payments = [], $options = [])
    {
        $logRecord = SiigoLog::addNewRecord($customer, $loan, 'Factura');
        \Log::channel('Siigo')->info('Inicio creacion de factura.', ['siigo_log_id' => $logRecord->id, 'data' => [ 'customer' => $customer, 'loan' => $loan, 'items' => $items, 'payments' => $payments, 'options' => $options]]);
        $this->totalValue = 0;
        // Obtengo el tipo de documento de la base de datos para facturas.
        $documentType = $this->getDocumentTypeById(config('Siigo.siigo_default_invoice_document'));
        // document.id | number | Identificador del tipo de comprobante. /document-types
        $payload['document']['id'] = $documentType->id; // Get document from Service
        // number | number | (Opcional) Consecutivo/número del comprobante, el campo NO es obligatorio, depende de la configuración del comprobante.
        $invoiceNumber = $this->getInvoiceNumber($options, $documentType);
        $invoiceNumber ? $payload['number'] = $invoiceNumber : null;
        // date | string | Fecha de la factura, formato yyyy-MM-dd. Ejemplo 2021-03-19. La fecha de creacion no debe superar los 10 dias para que la DIAN no la rechace.
        $payload['date'] = isset($options['date']) ? $options['date'] : Carbon::now()->format('Y-m-d');
        // string | Número de identificación del cliente.
        $payload['customer']['identification'] = isset($customer['identification']) ? $customer['identification'] : null;
        // number | (Opcional) Sucursal, valor por default 0.
        $payload['customer']['branch_office'] = isset($options['branch_office']) ? $options['branch_office'] : 0;
        // number	Identificador del vendedor asociado a la factura. /users
        $payload['seller'] = isset($options['seller']) ? $options['seller'] : $this->getIdSeller();
        // string | Observaciones asociadas a la factura.
        $payload['observations'] = isset($options['observations']) ? $options['observations'] : config('Siigo.siigo_default_invoice_observation');// Traer de la base
        // array | (Opcional) Array con los id de los impuestos tipo ReteICA, ReteIVA o Autoretención /taxes
        isset($options['retentions']) ? $payload['retentions'] = $options['retentions'] : null;
        // number | (Opcional) Valor de Anticipo o Copago.
        isset($options['advance_payment']) ? $payload['advance_payment'] = $options['advance_payment'] : null;
        // 	number	(Opcional) Identificador del Centro de costos.
        isset($options['cost_center']) ? $payload['cost_center'] = $options['cost_center'] : null;
        // array Productos o Servicios asociados a la factura.
        $payload['items'] = $this->getItems($items);
        // array Productos o Servicios asociados a la factura.
        $payload['payments'] = $this->getPayments($payments);
        // Valido la informacion que voy a mandar al servicio Siigo Api.
        $errors = $this->validateInvoice($payload);
        if($errors)
        {
            $response = ['Status' => 400,'siigo_log_id' => $logRecord->id, 'Message' => 'Error en la validación de datos', 'Errors' => $errors];
            \Log::channel('Siigo')->error('Los datos contienen errores.', $response);
            return $response;
        }

        // Obtengo el token para realizar la factura.
        $token = $this->tokenService->get();
        \Log::channel('Siigo')->info('Enviar a crear.', ['siigo_log_id' => $logRecord->id, 'data' => ['request' => $payload]]);
        // Llamo al servicio.
        $invoice = $this->invoiceAdapter->create($payload, $token['token']);
        if(!$invoice->successful())
        {
            \Log::channel('Siigo')->error('Siigo API devolvió un error.', ['siigo_log_id' => $logRecord->id, 'data' => [$invoice->json()]] );
            $responseSiigo = $invoice->json();
            $responseSiigo['Message'] = 'Error en Siigo Api';
            $responseSiigo['Errors'] = $this->formatErrorsSiigo($responseSiigo['Errors']);

            return $responseSiigo;
        }
        $siigoResponse = $invoice->json();
        \Log::channel('Siigo')->info('Resultado de la creacion de la factura.', ['siigo_log_id' => $logRecord->id, 'data' => ['response' => $siigoResponse]]);

        $this->saveInvoice($customer, $loan, $payload, $siigoResponse);
        return $invoice->json();
    }

    private function getIdSeller()
    {
        $seller = $this->sellerService->searchByUserName();
        if(!isset($seller))
        {
            throw new Exception('No hay un vendedor configurado en la base de datos o en Siigo Nube');
        }
        return $seller->id;
    }

    private function getIdPaymentType($paymentType)
    {
        $response = [];
        $paymentTypes = $this->paymentService->searchByName($paymentType);
        if(!isset($paymentTypes))
        {
            throw new Exception('No hay un medio de pago configurado por defecto.');
        }

        foreach ($paymentTypes as $key => $pay) {
            $response[$key] = $pay->id;

        }

        return $response;
    }

    private function getDocumentTypeById($documentId)
    {
        $documentType = $this->documentService->getById($documentId);
        if(!isset($documentType))
        {
            throw new Exception('No hay un tipo de documento configurado por defecto.');
        }
        return $documentType;
    }

    private function getInvoiceNumber($options, $documentType)
    {
        $invoiceNumber = false;
        if(isset($options['number']))
        {
            $invoiceNumber = $options['number'];
        }
        else{
            if(!$documentType->automatic_number)
            {
                $invoiceNumber = Invoice::max('number');
            }
        }
        return $invoiceNumber;
    }

    private function getItems($items)
    {
        $response = [];
        foreach ($items as $key => $item)
        {
            // string Código único del producto. Ejemplo Item-1
            $response[$key]['code'] = $item['code'];
            // string Nombre o descripción del producto/servicio.. Ejemplo Camiseta de algodón
            isset($item['description']) ? $response[$key]['description'] = $item['description'] : null;
            // number Cantidad
            $response[$key]['quantity'] = isset($item['quantity']) ? $item['quantity'] : 1;
            // number Precio del producto / Valor unitario. Ejemplo 1069.77
            $response[$key]['price'] = ceil(number_format($item['price'], 2, ".", ""));
            $this->totalValue += $item['price'];
        }
        return $response;
    }

    private function getPayments($payments)
    {
        $response = [];
        $paymentsIds = $this->getIdPaymentType($payments);

        foreach ($paymentsIds as $key => $paymentId)
        {
            // number ID del medio de pago. Ejemplo 5636
            $response[$key]['id'] = $paymentId;
            // number Valor asociado al medio de pago. Ejemplo 1273.03
            $response[$key]['value'] = $this->totalValue;
            // string Fecha pago cuota, formato yyyy-MM-dd. Ejemplo 2021-03-19
            $response[$key]['due_date'] = isset($payment['due_date']) ? $payment['due_date'] : Carbon::now()->format('Y-m-d');
        }

        return $response;
    }

    private function saveInvoice($customer, $loan, $payload, $invoice)
    {
        $entity['loan_id'] = $loan['id'];
        $entity['quota'] = $loan['quota'];
        $entity['hash_id'] = $invoice['id'];
        $entity['due_date'] = $payload['payments'][0]['due_date'];
        $entity['due_prefix'] = 'FV-1';
        $entity['number'] = $invoice['number'];
        $entity['account_id'] = $invoice['customer']['id'];
        $entity['identification'] = $customer['identification'];
        $entity['first_name'] = $customer['first_name'];
        $entity['last_name'] = $customer['last_name'];
        $entity['total_value'] = $payload['payments'][0]['value'];
        $entity['created_at'] = Carbon::now();
        return Invoice::create($entity);
    }

    private function validateInvoice($payload)
    {
        $errors = [];
        if(isset($payload['date']))
        {
            $dateCreation = Carbon::createFromFormat('Y-m-d', $payload['date']);
            if(isset($dateCreation))
            {
                if($dateCreation->diffInDays(Carbon::now()) >= config('Siigo.siigo_default_days_limit_diam'))
                {
                    $errors['date']['Message'] = "La diferencia entre la fecha de creación y la actual es igual o superior a ".config('Siigo.siigo_default_days_limit_diam')." días.";
                    $errors['date']['Creation'] = $dateCreation->format('Y-m-d H:i:s');
                    $errors['date']['Current'] = Carbon::now()->format('Y-m-d H:i:s');
                }
            }
        }else{
            $errors['date']['Message'] = "La fecha de creación no es válida.";
            $errors['date']['Value'] = $payload['date'];
        }

        if(!isset($payload['customer']['identification']) || empty($payload['customer']['identification']) || trim($payload['customer']['identification']) == "")
        {
            $errors['identification']['Message'] = "La identificación del cliente no es válida.";
        }
        if(!empty($payload['items']))
        {
            foreach ($payload['items'] as $key => $item)
            {
                if($item['price'] <= 0)
                {
                    $errors['items'][$key]['Message'] = "El valor del item es menor o igual a cero (".$item['code'].")";
                    $errors['items'][$key]['Item'] = $item['code'];
                    $errors['items'][$key]['Value'] = $item['price'];
                }
            }
            if(!empty($errors['items']))
            {
                $errors['items']['Message'] = "Existen items con valor menor o igual a cero (".$item['code'].")";
            }
        }else{
            $errors['items']['Message'] = "La lista de items esta vacía.";
        }

        if(empty($payload['payments']))
        {
            $errors['payments']['Message'] = "No hay medios de pago definidos.";
        }
        return $errors;
    }

    private function formatErrorsSiigo($errors)
    {
        $response = [];
        foreach ($errors as $index => $error)
        {
            $response[$error['Code']] = $error['Message'];
        }
        return $response;
    }

}
