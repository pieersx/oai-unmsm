# 📦 OAI-PMH Repository UNMSM

Servidor OAI-PMH (Open Archives Initiative Protocol for Metadata Harvesting) para exponer publicaciones académicas de la Universidad Nacional Mayor de San Marcos.

## 🚀 Inicio Rápido

### Requisitos
- PHP 8.2+
- Composer
- Docker & Docker Compose
- Node.js 20+

### Instalación

1. **Clonar repositorio**
```bash
git clone git@github.com:pieersx/oai-unmsm.git
cd oai-unmsm
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Iniciar base de datos**
```bash
docker-compose up -d
php artisan migrate
```

5. **Ejecutar servidor**
```bash
php artisan serve
```

La API estará disponible en `http://localhost:8000/oai-pmh`

---

## 📡 Endpoints OAI-PMH

```
GET /oai-pmh?verb=Identify
GET /oai-pmh?verb=ListRecords&metadataPrefix=oai_dc
GET /oai-pmh?verb=ListIdentifiers&metadataPrefix=oai_dc
GET /oai-pmh?verb=GetRecord&identifier=oai:unmsm:1&metadataPrefix=oai_dc
GET /oai-pmh?verb=ListMetadataFormats
GET /oai-pmh?verb=ListSets
```

## 🗄️ Base de Datos

Crear publicaciones de prueba:
```bash
php artisan db:seed --class=PublicationSeeder
```

## 📝 Licencia

MIT
