# Changelog

Todos los cambios relevantes de este paquete se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado se adhiere a [SemVer](https://semver.org/lang/es/).

## [Sin publicar]

### Añadido

- **API programática** para incorporar recursos desde código, vía el facade
  `Marzio\MediaManager\Facades\MediaManager`:
  - `add()` — sube un fichero desde un disco de Laravel (por defecto `public`),
    con carpeta opcional que se crea si no existe. Devuelve el `Media`, así que
    admite `->getUrl()`.
  - `addFromFile()` — igual, pero desde una ruta absoluta del sistema de
    ficheros.
  - `url()` — atajo que devuelve directamente la URL pública.
  - `folder()` / `findFolder()` — resolución de carpetas con y sin creación.
- `Support\MediaFolderResolver` — resolución y creación de carpetas anidadas a
  partir de una ruta legible (`"documentos/facturas"`), reutilizable desde
  cualquier punto del paquete.

### Cambiado

- **Las tres migraciones se unifican en una sola**, `create_media_manager_tables`.
  Es idempotente: comprueba el estado de cada tabla y columna antes de actuar,
  por lo que es segura sobre instalaciones que ya ejecutaron las migraciones
  antiguas.
- **Publicar migraciones ahora respeta la convención de fechas del proyecto.**
  El archivo se copia con la fecha del momento de publicación. Si ya existía una
  copia publicada, se reutiliza ese nombre para que republicar la actualice en
  lugar de crear un duplicado con otra fecha.
- **La autocarga se desactiva cuando existe una copia publicada.** Antes el
  paquete autocargaba sus migraciones desde `vendor` y además las publicaba, de
  modo que publicarlas hacía correr la misma migración dos veces bajo nombres
  distintos.
- Las migraciones solo se registran cuando la aplicación corre en consola,
  evitando trabajo innecesario en cada request web.
- README: el orden de instalación estaba invertido. Publicar las migraciones de
  Spatie Media Library ahora es el paso 2, antes de las del paquete.

### Corregido

- **La columna `media_folder_id` no se creaba** cuando la migración
  `create_media_table` de Spatie tenía una fecha posterior a la del paquete —
  el caso normal, ya que el proyecto anfitrión la publica con la fecha del día.
  La migración del paquete corría primero, encontraba que la tabla `media` aún
  no existía y retornaba en silencio, quedando marcada como ejecutada. El
  resultado era un `SQLSTATE[42S22]: Unknown column 'media_folder_id'` al abrir
  el gestor.

  La migración unificada usa el timestamp `9999_12_31_000000`, que garantiza que
  se ejecute al final de la cola sin importar cuándo se instaló el paquete. Y si
  aun así no encuentra la tabla `media`, ahora lanza una excepción con
  instrucciones en lugar de fallar en silencio.

### Migrar desde una versión anterior

No se requiere ninguna acción. La nueva migración detecta las tablas y columnas
existentes y solo crea lo que falte. Las tres migraciones antiguas ya ejecutadas
permanecen registradas en la tabla `migrations` sin causar conflicto.
