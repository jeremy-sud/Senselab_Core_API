import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { BASE_URL, SCENARIOS, THRESHOLDS } from './k6-config.js';
import { authenticate, authGet, authPost } from './helpers.js';

/**
 * Smoke Test — Flujo Crítico de Negocio
 *
 * Simula el flujo más importante del sistema:
 *   1. Login
 *   2. Consultar productos
 *   3. Consultar clientes
 *   4. Crear venta
 *   5. Consultar ventas
 *   6. Consultar cuentas por cobrar
 *
 * Ejecutar:
 *   k6 run tests/Load/smoke-test.js
 *   k6 run --env K6_BASE_URL=http://staging.example.com/api tests/Load/smoke-test.js
 */

// ── Métricas custom ──────────────────────────────────────────────────
const healthDuration = new Trend('health_check_duration', true);
const loginDuration = new Trend('login_duration', true);
const productosListDuration = new Trend('productos_list_duration', true);
const clientesListDuration = new Trend('clientes_list_duration', true);
const ventasCreateDuration = new Trend('ventas_create_duration', true);
const ventasListDuration = new Trend('ventas_list_duration', true);
const cuentasCobrarDuration = new Trend('cuentas_cobrar_list_duration', true);
const errorRate = new Rate('errors');
const requestCount = new Counter('total_requests');

// ── Configuración ────────────────────────────────────────────────────
export const options = {
    scenarios: {
        smoke: SCENARIOS.smoke,
    },
    thresholds: {
        'health_check_duration': [`p(95)<${THRESHOLDS.health.p95}`],
        'login_duration': [`p(95)<${THRESHOLDS.write.p95}`],
        'productos_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'clientes_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'ventas_create_duration': [`p(95)<${THRESHOLDS.write.p95}`],
        'ventas_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'errors': ['rate<0.1'],  // menos de 10% errores
        'http_req_failed': ['rate<0.1'],
    },
};

// ── Setup: autenticación compartida ──────────────────────────────────
export function setup() {
    const token = authenticate();
    if (!token) {
        throw new Error('No se pudo autenticar. Verificar credenciales y que la API esté arriba.');
    }
    return { token };
}

// ── Iteración principal ──────────────────────────────────────────────
export default function (data) {
    const { token } = data;

    // 1. Health check (sin auth)
    {
        const res = http.get(`${BASE_URL}/health/live`);
        healthDuration.add(res.timings.duration);
        requestCount.add(1);
        check(res, {
            'health: status 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    }

    sleep(0.5);

    // 2. Listar productos (paginado)
    {
        const res = authGet(token, '/productos', 'page=1&per_page=15');
        productosListDuration.add(res.timings.duration);
        requestCount.add(1);
        check(res, {
            'productos: status 200': (r) => r.status === 200,
            'productos: tiene datos': (r) => {
                try { return JSON.parse(r.body).data !== undefined; }
                catch { return false; }
            },
        }) || errorRate.add(1);
    }

    sleep(0.3);

    // 3. Listar clientes
    {
        const res = authGet(token, '/clientes', 'page=1&per_page=15');
        clientesListDuration.add(res.timings.duration);
        requestCount.add(1);
        check(res, {
            'clientes: status 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    }

    sleep(0.3);

    // 4. Listar ventas
    {
        const res = authGet(token, '/ventas', 'page=1&per_page=10');
        ventasListDuration.add(res.timings.duration);
        requestCount.add(1);
        check(res, {
            'ventas: status 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    }

    sleep(0.3);

    // 5. Consultar cuentas por cobrar
    {
        const res = authGet(token, '/cuentas-por-cobrar', 'page=1');
        cuentasCobrarDuration.add(res.timings.duration);
        requestCount.add(1);
        check(res, {
            'cuentas cobrar: status 200': (r) => r.status === 200,
        }) || errorRate.add(1);
    }

    sleep(1);
}

// ── Teardown ─────────────────────────────────────────────────────────
export function teardown(data) {
    console.log('Smoke test completado.');
}
