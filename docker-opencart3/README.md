![Opencart](https://www.opencart.com/application/view/image/icon/opencart-logo.png)

#  Opencart Docker para desarrollo

### Apache 2.4 + PHP 7.0 + MariaDB + Opencart 3.0.2-0-5

### Requerimientos

**MacOS:**

Instalar [Docker](https://docs.docker.com/docker-for-mac/install/), [Docker-compose](https://docs.docker.com/compose/install/#install-compose) y [Docker-sync](https://github.com/EugenMayer/docker-sync/wiki/docker-sync-on-OSX).

**Windows:**

Instalar [Docker](https://docs.docker.com/docker-for-windows/install/), [Docker-compose](https://docs.docker.com/compose/install/#install-compose) y [Docker-sync](https://github.com/EugenMayer/docker-sync/wiki/docker-sync-on-Windows).

**Linux:**

Instalar [Docker](https://docs.docker.com/engine/installation/linux/docker-ce/ubuntu/) y [Docker-compose](https://docs.docker.com/compose/install/#install-compose).

### Como usar

Para instalar Opencart, hacer lo siguiente:

```
./start
./shell
```

### Importante
La primera vez es necesario corregir unos permisos del contenedor,ingresar al contenedor
```
./shell
```

y ejecutar los comandos
```
sudo chown -R bitnami:daemon /opt/bitnami/opencart/
sudo chmod -R 775 /opt/bitnami/opencart/
```

### Paneles

**Web server:** http://localhost/

**Admin:** http://localhost/admin

    user: user
    password: bitnami1

Basado en:

[Imagen docker](https://hub.docker.com/r/bitnami/opencart)
[Imagen docker base](https://hub.docker.com/r/bitnami/minideb-extras/)
