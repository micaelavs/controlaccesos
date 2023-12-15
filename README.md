# Control de Accesos

## Actualizacion Diciembre-2019: Integracion con SIGARHU
 - Anteriormente los contratos del sistema estaban representados por un Array cuyos IDs eran constantes. Ahora los IDs de contratos son **situaciones de revista**, que pertenecen a grupos de **modalidades de vinculacion** sincronizados con SIGARHU y almacenados en la tabla `situaciones_revistas`.

## Descripción
Aplicativo interno que busca llevar un control de los accesos a edificios y dependencias del Ministerio de Transporte. El objetivo es poder contar con una herramienta donde se pueda registrar el ingreso y egreso de las personas que trabajan, como así también tener un control de las personas que visitan las instalaciones.

## Dependencias
 - pdftk -> (gcj-4.8-jre-lib libasound2 libasound2-data libgcj-common libgcj14)

## Módulos
- Registro de Accesos
- Reportes
- ABM's
	- **Edificios**
	- **Empleados**
	- Perfiles de **Usuario**
	- **Contratistas**
	- **Bloqueos** de Personas (se trate de Visitas ó Empleados)
- **Auditoría**

## Perfiles
- Usuario Consulta Acceso
- Usuario ABM
- Administrador
- Auditoría


## Tipos de personas de ingreso

1. **Empleado del edificio**
	- no requiere autorizacion
	- sólo Documento
	- puede autorizar visitas
2. **Empleado c/acceso autorizado** al edificio
	- no req autorizacion
	- no cuenta como "empleado" del edificio (sino visita)
	- ingresa sólo con Documento
3. **Empleado de otro Edificio**
	- se cargan los datos automáticamente y el sistema indica que es un empleado pero requiere destino y autorizacion para ingresar.
4. **Contratista**
	- sólo con art vigente (control de backend)
	- puede tener un periodo con vigencia adicional para su ingreso (subset del periodo de art). Para aquellos casos como el de Mantenimiento de Impresoras.
	- se cargan autom. campos origen/destino, ya que estarán pre-cargados.
5. **Visita** (genérico)
	- requiere cargar todos los campos del formulario.

Ejecucion de procesos cron

php cron.php informes

Donde informes es el nombre de la acción.