import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { BASE_URL, SCENARIOS, THRESHOLDS } from './k6-config.js';
import { authenticate, authGet, authPost, authPut } from './helpers.js';

/**
 * Load Test — Endpoints de Ventas y Facturación
 *
 * Enfocado en los módulos financieros core:
 *   - Ventas CRUD
 *   - Clientes
 *   - Productos/Inventario
 *   - Comprobantes electrónicos
 *   - Cuentas por cobrar
 *
 * Ejecutar (normal):
 *   k6 run tests/Load/load-ventas-facturacion.js
 *
 * Ejecutar (estrés):
 *   k6 run --env SCENARIO=stress tests/Load/load-ventas-facturacion.js
 */

// ── Métricas custom ──────────────────────────────────────────────────
const productosListDuration = new Trend('productos_list_duration', true);
const productosShowDuration = new Trend('productos_show_duration', true);
const clientesListDuration = new Trend('clientes_list_duration', true);
const ventasListDuration = new Trend('ventas_list_duration', true);
const ventasShowDuration = new Trend('ventas_show_duration', true);
const comprobantesListDuration = new Trend('comprobantes_list_duration', true);
const comprobantesShowDuration = new Trend('comprobantes_show_duration', true);
const cuentasCobrarDuration = new Trend('cuentas_cobrar_duration', true);
const cuentasCobrarVencidasDuration = new Trend('cuentas_cobrar_vencidas_duration', true);
const inventarioStockDuration = new Trend('inventario_stock_duration', true);
const contabilidadAsientosDuration = new Trend('contabilidad_asientos_duration', true);
const errorRate = new Rate('errors');
const requestCount = new Counter('total_requests');

// ── Configuración ────────────────────────────────────────────────────
const scenarioName = __ENV.SCENARIO || 'normal';
const selectedScenario = SCENARIOS[scenarioName] || SCENARIOS.normal;

export const options = {
    scenarios: {
        load: selectedScenario,
    },
    thresholds: {
        'productos_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'productos_show_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'clientes_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'ventas_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'ventas_show_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'comprobantes_list_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'cuentas_cobrar_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'inventario_stock_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'contabilidad_asientos_duration': [`p(95)<${THRESHOLDS.read.p95}`],
        'errors': ['rate<0.05'],       // menos de 5% errores
        'http_req_failed': ['rate<0.05'],
        'http_req_duration': [`p(95)<${THRESHOLDS.read.p95}`],
    },
};

// ── Setup ────────────────────────────────────────────────────────────
export function setup() {
    const token = authenticate();
    if (!token) {
        throw new Error('No se pudo autenticar.');
    }

    // Precargar IDs para usar en show/detail requests
    let productoIds = [];
    let clienteIds = [];
    let ventaIds = [];
    let comprobanteIds = [];

    // Obtener algunos IDs de productos
    const prodRes = authGet(token, '/productos', 'page=1&per_page=5');
    if (prodRes.status === 200) {
        try {
            const data = JSON.parse(prodRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                productoIds = items.map(p => p.id).filter(Boolean);
            }
        } catch (e) { /* sin datos */ }
    }

    // Obtener algunos IDs de clientes
    const cliRes = authGet(token, '/clientes', 'page=1&per_page=5');
    if (cliRes.status === 200) {
        try {
            const data = JSON.parse(cliRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                clienteIds = items.map(c => c.id).filter(Boolean);
            }
        } catch (e) { /* sin datos */ }
    }

    // Obtener algunos IDs de ventas
    const ventaRes = authGet(token, '/ventas', 'page=1&per_page=5');
    if (ventaRes.status === 200) {
        try {
            const data = JSON.parse(ventaRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                ventaIds = items.map(v => v.id).filter(Boolean);
            }
        } catch (e) { /* sin datos */ }
    }

    // Obtener algunos IDs de comprobantes
    const compRes = authGet(token, '/comprobantes', 'page=1&per_page=5');
    if (compRes.status === 200) {
        try {
            const data = JSON.parse(compRes.body);
            const items = data.data || data;
            if (Array.isArray(items)) {
                comprobanteIds = items.map(c => c.id).filter(Boolean);
            }
        } catch (e) { /* sin datos */ }
    }

    console.log(`Setup: ${productoIds.length} productos, ${clienteIds.length} clientes, ` +
        `${ventaIds.length} ventas, ${comprobanteIds.length} comprobantes`);

    return { token, productoIds, clienteIds, ventaIds, comprobanteIds };
}

// ── Helpers ──────────────────────────────────────────────────────────
function randomItem(arr) {
    if (!arr || arr.length === 0) return null;
    return arr[Math.floor(Math.random() * arr.length)];
}

function trackRequest(res, metric, checkName) {
    metric.add(res.timings.duration);
    requestCount.add(1);
    check(res, {
        [`${checkName}: status 2xx`]: (r) => r.status >= 200 && r.status < 300,
    }) || errorRate.add(1);
}

// ── Iteración principal ──────────────────────────────────────────────
export default function (data) {
    const { token, productoIds, clienteIds, ventaIds, comprobanteIds } = data;

    // ── Productos ────────────────────────────────────────────────
    group('Productos', () => {
        // Listar con paginación (variada)
        const page = Math.ceil(Math.random() * 3);
        const res = authGet(token, '/productos', `page=${page}&per_page=15`);
        trackRequest(res, productosListDuration, 'productos.index');

        sleep(0.2);

        // Detalle de un producto específico
        const id = randomItem(productoIds);
        if (id) {
            const showRes = authGet(token, `/productos/${id}`);
            trackRequest(showRes, productosShowDuration, 'productos.show');

            // Stock del producto
            const stockRes = authGet(token, `/productos/${id}/stock`);
            trackRequest(stockRes, inventarioStockDuration, 'productos.stock');
        }
    });

    sleep(0.3);

    // ── Clientes ─────────────────────────────────────────────────
    group('Clientes', () => {
        const res = authGet(token, '/clientes', 'page=1&per_page=15');
        trackRequest(res, clientesListDuration, 'clientes.index');

        const id = randomItem(clienteIds);
        if (id) {
            const saldoRes = authGet(token, `/clientes/${id}/saldo`);
            trackRequest(saldoRes, clientesListDuration, 'clientes.saldo');
        }
    });

    sleep(0.3);

    // ── Ventas ───────────────────────────────────────────────────
    group('Ventas', () => {
        const res = authGet(token, '/ventas', 'page=1&per_page=10');
        trackRequest(res, ventasListDuration, 'ventas.index');

        const id = randomItem(ventaIds);
        if (id) {
            const showRes = authGet(token, `/ventas/${id}`);
            trackRequest(showRes, ventasShowDuration, 'ventas.show');
        }
    });

    sleep(0.3);

    // ── Comprobantes Electrónicos ────────────────────────────────
    group('Comprobantes', () => {
        const res = authGet(token, '/comprobantes', 'page=1&per_page=10');
        trackRequest(res, comprobantesListDuration, 'comprobantes.index');

        const id = randomItem(comprobanteIds);
        if (id) {
            const showRes = authGet(token, `/comprobantes/${id}`);
            trackRequest(showRes, comprobantesShowDuration, 'comprobantes.show');
        }
    });

    sleep(0.3);

    // ── Cuentas por Cobrar ───────────────────────────────────────
    group('Cuentas por Cobrar', () => {
        const res = authGet(token, '/cuentas-por-cobrar', 'page=1');
        trackRequest(res, cuentasCobrarDuration, 'cuentas_cobrar.index');

        const vencidasRes = authGet(token, '/cuentas-por-cobrar/vencidas/list');
        trackRequest(vencidasRes, cuentasCobrarVencidasDuration, 'cuentas_cobrar.vencidas');

        const resumenRes = authGet(token, '/cuentas-por-cobrar/resumen/por-estado');
        trackRequest(resumenRes, cuentasCobrarDuration, 'cuentas_cobrar.resumen');
    });

    sleep(0.3);

    // ── Contabilidad ─────────────────────────────────────────────
    group('Contabilidad', () => {
        const res = authGet(token, '/asientos-contables', 'page=1&per_page=10');
        trackRequest(res, contabilidadAsientosDuration, 'asientos.index');

        const cuentasRes = authGet(token, '/cuentas-contables', 'page=1');
        trackRequest(cuentasRes, contabilidadAsientosDuration, 'cuentas_contables.index');
    });

    sleep(0.5);
}

// ── Teardown ─────────────────────────────────────────────────────────
export function teardown(data) {
    const scenario = __ENV.SCENARIO || 'normal';
    console.log(`Load test (${scenario}) completado.`);
}
