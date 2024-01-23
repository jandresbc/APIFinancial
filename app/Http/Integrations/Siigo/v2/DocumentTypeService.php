<?php

namespace App\Http\Integrations\Siigo\v2;

use App\Http\Integrations\Siigo\v2\Adapters\DocumentTypeAdapter;
use App\Http\Integrations\Siigo\v2\TokenService;
use App\Models\Siigo\DocumentType;

class DocumentTypeService
{
    private $documentTypeAdapter;
    private $tokenService;

    public function __construct(DocumentTypeAdapter $documentTypeAdapter, TokenService $tokenService)
    {
        $this->documentTypeAdapter = $documentTypeAdapter;
        $this->tokenService = $tokenService;
    }

    // Obtengo los datos desde la base de datos.
    public function getAll()
    {
        return DocumentType::all();
    }

    public function getById($document)
    {
        return DocumentType::where(['id' => $document])->first();
    }

    // Obtengo los datos desde el servicio de Siigo.
    public function getAllFromAdapter($type = 'FV')
    {
        $token = $this->tokenService->get();

        $response = $this->documentTypeAdapter->getAllByType($token['token'], $type);
        return $response->json();
    }

    public function getAllByTypeFromAdapter($id)
    {
        $token = $this->tokenService->get();

        $response = $this->documentTypeAdapter->getAllByType($token['token'], $id);
        return $response->json();
    }

    public function getAvailablesFromAdapter()
    {
        $token = $this->tokenService->get();

        $response = $this->documentTypeAdapter->getAvailables($token['token']);
        return $response->json();
    }
    
    public function getByCodeFromAdapter($docClass, $docCode)
    {
        $token = $this->tokenService->get();

        $response = $this->documentTypeAdapter->getByCode($token['token'], $docClass, $docCode);
        return $response->json();
    }
    
    public function getByIDFromAdapter($id)
    {
        $token = $this->tokenService->get();

        $response = $this->documentTypeAdapter->getByID($token['token'], $id);
        return $response->json();
    }

    public function upsert($type = 'FV')
    {
        $response = $this->getAllFromAdapter($type);
        foreach($response as $doc)
        {
            $this->save($doc);
        }
        return DocumentType::all();
    }

    public function save($doc)
    {
        $documentTypeModel = new DocumentType();
        $documentTypeModel->updateOrCreate(['id' => $doc['id']] , $this->setDocumentEntity($doc));
    }
    private function setDocumentEntity($doc)
    {
        $document['id'] = $doc['id'];
        $document['code'] = $doc['code'];
        $document['name'] = $doc['name'];
        $document['description'] = isset($doc['description']) ? $doc['description'] : null;
        $document['type'] = $doc['type'];
        $document['active'] = $doc['active'];
        $document['seller_by_item'] = isset($doc['seller_by_item']) ? $doc['seller_by_item'] : null;
        $document['cost_center'] = $doc['cost_center'];
        $document['cost_center_mandatory'] = $doc['cost_center_mandatory'];
        $document['automatic_number'] = $doc['automatic_number'];
        $document['consecutive'] = $doc['consecutive'];
        $document['discount_type'] = isset($doc['discount_type']) ? $doc['discount_type'] : null;
        $document['decimals'] = isset($doc['decimals']) ? $doc['decimals'] : null;
        $document['advance_payment'] = isset($doc['advance_payment']) ? $doc['advance_payment'] : null;
        $document['reteiva'] = isset($doc['reteiva']) ? $doc['reteiva'] : null;
        $document['reteica'] = isset($doc['reteica']) ? $doc['reteica'] : null;
        $document['self_withholding'] = isset($doc['self_withholding']) ? $doc['self_withholding'] : null;
        $document['self_withholding_limit'] = isset($doc['self_withholding_limit']) ? $doc['self_withholding_limit'] : null;
        $document['electronic_type'] = isset($doc['electronic_type']) ? $doc['electronic_type'] : null;
        return $document;
    }
}