# SIGEL

SIGEL es un Sistema de Gestión de Préstamo de Equipos de Laboratorio desarrollado para el curso ISW-613 - Programación en Ambiente Web I.

El objetivo del sistema es llevar un control de los equipos de laboratorio, los usuarios y los préstamos que se realizan.

El sistema permite registrar equipos, categorías, usuarios y préstamos. También permite controlar el estado de cada préstamo, registrar devoluciones y calcular los días de mora cuando un equipo se devuelve después de la fecha establecida.

## Roles del sistema

El sistema utiliza tres roles:

- Administrador
- Operador
- Profesor

El administrador puede gestionar usuarios, categorías, equipos, préstamos y reportes.

El operador puede consultar equipos, registrar préstamos, cambiar estados y registrar devoluciones.

El profesor no inicia sesión en el sistema. Se utiliza para asignarle los préstamos de los equipos.

## Funciones principales

El sistema permite:

- Iniciar sesión.
- Registrar y administrar usuarios.
- Registrar y administrar categorías.
- Registrar y administrar equipos.
- Agregar imágenes a los equipos.
- Registrar préstamos.
- Asignar préstamos a profesores.
- Cambiar el estado de los préstamos.
- Registrar devoluciones.
- Calcular días de mora.
- Consultar los equipos vistos recientemente.
- Generar reportes.
- Exportar reportes a CSV.

## Estados de los préstamos

Los préstamos pueden tener los siguientes estados:

- solicitado
- aprobado
- entregado
- devuelto
- cancelado

Cuando un préstamo se registra, inicia con el estado solicitado.

Después puede pasar a aprobado, entregado y finalmente devuelto. También puede ser cancelado cuando corresponda.

## Cálculo de mora

Cuando se registra una devolución, el sistema compara la fecha de devolución con la fecha final del préstamo.

Si el equipo se devuelve a tiempo, los días de mora son 0.

Si se devuelve después de la fecha indicada, el sistema calcula la cantidad de días de atraso y guarda ese valor en el préstamo.

## Tecnologías utilizadas

- PHP 8
- MySQL / MariaDB
- Apache
- XAMPP
- HTML
- CSS

## Usuarios de prueba

Administrador:

Usuario: admin
Contraseña: Admin1234


Operador:

Usuario: operador
Contraseña: Operador1234



Profesor:

Usuario: profesor1


El profesor no inicia sesión.

## Base de datos

La base de datos se crea con:

sql/schema.sql


Los datos iniciales se agregan con:

sql/seed.sql


## Carpetas principales


config/
includes/
public/
sql/
storage/


Las imágenes de los equipos se guardan en:

storage/equipment_images/
