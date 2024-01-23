<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use  App\Http\Integrations\Siigo\v2\DocumentTypeService;
use Carbon\Carbon;

class UpdateDocumentTypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documentType:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DocumentTypeService $documentTypeService)
    {
        parent::__construct();
        $this->documentTypeService = $documentTypeService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Inicio del proceso actualizacion de Documentos.');
        
        $response = [];
        $headers = ['Name','DocCode','DocClass','Consecutive', 'Is Active'];
        $documents = ['FV','RC','NC','FC','CC'];
        foreach ($documents as $key => $document) {
            foreach($this->documentTypeService->upsert($document) as $doc)
            {
                array_push($response, [$doc['name'], $doc['id'], $doc['type'], $doc['consecutive'], $doc['active']]);
            }
        }
        $this->table($headers,$response);
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Fin del proceso');
    }
}
