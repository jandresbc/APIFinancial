<?php
namespace App\Http\Integrations\Siigo\v2\Logging;
use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use App\Models\Siigo\SiigoDataLog;

class MySQLLoggingHandler extends AbstractProcessingHandler{
/**
 *
 * Reference:
 * https://github.com/markhilton/monolog-mysql/blob/master/src/Logger/Monolog/Handler/MysqlHandler.php
 */
    public function __construct($level = Logger::DEBUG, $bubble = true) {
        $this->table = 'siigo_data_logs';
        parent::__construct($level, $bubble);
    }
    protected function write(array $record):void
    {
        $log['siigo_log_id'] = $record['context']['siigo_log_id'];
        $log['level'] = $record['level'];
        $log['level_name'] = $record['level_name'];
        $log['data'] = json_encode($record['context']);
        $log['error'] = in_array($record['level_name'], ["ERROR"]) ? true : false;
        $log['message'] = $record['message'];
        $log['created_at'] = Carbon::now();
        SiigoDataLog::create($log);
    }
}