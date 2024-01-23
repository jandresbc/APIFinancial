<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Integrations\Siigo\v2\SellerService;
use Carbon\Carbon;

class UpdateSellersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sellers:run-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to upsert sellers.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SellerService $sellerService)
    {
        parent::__construct();
        $this->sellerService = $sellerService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Inicio del proceso actualizacion de usuarios del sistema Siigo.');
        
        $response = [];
        $headers = ['Id', 'Username', 'First name', 'Last name', 'Identification', 'Email', 'Is Active'];

        foreach($this->sellerService->upsert() as $seller)
        {
            array_push($response, [$seller['id'], $seller['username'], $seller['first_name'], $seller['last_name'], $seller['identification'], $seller['email'], $seller['active'], $seller['identification']]);
        }
        $this->table($headers,$response);
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Fin del proceso');
    }
}
