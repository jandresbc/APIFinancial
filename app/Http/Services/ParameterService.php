<?php
namespace  App\Http\Services;
use App\Models\CrCore\Parameters;
use Illuminate\Support\Facades\DB;

class ParameterService{

    public const message = '';

    public function get($grupo_id, $nombre, $typePerson = null)
    {
        try {
            if($typePerson !== null){
                $client = Parameters::where('grupo_id', $grupo_id)->where('type_person', $typePerson)->where('nombre', $nombre)->first();
                if (isset($client)) {
                    $client['valor'] = (string)$client['valor'];
                    return $client['valor'];
                }
            }
            $client = Parameters::where('grupo_id', $grupo_id)->where('nombre', $nombre)->first();
            return $client['valor'];
        } catch (\Exception $e) {
            var_dump($e->getMessage(), $e->getFile(), $e->getLine());
            return 'error';
        }
    }
}
