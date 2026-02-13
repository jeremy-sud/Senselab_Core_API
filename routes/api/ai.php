<?php

/**
 * Rutas de Inteligencia Artificial
 * - OCR, Chatbot, Predicciones, Anomalías, CABYS, Credit Scoring
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\AI\OCRController;
use App\Http\Controllers\API\AI\ChatbotController;
use App\Http\Controllers\API\AI\PredictionController;
use App\Http\Controllers\Api\V1\AI\AnomalyController;
use App\Http\Controllers\Api\V1\AI\ContentController;
use App\Http\Controllers\Api\V1\AI\CabysController;
use App\Http\Controllers\Api\V1\AI\CreditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Inteligencia Artificial (IA)
| Endpoints para OCR, Chatbot y Predicciones con IA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('ai')->group(function () {

    // ------------------------------------------------------------------------
    // OCR - Escaneo de Facturas con GPT-4 Vision
    // ------------------------------------------------------------------------
    Route::post('/ocr/invoice', [OCRController::class, 'scanInvoice'])
        ->middleware('throttle:30,1');
    Route::post('/ocr/batch', [OCRController::class, 'batchScan'])
        ->middleware('throttle:10,1');
    Route::get('/ocr/capabilities', [OCRController::class, 'capabilities']);

    // ------------------------------------------------------------------------
    // Chatbot - Asistente Virtual ERP
    // ------------------------------------------------------------------------
    Route::post('/chat', [ChatbotController::class, 'chat'])
        ->middleware('throttle:60,1');
    Route::get('/chat/suggestions', [ChatbotController::class, 'suggestions']);
    Route::delete('/chat/history', [ChatbotController::class, 'clearHistory']);

    // ------------------------------------------------------------------------
    // Predicciones - Demanda e Inventario
    // ------------------------------------------------------------------------
    Route::get('/predictions/product/{productoId}', [PredictionController::class, 'predictProduct']);
    Route::get('/predictions/alerts', [PredictionController::class, 'alerts']);
    Route::get('/predictions/recommendations', [PredictionController::class, 'recommendations']);
    Route::get('/predictions/trends', [PredictionController::class, 'trends']);
    Route::get('/predictions/revenue', [PredictionController::class, 'revenue']);
    Route::get('/predictions/dashboard', [PredictionController::class, 'dashboard']);

    // ------------------------------------------------------------------------
    // Detección de Anomalías - Auditoría Financiera
    // ------------------------------------------------------------------------
    Route::post('/anomalies/sales', [AnomalyController::class, 'detectSalesAnomalies'])
        ->middleware('throttle:20,1');
    Route::post('/anomalies/cash-flow', [AnomalyController::class, 'detectCashFlowAnomalies'])
        ->middleware('throttle:20,1');
    Route::post('/anomalies/accounting', [AnomalyController::class, 'detectAccountingAnomalies'])
        ->middleware('throttle:20,1');
    Route::post('/anomalies/audit', [AnomalyController::class, 'runFullAudit'])
        ->middleware('throttle:10,1');

    // ------------------------------------------------------------------------
    // Generación de Contenido - Emails y Reportes
    // ------------------------------------------------------------------------
    Route::post('/content/payment-reminder', [ContentController::class, 'generatePaymentReminder'])
        ->middleware('throttle:30,1');
    Route::post('/content/thank-you', [ContentController::class, 'generateThankYouEmail'])
        ->middleware('throttle:30,1');
    Route::post('/content/invoice-email', [ContentController::class, 'generateInvoiceEmail'])
        ->middleware('throttle:30,1');
    Route::post('/content/report', [ContentController::class, 'generateReport'])
        ->middleware('throttle:20,1');
    Route::post('/content/custom', [ContentController::class, 'generateCustomContent'])
        ->middleware('throttle:20,1');

    // ------------------------------------------------------------------------
    // Clasificación CABYS - Códigos de Bienes y Servicios CR
    // ------------------------------------------------------------------------
    Route::post('/cabys/classify', [CabysController::class, 'classifyProduct'])
        ->middleware('throttle:30,1');
    Route::post('/cabys/batch', [CabysController::class, 'batchClassify'])
        ->middleware('throttle:10,1');
    Route::get('/cabys/search', [CabysController::class, 'searchByDescription']);
    Route::get('/cabys/validate/{code}', [CabysController::class, 'validateCode']);
    Route::get('/cabys/suggest/{productoId}', [CabysController::class, 'suggestForProduct']);

    // ------------------------------------------------------------------------
    // Credit Scoring - Análisis de Riesgo de Clientes
    // ------------------------------------------------------------------------
    Route::get('/credit/score/{clienteId}', [CreditController::class, 'calculateScore']);
    Route::get('/credit/analysis/{clienteId}', [CreditController::class, 'getDetailedAnalysis']);
    Route::get('/credit/limit/{clienteId}', [CreditController::class, 'recommendCreditLimit']);
    Route::post('/credit/batch', [CreditController::class, 'batchCalculate'])
        ->middleware('throttle:10,1');
    Route::get('/credit/ranking', [CreditController::class, 'getRanking']);
    Route::post('/credit/evaluate', [CreditController::class, 'evaluateTransaction'])
        ->middleware('throttle:60,1');
});
