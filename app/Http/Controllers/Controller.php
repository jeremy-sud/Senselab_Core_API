<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="2.0.0",
 *         title="Senselab Core API 2.0 - Open Source ERP System",
 *         description="API completa de ERP para empresas costarricenses y globales. Sistema integral de gestión empresarial con soporte para contabilidad, facturación electrónica DGT v4.4, inventario, compras, ventas, nómina y Eón AI. Senselab 2.0: plataforma abierta, gratuita y orientada a código abierto en GitHub.",
 *         @OA\Contact(
 *             name="Senselab Engineering Team",
 *             email="contacto@scisenselab.com",
 *             url="https://github.com/jeremy-sud/Senselab_Core_API"
 *         )
 *     ),
 *     @OA\Server(
 *         url="https://api.scisenselab.com",
 *         description="Servidor de Producción (api.scisenselab.com)"
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="Servidor de Desarrollo Local"
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
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use ApiResponse;
}
