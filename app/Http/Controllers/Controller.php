<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Ursol CAST API - ERP System",
 *         description="API completa de ERP para empresas costarricenses. Sistema integral de gestión empresarial con soporte para contabilidad, facturación electrónica, inventario, compras, ventas, recursos humanos y más.",
 *         @OA\Contact(
 *             name="Sistemas Ursol",
 *             email="sistemas@ursol.com"
 *         )
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="Servidor de Desarrollo Local"
 *     ),
 *     @OA\Server(
 *         url="https://api.ursol.com",
 *         description="Servidor de Producción"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Bearer Token. Obtén el token mediante POST /api/login"
 * )
 */
abstract class Controller
{
    //
}
