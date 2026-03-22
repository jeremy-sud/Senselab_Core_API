import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { BASE_URL, SCENARIOS, THRESHOLDS } from './k6-config.js';
import { authenticate, authGet } from './helpers.js';

/**
 * Load Test — Detección de N+1 Queries
 *
 * Enfocado en endpoints que históricamente tienen problemas de N+1:
 *   - Listados con relaciones (ventas+detalles, comprobantes+empresa)
 *   - Consultas por cliente/proveedor con datos relacionados
 *   - Reportes que agregan datos de múltiples tablas
 *
 * Señales de N+1 en k6:
 *   - Latencia del listado crece linealmente con más registros
 *   - p95 >> p50 (alta varianza)
 *   - Endpoint de "show" más rápido que "index" con pocos registros
 *
 * Ejecutar:
 *   k6 run tests/Load/load-n1-detection.js
 *   k6 run --env SCENARIO=stress tests/Load/load-n1-detection.js
 */

// ── Métricas por endpoint ────────────────────────────────────────────
const ventasIndexDuration = new Trend('ventas_index_duration', true);
const ventasBy10Duration = new Trend('ventas_paginated_10_duration', true);
const ventasBy50Duration = new Trend('ventas_paginated_50_duration', true);
const clientesSaldoDuration = new Trend('clientes_saldo_duration', true);
const comprobantesIndexDuration = new Trend('comprobantes_index_duration', true);
const salidasPorClienteDuration = new Trend('salidas_por_cliente_duration', true);
const salidasPorAlmacenDuration = new Trend('salidas_por_almacen_duration', true);
const ordenesCompraPendDuration = new Trend('ordenes_compra_pend_duration', true);
const cuentasCobrarPorClienteDuration = new Trend('cuentas_cobrar_cliente_duration', true);
const asientosIndexDuration = new Trend('asientos_index_duration', true);
const libroMayorDuration = new Trend('libro_mayor_duration', true);
const errorRate = new Rate('errors');

// ── Configuración ────────────────────────────────────────────────────
const scenarioName = __ENV.SCENARIO || 'normal';

export const options = {
    scenarios: {
        load: SCENARIOS[scenarioName] || SCENARIOS.normal,
    },
    thresholds: {
        // Si un listado de 50 items tarda >2x que uno de 10, hay N+1
        'ventas_paginated_10_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'ventas_paginated_50_duration': [`p(95)<${THRESHOLDS.read.p95 * 2}`],
        'comprobantes_index_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'clientes_saldo_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'ordenes_compra_pend_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'asientos_index_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'libro_mayor_duration': [`p(95)<${THRESHOLDS.report.p95}`],
        'errors': ['rate<0.1'],
        'http_req_failed': ['rate<0.1'],
    },
};

// ── Setup ────────────────────────────────────────────────────────────
export function setup() {
    const token = authenticate();
    if (!token) {
        throw new Error('No se pudo autenticar.');
    }

    // Obtener IDs para consultas de detalle
    let clienteIds = [];
    let almacenIds = [];

    const cliRes = authGet(token, '/clientes', 'page=1&per_page=5');
    if (cliRes.status === 200) {
        try {
            const data = JSON.parse(cliRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                clienteIds = items.map(c => c.id).filter(Boolean);
            }
        } catch (e) { /* */ }
    }

    const almRes = authGet(token, '/almacenes', 'page=1&per_page=5');
    if (almRes.status === 200) {
        try {
            const data = JSON.parse(almRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                almacenIds = items.map(a => a.id).filter(Boolean);
            }
        } catch (e) { /* */ }
    }

    console.log(`Setup: ${clienteIds.length} clientes, ${almacenIds.length} almacenes`);
    return { token, clienteIds, almacenIds };
}

function randomItem(arr) {
    if (!arr || arr.length === 0) return null;
    return arr[Math.floor(Math.random() * arr.length)];
}

function track(res, metric, name) {
    metric.add(res.timings.duration);
    check(res, {
        [`${name}: status 2xx`]: (r) => r.status >= 200 && r.status < 300,
    }) || errorRate.add(1);
}

// ── Iteración principal ──────────────────────────────────────────────
export default function (data) {
    const { token, clienteIds, almacenIds } = data;

    // ── Test de N+1: Comparar paginación 10 vs 50 ───────────────
    group('N+1: Ventas paginación', () => {
        const res10 = authGet(token, '/ventas', 'page=1&per_page=10');
        track(res10, ventasBy10Duration, 'ventas.per_page=10');

        sleep(0.2);

        const res50 = authGet(token, '/ventas', 'page=1&per_page=50');
        track(res50, ventasBy50Duration, 'ventas.per_page=50');

        // Si per_page=50 tarda más de 3x que per_page=10, posible N+1
        if (res10.timings.duration > 0 && res50.timings.duration > res10.timings.duration * 3) {
            console.warn(`⚠️ Posible N+1 en ventas: 10items=${res10.timings.duration}ms, 50items=${res50.timings.duration}ms`);
        }
    });

    sleep(0.3);

    // ── Comprobantes electrónicos (relación empresa + tipo) ──────
    group('N+1: Comprobantes', () => {
        const res = authGet(token, '/comprobantes', 'page=1&per_page=20');
        track(res, comprobantesIndexDuration, 'comprobantes.index');
    });

    sleep(0.3);

    // ── Clientes: saldo (requiere calcular cuentas por cobrar) ──
    group('N+1: Cliente saldo', () => {
        const id = randomItem(clienteIds);
        if (id) {
            const res = authGet(token, `/clientes/${id}/saldo`);
            track(res, clientesSaldoDuration, 'clientes.saldo');
        }
    });

    sleep(0.2);

    // ── Cuentas por cobrar por cliente ───────────────────────────
    group('N+1: Cuentas cobrar por cliente', () => {
        const id = randomItem(clienteIds);
        if (id) {
            const res = authGet(token, `/cuentas-por-cobrar/cliente/${id}`);
            track(res, cuentasCobrarPorClienteDuration, 'cuentas_cobrar.por_cliente');
        }
    });

    sleep(0.2);

    // ── Órdenes de compra pendientes (relación proveedor) ────────
    group('N+1: Órdenes compra pendientes', () => {
        const res = authGet(token, '/ordenes-compra/pendientes/list');
        track(res, ordenesCompraPendDuration, 'ordenes_compra.pendientes');
    });

    sleep(0.3);

    // ── Asientos contables (relación detalles + cuentas) ─────────
    group('N+1: Asientos contables', () => {
        const res = authGet(token, '/asientos-contables', 'page=1&per_page=20');
        track(res, asientosIndexDuration, 'asientos.index');
    });

    sleep(0.3);

    // ── Libro mayor (reporte pesado) ─────────────────────────────
    group('N+1: Libro mayor', () => {
        const res = authGet(token, '/detalle-asientos/reportes/libro-mayor');
        track(res, libroMayorDuration, 'reportes.libro_mayor');
    });

    sleep(0.5);
}

export function teardown() {
    console.log('N+1 detection test completado.');
    console.log('Comparar ventas_paginated_10_duration vs ventas_paginated_50_duration.');
    console.log('Si 50 items tarda >2x que 10 items, investigar N+1 queries.');
}
