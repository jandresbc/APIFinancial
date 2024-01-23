<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departamento;

class DepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Departamento::firstOrCreate(['id' => 2],['pais_id' => 52, 'nombre' => 'Antioquia', 'orden' => 1]);
        Departamento::firstOrCreate(['id' => 3],['pais_id' => 52, 'nombre' => 'Atlantico', 'orden' => 2]);
        Departamento::firstOrCreate(['id' => 4],['pais_id' => 52, 'nombre' => 'Bogota', 'orden' => 3]);
        Departamento::firstOrCreate(['id' => 5],['pais_id' => 52, 'nombre' => 'Bolivar', 'orden' => 4]);
        Departamento::firstOrCreate(['id' => 6],['pais_id' => 52, 'nombre' => 'Boyaca', 'orden' => 5]);
        Departamento::firstOrCreate(['id' => 7],['pais_id' => 52, 'nombre' => 'Caldas', 'orden' => 6]);
        Departamento::firstOrCreate(['id' => 8],['pais_id' => 52, 'nombre' => 'Caqueta', 'orden' => 7]);
        Departamento::firstOrCreate(['id' => 9],['pais_id' => 52, 'nombre' => 'Cauca', 'orden' => 8]);
        Departamento::firstOrCreate(['id' => 10],['pais_id' => 52, 'nombre' => 'Cesar', 'orden' => 9]);
        Departamento::firstOrCreate(['id' => 11],['pais_id' => 52, 'nombre' => 'Cordoba', 'orden' => 10]);
        Departamento::firstOrCreate(['id' => 12],['pais_id' => 52, 'nombre' => 'Cundinamarca', 'orden' => 11]);
        Departamento::firstOrCreate(['id' => 13],['pais_id' => 52, 'nombre' => 'Choco', 'orden' => 12]);
        Departamento::firstOrCreate(['id' => 14],['pais_id' => 52, 'nombre' => 'Huila', 'orden' => 13]);
        Departamento::firstOrCreate(['id' => 15],['pais_id' => 52, 'nombre' => 'La guajira', 'orden' => 14]);
        Departamento::firstOrCreate(['id' => 16],['pais_id' => 52, 'nombre' => 'Magdalena', 'orden' => 15]);
        Departamento::firstOrCreate(['id' => 17],['pais_id' => 52, 'nombre' => 'Meta', 'orden' => 16]);
        Departamento::firstOrCreate(['id' => 18],['pais_id' => 52, 'nombre' => 'Nariño', 'orden' => 17]);
        Departamento::firstOrCreate(['id' => 19],['pais_id' => 52, 'nombre' => 'N. De santander', 'orden' => 18]);
        Departamento::firstOrCreate(['id' => 20],['pais_id' => 52, 'nombre' => 'Quindio', 'orden' => 19]);
        Departamento::firstOrCreate(['id' => 21],['pais_id' => 52, 'nombre' => 'Risaralda', 'orden' => 20]);
        Departamento::firstOrCreate(['id' => 22],['pais_id' => 52, 'nombre' => 'Santander', 'orden' => 21]);
        Departamento::firstOrCreate(['id' => 23],['pais_id' => 52, 'nombre' => 'Sucre', 'orden' => 22]);
        Departamento::firstOrCreate(['id' => 24],['pais_id' => 52, 'nombre' => 'Tolima', 'orden' => 23]);
        Departamento::firstOrCreate(['id' => 25],['pais_id' => 52, 'nombre' => 'Valle del cauca', 'orden' => 24]);
        Departamento::firstOrCreate(['id' => 26],['pais_id' => 52, 'nombre' => 'Arauca', 'orden' => 25]);
        Departamento::firstOrCreate(['id' => 27],['pais_id' => 52, 'nombre' => 'Casanare', 'orden' => 26]);
        Departamento::firstOrCreate(['id' => 28],['pais_id' => 52, 'nombre' => 'Putumayo', 'orden' => 27]);
        Departamento::firstOrCreate(['id' => 29],['pais_id' => 52, 'nombre' => 'San andres', 'orden' => 28]);
        Departamento::firstOrCreate(['id' => 30],['pais_id' => 52, 'nombre' => 'Amazonas', 'orden' => 29]);
        Departamento::firstOrCreate(['id' => 31],['pais_id' => 52, 'nombre' => 'Guainia', 'orden' => 30]);
        Departamento::firstOrCreate(['id' => 32],['pais_id' => 52, 'nombre' => 'Guaviare', 'orden' => 31]);
        Departamento::firstOrCreate(['id' => 33],['pais_id' => 52, 'nombre' => 'Vaupes', 'orden' => 32]);
        Departamento::firstOrCreate(['id' => 34],['pais_id' => 52, 'nombre' => 'Vichada', 'orden' => 33]);
        Departamento::firstOrCreate(['id' => 35],['pais_id' => 52, 'nombre' => 'Zulia', 'orden' => 34]);
        Departamento::firstOrCreate(['id' => 36],['pais_id' => 52, 'nombre' => 'Caracas', 'orden' => 35]);
    
    }
}