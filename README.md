# Filament Media Manager
## Setup

Ejecutar los siguientes comandos:

### Publica migrations

```bash
  php artisan vendor:publish --tag=media-manager-migrations
```

### Publica la configuración del paquete

```bash
  php artisan vendor:publish --tag=media-manager-config
```

### Publica el Seeder del MediaVault necesario

```bash
  php artisan vendor:publish --tag=media-manager-seeders
```

### Ejecutar el Seeder del MediaVault

```bash
  php artisan db:seed --class=MediaVaultSeeder
```