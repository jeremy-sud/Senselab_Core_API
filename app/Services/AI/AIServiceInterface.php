<?php

namespace App\Services\AI;

/**
 * Interface para servicios de IA
 *
 * Define el contrato base para implementaciones de servicios de inteligencia artificial.
 * Desarrollado por Sistemas Ursol S.A.
 */
interface AIServiceInterface
{
    /**
     * Enviar un mensaje de chat y obtener respuesta.
     *
     * @param string $message Mensaje del usuario
     * @param array $context Contexto adicional (historial, datos del sistema)
     * @param array $options Opciones adicionales (model, temperature, etc.)
     * @return array Respuesta estructurada
     */
    public function chat(string $message, array $context = [], array $options = []): array;

    /**
     * Analizar una imagen y extraer información.
     *
     * @param string $imagePath Ruta o URL de la imagen
     * @param string $prompt Instrucciones de análisis
     * @param array $options Opciones adicionales
     * @return array Datos extraídos
     */
    public function analyzeImage(string $imagePath, string $prompt, array $options = []): array;

    /**
     * Generar embeddings para texto.
     *
     * @param string|array $text Texto o array de textos
     * @param array $options Opciones adicionales
     * @return array Vectores de embeddings
     */
    public function embeddings(string|array $text, array $options = []): array;

    /**
     * Verificar si el servicio está disponible.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Obtener estadísticas de uso.
     *
     * @return array
     */
    public function getUsageStats(): array;
}

