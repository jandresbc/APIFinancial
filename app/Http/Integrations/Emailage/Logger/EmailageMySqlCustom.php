<?php

namespace App\Http\Integrations\Emailage\Logger;

use Monolog\Logger;

class EmailageMySqlCustom
{
    /**
     * Create a custom Monolog instance.
     *
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger =  new Logger("EmailageMySqlHandler");
        $handler = new EmailageMySqlHandler();
        $logger->pushHandler($handler);

        return $logger;
    }
}
