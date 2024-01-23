<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Integrations\Siigo\v2\PaymentService;
use Carbon\Carbon;

class UpdatePaymentTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment_types:run-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to upsert payment types.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Inicio del proceso actualizacion de medios de pago.');
        $response = [];
        // $table->id();
        // $table->string("name",50)->nullable(false);
        // $table->string("type",50)->nullable(true);
        // $table->boolean("active")->nullable(false);
        // $table->datetime("due_date")->nullable(true);

        $headers = ['Payment type Id', 'Name','Type', 'Active', 'Due date'];
        
        foreach($this->paymentService->upsert() as $pay)
        {
            array_push($response, [$pay['id'], $pay['name'], $pay['type'], $pay['active'], $pay['due_date']]);
        }

        $this->table($headers,$response);

        $this->info('['.Carbon::now()->format('Y-m-d H:i:s').']'.' Fin del proceso');
    }
}
