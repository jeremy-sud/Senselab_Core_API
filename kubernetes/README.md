# Kubernetes Deployment para Ursol CAST API

Este directorio contiene los manifests de Kubernetes para desplegar Ursol CAST API en un cluster.

## Estructura

```
kubernetes/
├── base/                    # Configuración base
│   ├── namespace.yaml       # Namespace ursol-cast
│   ├── configmap.yaml       # Variables de entorno
│   ├── secret.yaml          # Credenciales (editar antes de usar)
│   ├── deployment.yaml      # API Deployment (PHP-FPM + Nginx)
│   ├── service.yaml         # Services (API, MySQL, Redis)
│   ├── ingress.yaml         # Ingress con TLS
│   ├── pvc.yaml             # Persistent Volume Claims
│   ├── statefulset.yaml     # MySQL y Redis StatefulSets
│   ├── hpa.yaml             # Horizontal Pod Autoscaler
│   ├── jobs.yaml            # Queue Workers y Scheduler
│   └── kustomization.yaml   # Kustomize base
└── overlays/
    ├── development/         # Configuración para desarrollo
    │   └── kustomization.yaml
    └── production/          # Configuración para producción
        └── kustomization.yaml
```

## Requisitos Previos

1. **Cluster Kubernetes** (minikube, GKE, EKS, AKS, etc.)
2. **kubectl** configurado
3. **kustomize** instalado (o usar `kubectl apply -k`)
4. **Ingress Controller** (nginx-ingress recomendado)
5. **cert-manager** (para TLS automático)

## Instalación

### 1. Configurar Secretos

Edita `base/secret.yaml` con tus credenciales reales:

```bash
# Generar APP_KEY
php artisan key:generate --show

# Editar secretos
nano kubernetes/base/secret.yaml
```

### 2. Desplegar en Desarrollo

```bash
# Aplicar configuración de desarrollo
kubectl apply -k kubernetes/overlays/development

# Verificar despliegue
kubectl get all -n ursol-cast-dev
```

### 3. Desplegar en Producción

```bash
# Aplicar configuración de producción
kubectl apply -k kubernetes/overlays/production

# Verificar despliegue
kubectl get all -n ursol-cast-prod
```

## Componentes

### API (Deployment)
- **Réplicas**: 2 (dev: 1, prod: 3+)
- **Containers**: PHP-FPM + Nginx sidecar
- **Resources**: 256-512Mi RAM, 250-500m CPU
- **Health checks**: `/up` endpoint

### MySQL (StatefulSet)
- **Versión**: 8.0
- **Storage**: 20Gi (prod: 50Gi)
- **Resources**: 512Mi-1Gi RAM

### Redis (StatefulSet)
- **Versión**: 7-alpine
- **Storage**: 1Gi
- **Config**: appendonly, maxmemory 256mb

### Queue Workers (Deployment)
- **Réplicas**: 2 (prod: 3)
- **Queues**: hacienda, reports, emails, imports, maintenance

### Scheduler (CronJob)
- **Schedule**: Cada minuto
- **Command**: `php artisan schedule:run`

### HPA (Autoscaling)
- **Min**: 2 réplicas (prod: 3)
- **Max**: 10 réplicas (prod: 20)
- **Target**: CPU 70%, Memory 80%

## Comandos Útiles

```bash
# Ver pods
kubectl get pods -n ursol-cast-prod

# Ver logs del API
kubectl logs -f deployment/prod-ursol-cast-api -n ursol-cast-prod

# Ver logs del queue worker
kubectl logs -f deployment/prod-ursol-cast-queue-worker -n ursol-cast-prod

# Ejecutar migraciones
kubectl exec -it deployment/prod-ursol-cast-api -n ursol-cast-prod -- php artisan migrate

# Shell en el container
kubectl exec -it deployment/prod-ursol-cast-api -n ursol-cast-prod -- /bin/sh

# Escalar manualmente
kubectl scale deployment/prod-ursol-cast-api --replicas=5 -n ursol-cast-prod

# Ver HPA
kubectl get hpa -n ursol-cast-prod

# Ver Ingress
kubectl get ingress -n ursol-cast-prod
```

## Monitoreo

### Prometheus Metrics

Agregar anotaciones al deployment para scraping:

```yaml
annotations:
  prometheus.io/scrape: "true"
  prometheus.io/port: "9000"
  prometheus.io/path: "/metrics"
```

### Logs

Recomendado: Fluentd/Fluent Bit hacia ElasticSearch o Loki.

## Troubleshooting

### Pods en CrashLoopBackOff

```bash
kubectl describe pod <pod-name> -n ursol-cast-prod
kubectl logs <pod-name> -n ursol-cast-prod --previous
```

### MySQL no inicia

```bash
kubectl logs statefulset/prod-mysql -n ursol-cast-prod
kubectl describe pvc prod-mysql-pvc -n ursol-cast-prod
```

### Redis connection refused

Verificar que el service esté correcto:

```bash
kubectl get svc redis-service -n ursol-cast-prod
kubectl get endpoints redis-service -n ursol-cast-prod
```
