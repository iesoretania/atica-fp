# Instalación y puesta en marcha

Este capítulo es para la persona que instala ÁTICA FP en el servidor del centro o de la
administración educativa. Cubre **solo lo mínimo para que la aplicación funcione**: dejarla
arrancada, crear el primer administrador y el primer centro, y programar las tareas periódicas. El
despliegue en detalle, las actualizaciones y el mantenimiento están en el anexo técnico
[Despliegue y mantenimiento](92-despliegue-y-mantenimiento.md).

Si tu centro ya tiene la aplicación en marcha, puedes saltarte este capítulo y pasar a
[Primeros pasos](02-primeros-pasos.md).

## Qué necesitas

- **PHP 8.2 o superior.**
- **Servidor web Apache2** con `mod_rewrite` activado (con nginx podría funcionar, pero no se ha
  probado).
- **MySQL 8.0.28 o superior**, o un derivado equivalente (MariaDB, Percona...).
- **Composer** y **Node.js** (versión 18 a 20) para instalar las dependencias y generar los
  ficheros de la interfaz.
- Una cuenta de correo desde la que la aplicación pueda enviar mensajes (recuperación de
  contraseña y avisos). Sin ella la aplicación funciona, pero esos mensajes no llegan.

## Probarla en cinco minutos con Docker

Si solo quieres verla funcionar para decidir si te sirve, la forma más rápida es Docker. No es una
instalación para producción.

1. Desde la carpeta del proyecto, ejecuta `docker-compose up -d`.
2. Espera a que termine (la primera vez tarda bastante).
3. Abre `http://127.0.0.1:9999` en el navegador. El usuario es `admin` y la contraseña `admin`; la
   aplicación te pedirá cambiarla en el primer acceso.

Para arrancarla con datos de ejemplo (dos centros con los tres módulos y un usuario de cada perfil)
usa `DEMO=1 docker-compose up -d`. En ese caso el usuario administrador es `admin` con la contraseña
`aticafp`, y el resto de usuarios de ejemplo (`alfa.admin`, `alfa.tutor`, `alfa.alumno1`...) tienen la
contraseña `demo1234`.

!!! warning "Solo para pruebas"
    Los datos de la base de datos quedan en la carpeta `data`. No uses esta configuración con datos
    reales de alumnado.

## Instalarla en un servidor

1. **Descarga la aplicación** en la carpeta que quieras, con `git clone`, y ejecuta
   `composer install` dentro de ella.
2. **Configura el entorno.** Copia `.env.local.sample` como `.env.local` y cambia, al menos, dos
   variables:
    - `APP_SECRET`: pon un valor aleatorio y largo. No dejes el del ejemplo.
    - `DATABASE_URL`: los datos de acceso a tu base de datos.
3. **Genera los ficheros de la interfaz:** `npm install` y después `npm run build`.
4. **Apunta Apache a la carpeta `public/`.** El `DocumentRoot` de tu sitio debe ser la carpeta
   `public/` dentro de la instalación.
5. **Crea la base de datos y las tablas:**
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:schema:create
    php bin/console doctrine:migrations:version --add --all
    ```

## Crear el primer centro y el primer administrador

Con la base de datos vacía, hacen falta un centro y un usuario con el que entrar. Ejecuta, cambiando
los datos por los de tu centro:

```bash
php bin/console app:organization "I.E.S. Ada Lovelace" --code=29700456 --city=Linares
php bin/console app:admin admin --firstname=Admin --lastname=ATICA --password=admin
```

El primer comando crea el centro. El segundo crea el usuario `admin` como **administrador global**,
que puede gestionar todos los centros de la plataforma. Cámbiale la contraseña la primera vez que
entres (lo verás en [Primeros pasos](02-primeros-pasos.md#cambiar-tu-contrasena)).

A partir de aquí, todo se hace desde el navegador: ve a
[Preparar el curso académico](03-preparar-el-curso-academico.md) para continuar.

## Tareas periódicas {#tareas-periodicas}

ÁTICA FP envía avisos por correo al profesorado de FP dual (por ejemplo, si el alumnado lleva días
sin registrar su actividad o hay encuestas pendientes). Para ello necesita que el servidor ejecute
una vez al día el comando de tareas:

```bash
php bin/console app:cron
```

Prográmalo con `cron` en el servidor (por ejemplo, cada mañana a primera hora). Si no lo programas, la
aplicación sigue funcionando, pero no se envían esos avisos.

## Una copia de seguridad

Antes de empezar a cargar datos reales, comprueba que sabes sacar una copia de seguridad de la base de
datos:

```bash
php bin/console app:backup --timestamp
```

El comando guarda una copia con la fecha y la hora en la carpeta `backups`. Cómo programarla,
cómo restaurarla y cómo actualizar la aplicación a una versión nueva están en
[Despliegue y mantenimiento](92-despliegue-y-mantenimiento.md).

## Comprobar que todo funciona

1. Abre la dirección de tu servidor: debe aparecer la pantalla de acceso con el nombre de la
   aplicación.
2. Entra con el usuario `admin`. Si has creado un solo centro, entrarás directamente en él.
3. Si no llega ningún correo de recuperación de contraseña, revisa la variable `MAILER_DSN` de tu
   `.env.local` (el anexo técnico explica cómo configurarla).
