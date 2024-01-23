<?php

namespace App\Console\Commands;

use App\Http\Controllers\ContactFlowsController;
use Illuminate\Console\Command;

class contact_flows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:contact_flows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'call the function contact flows in controller';

    /**
     * Create a new command instance.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $contact = new ContactFlowsController();
        $response = $contact->dateMessage();
        $this->info($response);
    }
}
