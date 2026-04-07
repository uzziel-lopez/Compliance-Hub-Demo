# Compliance Hub Demo

Aplicacion web para gestion de cumplimiento normativo con enfoque en expedientes, operaciones y trazabilidad de procesos.

## Aviso importante

- Proyecto publicado con fines de portafolio tecnico.
- La informacion usada para demostracion es ficticia o anonimizada.

## Funcionalidades principales

- Gestion de usuarios, empresas y clientes.
- Administracion de carpetas, documentos y vigencias.
- Notificaciones de actividad y seguimiento.
- Registro de operaciones sujetas a cumplimiento.
- Reportes y exportaciones operativas.

## Estructura del proyecto

- `app/`: controladores, conexion a datos y logica de aplicacion.
- `backoffice/`: vistas y modulos funcionales.
- `resources/`: JavaScript, estilos y recursos de interfaz.
- `uploads/`: carpeta local para archivos en ejecucion.
- `db/`: referencia minima para entorno local.

## Configuracion local

Este proyecto usa variables de entorno para configuracion sensible:

- `APP_DB_HOST`
- `APP_DB_NAME`
- `APP_DB_USER`
- `APP_DB_PASSWORD`
- `APP_SMTP_ENABLED`
- `APP_SMTP_HOST`
- `APP_SMTP_PORT`
- `APP_SMTP_USERNAME`
- `APP_SMTP_PASSWORD`
- `APP_SMTP_FROM_NAME`
- `APP_SMTP_FROM_EMAIL`

Crea un archivo `.env` local tomando como base `.env.example`.
