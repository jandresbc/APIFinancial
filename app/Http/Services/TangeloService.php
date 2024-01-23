<?php

namespace App\Http\Services;

use Exception;
use App\Http\Integrations\Tangelo;

class TangeloService extends Service
{

    /**
     * @param $data
     * @return array
     * @throws \JsonException
     */
    public function consumer ($data): array
    {
        $tangelo = new Tangelo();

        return $tangelo->consumer($data);
    }

    /**
     * @param $process
     * @return array
     */
    public function getStatus ($process): array
    {
        $tangelo = new Tangelo();

        return $tangelo->getStatus($process);
    }

}
