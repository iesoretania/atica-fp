# Prueba de la aplicación

# Prueba rápida mediante Docker Compose

**ATENCIÓN: No se recomienda ejecutarlo así en entornos de producción, tan sólo se sugiere para pruebas internas.**
- Ejecutar `docker-compose up -d` desde la carpeta del proyecto
    * El usuario será `admin` y la contraseña `admin`. Habrá que cambiarla en la primera entrada.
    * ¿Quieres cargar unos datos de prueba en vez de que esté vacío?
        * Si usas Linux, con el comando `DEMO=1 docker-compose up -d`
        * Si usas Windows, abre un PowerShell y ejecuta `$env:DEMO=1;  docker-compose up -d`
        * En estos caso, el usuario será `admin` y la contraseña `aticafp`
- Esperar...
- Acceder desde el navegador a la dirección http://127.0.0.1:9999
    * Si usas Docker Toolbox usa esta dirección en su lugar: http://192.168.99.100:9999
- ¡Listo!

**NOTA: La carpeta `data` contendrá la base de datos, puedes sacar copias de seguridad de la misma si lo estimas conveniente.**
