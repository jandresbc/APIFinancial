<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\LockUnlockController;

class UnlockLock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unlock:lock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatic unlock and set date lock automatic';

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
     * @return string
     * @throws \JsonException
     */
    public function handle(): string
    {
        return (new LockUnlockController())->lockUnlock();
    }
}
