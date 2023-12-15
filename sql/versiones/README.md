## INSTALACION DE LOS ARCHIVOS DE VERSIÓN.

Para instalar los archivos de versión previamente debe ejecutarse el "composer install" y deben configurarse las conexiones a DB.

El archivo "version.php" se encarga de reemplazar todas las etiqueta existentes en el archivo de versión. Se ejecuta por linea de comandos y espera como argumento el nombre del archivo al que se desea reemplazar etiquetas. 
*Ej:* **php ./version.php v_1**

Para instalar en la base de datos debe redireccionarse por tuberías la salida estandar de "version.php" hacia la conexión de mysql.
*Ej:* **php sql/versiones/version.php v_1 | mysql -u USUARIO_ADMINISTRACION -p -h mtnldb570.transporte.gob.ar**

**Nota:**
No confundir el "usuario de administración" con el "usuario de aplicación" ya que este último no posee permisos de creación ni actualización, necesarios para ejecutar un script de instalación en la db.

Si se desea ejecutar archivos sql de desarrollo debe anteponerse "../" al nombre de archivo. 
El script acepta el nombre de archivo con o sin la extensión.