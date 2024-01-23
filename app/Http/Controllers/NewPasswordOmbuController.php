<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\CrCore\MiniBackUsuarios;

class NewPasswordOmbuController extends Controller
{
    /**
     * @throws \JsonException
     */
    public function setNewPassword(): string
    {
        $tienda_id = 20;
        $usuarios = MiniBackUsuarios::where("tienda_id",$tienda_id)->where("estado","!=","ELI")->where("estado","!=","DES")
        ->where("cargo","!=","ADMIN")->where("tipo_usuario","!=","TIE")
        //->where("username","admin")
        ->with("empleado")->get();

        if(count($usuarios) > 0){
            foreach($usuarios as $key=>$user){
                //dd($usuarios[0]->sys_fecha_alta);
                $pepper = "En algún lugar de La Mancha, del cual no me quiero acordar...";
                //$cleartext = "Creditek2021!";
                $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
                // Output: 54esmdr0qf
                $cleartext = substr(str_shuffle($permitted_chars), 2, 6);
                
                //dd($pass);
                $result = md5($cleartext);
                //$fecha_alta = "2021-03-12 11:01:30";
                $fecha_alta = $user->sys_fecha_alta;
                if (!empty($fecha_alta)) {
                    $salt = md5($fecha_alta);
                    $jam = array(substr($salt, 0, 16), substr($salt, 16, 16));
                    $result = md5($jam[0] . md5($cleartext) . $jam[1]);
                } else {
                    print('Fecha de alta está vacía.');
                }
                if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
                    $password_peppered = hash_hmac("sha512", $result, $pepper);
                    $result = password_hash($password_peppered, PASSWORD_BCRYPT);
                }

                print("__________________________________\n");
                print("id: ".($key+1)."\n");
                print "Cleartext: ".$cleartext."\n";
                print "NewPassword: ".$result;
                print("\n__________________________________\n");

                $user->cleartext = $cleartext;
                $user->password = $result;
                $user->save();
            }
        }else{
            $result = "No hay datos que procesar";
            print ($result);
        }
        
		return $result;
    }
}
