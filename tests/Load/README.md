# Load Testing — Ursol CAST API

Tests de carga con [k6](https://k6.io/) para validar rendimiento y detectar problemas que los tests unitarios no atrapan.

## Prerequisitos

```bash
# Instalar k6
# macOS:
brew install k6
# Linux:
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D68
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install k6
```

## Levantar el entorno

```bash
docker compose up -d
php artisan migrate:fresh --seed
```

## Scripts disponibles

| Script | Propósito | VUs | Duración |
|--------|-----------|-----|----------|
| `smoke-test.js` | Flujo crítico básico | 1 | 30s |
| `load-ventas-facturacion.js` | Endpoints financieros | 10 | 2min |
| `load-n1-detection.js` | Detectar N+1 queries | 10 | 2min |

## Ejecución

### Smoke test (verificar que nada se rompe)
```bash
k6 run tests/Load/smoke-test.js
```

### Carga normal (10 VUs)
```bash
k6 run tests/Load/load-ventas-facturacion.js
```

### Estrés (hasta 75 VUs)
```bash
k6 run --env SCENARIO=stress tests/Load/load-ventas-facturacion.js
```

### Spike (picos abruptos)
```bash
k6 run --env SCENARIO=spike tests/Load/load-ventas-facturacion.js
```

### N+1 detection
```bash
k6 run tests/Load/load-n1-detection.js
```

### Cambiar URL base (staging, producción)
```bash
k6 run --env K6_BASE_URL=https://staging.ursol.com/api tests/Load/smoke-test.js
```

### Cambiar credenciales
```bash
k6 run --env K6_USERNAME=admin@empresa.com --env K6_PASSWORD=secret tests/Load/smoke-test.js
```

## Interpretar resultados

### Métricas clave
- **http_req_duration p(95)**: 95% de requests deben completarse bajo el umbral
- **http_req_failed**: Tasa de errores HTTP (objetivo: <5%)
- **errors**: Tasa de checks fallidos

### Umbrales configurados
| Tipo | p95 | p99 |
|------|-----|-----|
| Health checks | 100ms | 200ms |
| Lecturas (GET) | 500ms | 1000ms |
| Escrituras (POST/PUT) | 1000ms | 2000ms |
| Reportes | 3000ms | 5000ms |

### Señales de N+1
En el test `load-n1-detection.js`, comparar:
- `ventas_paginated_10_duration` vs `ventas_paginated_50_duration`
- Si 50 items tarda >2x que 10 items → investigar N+1 queries
- Activar `DB::enableQueryLog()` en el endpoint sospechoso

### Output a JSON (para CI/CD)
```bash
k6 run --out json=results.json tests/Load/smoke-test.js
```
