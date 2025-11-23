<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CodigosActividadEconomicaSeeder extends Seeder
{
    /**
     * Seed códigos de actividad económica más comunes en Costa Rica.
     */
    public function run(): void
    {
        $codigos = [
            // Comercio
            ['codigo' => '620101', 'descripcion' => 'Venta al por menor en comercios no especializados, con surtido compuesto principalmente de alimentos, bebidas y tabaco', 'categoria_principal' => 'Comercio', 'activo' => true],
            ['codigo' => '620901', 'descripcion' => 'Venta al por menor en comercios no especializados, sin predominio de alimentos, bebidas y tabaco', 'categoria_principal' => 'Comercio', 'activo' => true],
            ['codigo' => '631001', 'descripcion' => 'Venta al por menor de alimentos en comercios especializados', 'categoria_principal' => 'Comercio', 'activo' => true],
            ['codigo' => '642001', 'descripcion' => 'Venta al por menor de prendas de vestir, calzado y artículos de cuero en comercios especializados', 'categoria_principal' => 'Comercio', 'activo' => true],
            ['codigo' => '701001', 'descripcion' => 'Venta al por menor de muebles y artículos de uso doméstico', 'categoria_principal' => 'Comercio', 'activo' => true],
            
            // Servicios profesionales
            ['codigo' => '841101', 'descripcion' => 'Servicios jurídicos', 'categoria_principal' => 'Servicios Profesionales', 'activo' => true],
            ['codigo' => '841201', 'descripcion' => 'Servicios de contabilidad, teneduría de libros, auditoría y asesoría fiscal', 'categoria_principal' => 'Servicios Profesionales', 'activo' => true],
            ['codigo' => '841901', 'descripcion' => 'Servicios de consultoría en administración de empresas y gestión', 'categoria_principal' => 'Servicios Profesionales', 'activo' => true],
            ['codigo' => '842001', 'descripcion' => 'Servicios de arquitectura e ingeniería', 'categoria_principal' => 'Servicios Profesionales', 'activo' => true],
            
            // Tecnología
            ['codigo' => '850101', 'descripcion' => 'Consultoría en equipo de informática', 'categoria_principal' => 'Tecnología', 'activo' => true],
            ['codigo' => '850201', 'descripcion' => 'Consultoría en programas de informática y suministro de programas de informática', 'categoria_principal' => 'Tecnología', 'activo' => true],
            ['codigo' => '850301', 'descripcion' => 'Procesamiento de datos', 'categoria_principal' => 'Tecnología', 'activo' => true],
            ['codigo' => '850401', 'descripcion' => 'Actividades relacionadas con bases de datos', 'categoria_principal' => 'Tecnología', 'activo' => true],
            
            // Restaurantes y hoteles
            ['codigo' => '721001', 'descripcion' => 'Hoteles, campamentos y otros tipos de hospedaje temporal', 'categoria_principal' => 'Turismo', 'activo' => true],
            ['codigo' => '722001', 'descripcion' => 'Restaurantes, bares y cantinas', 'categoria_principal' => 'Turismo', 'activo' => true],
            
            // Construcción
            ['codigo' => '531001', 'descripcion' => 'Preparación del terreno', 'categoria_principal' => 'Construcción', 'activo' => true],
            ['codigo' => '532001', 'descripcion' => 'Construcción de edificios completos y de partes de edificios; obras de ingeniería civil', 'categoria_principal' => 'Construcción', 'activo' => true],
            ['codigo' => '533001', 'descripcion' => 'Acondicionamiento de edificios', 'categoria_principal' => 'Construcción', 'activo' => true],
            ['codigo' => '534001', 'descripcion' => 'Terminación de edificios', 'categoria_principal' => 'Construcción', 'activo' => true],
            
            // Transporte
            ['codigo' => '712101', 'descripcion' => 'Transporte de pasajeros', 'categoria_principal' => 'Transporte', 'activo' => true],
            ['codigo' => '712201', 'descripcion' => 'Transporte de carga', 'categoria_principal' => 'Transporte', 'activo' => true],
            
            // Agricultura
            ['codigo' => '111101', 'descripcion' => 'Cultivo de cereales y otros cultivos n.c.p.', 'categoria_principal' => 'Agricultura', 'activo' => true],
            ['codigo' => '113001', 'descripcion' => 'Cultivo de frutas, nueces, plantas bebestibles y especias', 'categoria_principal' => 'Agricultura', 'activo' => true],
            ['codigo' => '121101', 'descripcion' => 'Cría de ganado: bovino, equino, ovino, caprino, porcino', 'categoria_principal' => 'Ganadería', 'activo' => true],
            
            // Manufactura
            ['codigo' => '311001', 'descripcion' => 'Elaboración de productos alimenticios', 'categoria_principal' => 'Manufactura', 'activo' => true],
            ['codigo' => '312001', 'descripcion' => 'Elaboración de bebidas', 'categoria_principal' => 'Manufactura', 'activo' => true],
            ['codigo' => '321001', 'descripcion' => 'Fabricación de productos textiles', 'categoria_principal' => 'Manufactura', 'activo' => true],
            ['codigo' => '322001', 'descripcion' => 'Fabricación de prendas de vestir', 'categoria_principal' => 'Manufactura', 'activo' => true],
            
            // Salud
            ['codigo' => '931001', 'descripcion' => 'Servicios de salud humana', 'categoria_principal' => 'Salud', 'activo' => true],
            ['codigo' => '931101', 'descripcion' => 'Servicios de médicos y odontólogos', 'categoria_principal' => 'Salud', 'activo' => true],
            
            // Educación
            ['codigo' => '921001', 'descripcion' => 'Enseñanza primaria', 'categoria_principal' => 'Educación', 'activo' => true],
            ['codigo' => '922001', 'descripcion' => 'Enseñanza secundaria', 'categoria_principal' => 'Educación', 'activo' => true],
            ['codigo' => '923001', 'descripcion' => 'Enseñanza superior', 'categoria_principal' => 'Educación', 'activo' => true],
            
            // Servicios personales
            ['codigo' => '990101', 'descripcion' => 'Servicios de lavandería, peluquería y pompas fúnebres', 'categoria_principal' => 'Servicios Personales', 'activo' => true],
            ['codigo' => '990201', 'descripcion' => 'Servicios de entretenimiento, esparcimiento, culturales y deportivos', 'categoria_principal' => 'Servicios Personales', 'activo' => true],
        ];

        foreach ($codigos as $codigo) {
            DB::table('codigos_actividad_economica')->updateOrInsert(
                ['codigo' => $codigo['codigo']],
                $codigo
            );
        }

        $this->command->info('✓ 35 Códigos de actividad económica (los más comunes) cargados exitosamente.');
    }
}
