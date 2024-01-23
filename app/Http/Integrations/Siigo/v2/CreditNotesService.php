<?php

namespace App\Http\Integrations\Siigo\v2;

use App\ServicesV2\TokenService;
use App\ServicesV2\AdaptersV2\CreditNotesAdapter;
use App\Models\DocumentType;
use App\Models\CreditNote;
use Carbon\Carbon;

class CreditNotesService
{
    private $creditNotesAdapter;
    private $tokenService;

    public function __construct(CreditNotesAdapter $creditNotesAdapter, TokenService $tokenService)
    {
        $this->creditNotesAdapter = $creditNotesAdapter;
        $this->tokenService = $tokenService;
    }

    public function create($payload)
    {
        dump($payload);
        $token = $this->tokenService->get();
        $document = DocumentType::where('Name','Nota Crédito')->first();
        if(!isset($document))
        {
            throw new Exception('Tipo de documento Nota Crédito no encontrado');
        }

        $data['document']['id'] = $document->Id;

        if(isset($payload['number']))
        {
            $data['number'] =$payload['number'];
        } else {
            $lastCreditNotes = CreditNote::max('number');
            $data['number'] = $lastCreditNotes->number;
        }
        $data['date'] = isset($payload['date']) ? $payload['date'] : Carbon::now();
        isset($payload['cost_center']) ?  $data['date'] = $payload['cost_center']: null;
        isset($payload['retentions']) ?  $data['retentions'] = $payload['retentions']: null;
        isset($payload['observations']) ?  $data['observations'] = $payload['observations']: null;
        $data['invoice'] = $payload['invoice'];

        $data['payments'][0]['id'] = $payload['payments']['id'] ;
        $data['payments'][0]['value'] = $payload['payments']['value'] ;
        $data['payments'][0]['due_date'] = $payload['payments']['due_date'] ;
        $data['items'] = $payload['items'];




        // {
        //     *"document": {
        //      * "id": 77775
        //     },
        //     *"number": 22,
        //     *"date": "2015-12-15",
        //     "invoice": "302580df-838b-4531-b8bf-dd3c98b34059",
        //     *"cost_center": 235,
        //     *"reason": "1",
        //     *"retentions": [
        //       {
        //         *"id": 13156
        //       }
        //     ],
        //     *"observations": "Observaciones",
        //     "items": [
        //       {
        //         "code": "Item-1",
        //         "description": "Camiseta de algodón",
        //         "quantity": 1,
        //         "price": 1069.77,
        //         "discount": 0,
        //         "taxes": [
        //           {
        //             "id": 13156
        //           }
        //         ]
        //       }
        //     ],
        //     "payments": [
        //       {
        //         "id": 5636,
        //         "value": 1273.03,
        //         "due_date": "2021-03-19"
        //       }
        //     ]
        //   }

       dd($this->creditNotesAdapter->create($data, $token['token']));
    }

}
