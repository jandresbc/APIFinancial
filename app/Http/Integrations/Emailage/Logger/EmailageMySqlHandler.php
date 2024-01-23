<?php

namespace App\Http\Integrations\Emailage\Logger;

use Carbon\Carbon;
use Monolog\Logger;

use Monolog\Handler\AbstractProcessingHandler;

use App\Models\Creditek\Emailage\CreditekLogEmailage;
use App\Models\Creditek\Emailage\CreditekDataEmailage;

class EmailageMySqlHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $log = CreditekLogEmailage::create([
            'email' => $record['context']['email'],
            'message' => $record['message'],
            'type_log' => $record['context']['logType'],
        ]);
        $log->save();

        $data = CreditekDataEmailage::create([
            'creditek_log_emailage_id' => $log->id,
            'consultation_date' => Carbon::now(),
            'due_date' => $record['context']['logType'] === 'info' ? Carbon::now()->addDay(5)->toDateTimeString() : null,
            'data' => json_encode($record['context']['data']),
            'reason' => $record['context']['reason'] ? $record['context']['reason'] : $record['context']['data']->results[0]->EAReason,
            'riskBand' => $record['context']['logType'] === 'info' ? intval($record['context']['data']->results[0]->EARiskBandID) : null,
            'riskScore' => $record['context']['logType'] === 'info' ? intval($record['context']['data']->results[0]->EAScore) : null,
            'status' => $record['context']['status'],
        ]);
        $data->save();
    }
}
