<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class EnBancaExport implements FromCollection
{
    protected array $data;

    /**
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        return collect($this->data);
    }

    /**
     * Write code on Method
     * @return array
     */
    public function headings() :array
    {
        return [
            'documento',
            'teléfono',
            'match',
            'score',
            'json',
        ];
    }
}
