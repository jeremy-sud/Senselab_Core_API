/**
 * Configuración compartida para tests de carga k6
 *
 * Variables de entorno:
 *   K6_BASE_URL  - URL base de la API (default: http://localhost:8000/api)
 *   K6_USERNAME  - Email para login (default: admin@senselab.com)
 *   K6_PASSWORD  - Password para login (default: password)
 */

export const BASE_URL = __ENV.K6_BASE_URL || 'http://localhost:8000/api';
export const USERNAME = __ENV.K6_USERNAME || 'admin@senselab.com';
export const PASSWORD = __ENV.K6_PASSWORD || 'password';

/**
 * Umbrales de rendimiento por tipo de endpoint.
 * p95 = percentil 95, p99 = percentil 99
 */
export const THRESHOLDS = {
    // Health checks: deben ser instantáneos
    health: { p95: 100, p99: 200 },
    // Lecturas (GET index/show): latencia aceptable
    read: { p95: 500, p99: 1000 },
    // Escrituras (POST/PUT): pueden ser más lentas
    write: { p95: 1000, p99: 2000 },
    // Reportes/PDFs: operaciones pesadas
    report: { p95: 3000, p99: 5000 },
};

/**
 * Escenarios de carga predefinidos
 */
export const SCENARIOS = {
    // Smoke test: 1-2 VUs, verificar que nada se rompe
    smoke: {
        executor: 'constant-vus',
        vus: 1,
        duration: '30s',
    },
    // Carga normal: uso típico
    normal: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 10 },  // ramp up
            { duration: '1m', target: 10 },    // steady
            { duration: '30s', target: 0 },    // ramp down
        ],
    },
    // Estrés: encontrar el punto de quiebre
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 10 },
            { duration: '1m', target: 25 },
            { duration: '1m', target: 50 },
            { duration: '30s', target: 75 },
            { duration: '30s', target: 0 },
        ],
    },
    // Spike: picos abruptos
    spike: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '10s', target: 5 },
            { duration: '5s', target: 50 },   // spike
            { duration: '30s', target: 50 },
            { duration: '5s', target: 5 },    // drop
            { duration: '30s', target: 5 },
            { duration: '10s', target: 0 },
        ],
    },
};
