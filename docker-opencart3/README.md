![Opencart](https://www.opencart.com/application/view/image/icon/opencart-logo.png)

#  Opencart Docker para desarrollo

### PHP 7.0 + MariaDB + Opencart 3.0.2-0

### Requerimientos

**MacOS:**

Instalar [Docker](https://docs.docker.com/docker-for-mac/install/), [Docker-compose](https://docs.docker.com/compose/install/#install-compose) y [Docker-sync](https://github.com/EugenMayer/docker-sync/wiki/docker-sync-on-OSX).

**Windows:**

Instalar [Docker](https://docs.docker.com/docker-for-windows/install/), [Docker-compose](https://docs.docker.com/compose/install/#install-compose) y [Docker-sync](https://github.com/EugenMayer/docker-sync/wiki/docker-sync-on-Windows).

**Linux:**

Instalar [Docker](https://docs.docker.com/engine/installation/linux/docker-ce/ubuntu/) y [Docker-compose](https://docs.docker.com/compose/install/#install-compose).

### Como usar

Para instalar Opencart, hacer lo siguiente:

**NOTA:** La primera vez que se ejecuta ./start o ./build demorará en instalar todo, esperar al menos unos 5 minutos.

### Construir el contenedor desde cero

```
./build
```

### Iniciar el contenedor construido anteriormente

```
./start
```

### Acceder al contenedor

```
./shell
```

### Importante
La primera vez es necesario corregir unos permisos del contenedor, ingresar al contenedor
```
./shell
```

y ejecutar los siguientes comandos
```
sudo chown -R bitnami:daemon /opt/bitnami/opencart/
sudo chmod -R 775 /opt/bitnami/opencart/
```

### Paneles

**Web server:** http://localhost/

**Admin:** http://localhost/admin

    user: admin
    password: password

### Archivo de logs del plugin

```
./shell
tail -f /bitnami/opencart/system/storage/logs/onepay-log.log
```
    
Basado en:

[Imagen docker](https://hub.docker.com/r/bitnami/opencart)
