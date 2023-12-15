<?php
namespace App\Modelo;
use FMT\Roles;
use FMT\Usuarios;

class AppRoles extends Roles{

	const VER = 1;
	const EDITAR = 2;
	const NO_ACCEDE = 0;

	const ADMINISTRADOR_CIET = 1;
	const REGISTRO_DE_ACCESO = 2;
	const CARGA_DE_DATOS = 3;
	const AUDITOR = 4;
	const ADMINISTRADOR_RRHH = 5;
	const ADMINISTRADOR_CONVENIOS = 6;
	const ROL_DIS = 7;
	const ENROLADOR = 8;
	const ENROLADOR_DIS = 9;
	const RCA = 10;
	const EMPLEADO_CIET = 11;
	const ROL_DEFAULT = 0;
	const ROL_ADMINISTRACION = 12;

	static $rol;
	static $permisos= [
		self::ROL_ADMINISTRACION => [
			'nombre'	=> 'Administrador del sistema',
			'padre'		=> self::ROL_DEFAULT,
			'inicio'	=> ['control' => 'usuarios','accion' => 'index'],
			'roles_permitidos' => [
				self::ROL_ADMINISTRACION,
			],
			'atributos' => [
				'campos' => [],
			],	 
			'permisos'	=> [
				'Ejemplos' => [
					'index'		=> true,
				],
				'Usuarios' => [
					'index'		=> 1,
					'alta'		=> 1,
					'modificar'	=> 1,
					'baja'		=> 1,
					'ajax_usuarios' => 1,
					'buscarAutorizanteAjax' =>true,
				],
				'Personas'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_personas'	=>true,
					'buscarPersonaAjax'	=>true,
				],
				'Empleados'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_empleados'	=>true,
					'exportar_excel_empleados' =>true,
					'buscarDatosEmpleadoAjax'	=>true,
					'buscar_user'	=>true,
					'modificacion_contrato'	=>true,
					'finalizar_contrato'	=>true,
				],
				'Contratistas'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_contratistas'	=>true,
					'buscar_contratista'	=>true,
					'ajax_localidades' => true,
				],
				'Contratistaspersonal'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_contratistas_personal' =>true,
					'ubicaciones' =>true,
					'ajax_contratistas_ubicaciones' =>true,
					'buscarPersonalAjax' =>true,
					'buscarAutorizanteAjax' =>true,
					'ubicacion_baja' =>true,
					'ubicacion_editar' =>true,
				],				
				'Advertencias'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_advertencias'	=>true,
					'buscarPersonalAjax' =>true,
					'buscarSolicitanteAjax' =>true,
				],
				'Advertenciasgenericas'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_advertenciasgenericas'=>true,
				],
				'Pertenencias'=>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_pertenencias'	=>true,
					'buscar_documento'	=>true,
					'buscar_documento_solicitante'	=>true,
				],
				'Accesosbio' =>[
					'index'  => true,
					'sincronizar' => true,
					'ajax_accesosbio' => true,
				],
				'Accesos'=>[
					'historico_visitas_contratistas' => 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'horas_trabajadas' => 1,
					'ajax_horas_trabajadas' => 1,
					'horas_trabajadas_excel' => 1,
					'reporte_excel_accesos_empleados' => 1,
					'reporte_excel_accesos_empleados_horas' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1,
					'horas_trabajadas' => 1,
					'horas_trabajadas_excel' => 1,
					'informe_mensual' => 1,
					'informe_mensual_pdf' => 1,
					'planilla_reloj' => 1,
					'planilla_reloj_pdf' => 1,
				],
			]
		],
		self::RCA => [
			'nombre'=> 'RCA',
			'padre' => self::ROL_DEFAULT,
			'tiene_dependencias' => true,
      'atributos_visibles' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos_select' =>  [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'inicio'=> ['control' => 'empleados','accion' => 'index'],
			'manual' => 'rca.html',
			'permisos'=>[
				'Accesos' =>[
					'planilla_reloj' => 1,
					'planilla_reloj_pdf' => 1,
					'calendario_planilla_reloj' => 1,
					'detalle_calendario_reloj'	=> 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'exportar_excel_historico_empleados' => 1,
					'horas_trabajadas' => 1,
					'horas_trabajadas_excel' => 1,
					'ajax_horas_trabajadas' => 1,
					'reporte_excel_accesos_empleados_horas' => 1,
					'informe_mensual_accesos' => 1
				],
				'Novedades' =>[
					'index' => 1,
					'ajax_novedades'	=> 1,
					'exportar_excel'=> 1,
					'actualizarTipoNovedad' => 1,
					'alta' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'buscarEmpleado'=>1,
					'ajax_ultimas_siete' => 1,
					'exportar_ultimas_siete_excel' => 1
				],
				'Empleados' =>[
					'index' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'json_buscar_registro_empleado' =>1,
					'empleado_horarios' => 1,
					'plantilla_horaria' => 1,
					'inactivos' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' => 1
				],
				'Horarios' =>[
					'index' => 1,
					'crear' => 1,
					'alta' => 1,
					'editar' => 1,
					'modificacion' => 1,
					'baja' => 1,
					'desvios_horarios' => 1,
					'json_desvios_horarios' => 1,

				],
				'Base' =>[
					'manual' => 1
				],
			]
		],
		self::ENROLADOR_DIS => [
			'nombre'=> 'ENROLADOR DIS',
			'padre' => self::ROL_DEFAULT,
			'roles_permitidos' => [self::ADMINISTRADOR_CIET,self::EMPLEADO_CIET,self::REGISTRO_DE_ACCESO, self::AUDITOR,self::ADMINISTRADOR_RRHH,self::ADMINISTRADOR_CONVENIOS,self::ROL_DIS,self::ENROLADOR,self::ENROLADOR_DIS,self::RCA, self::ROL_DEFAULT],
			'atributos_visibles' =>  [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos_select' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'inicio'=> ['control' => 'empleados','accion' => 'index'],
			'manual' => 'enrolador_dis.html',
			'permisos'=>[
				'Empleados' =>[
					'index' =>1,
					'alta' =>1,
					'baja' =>1,
					'modificacion' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'buscar_user' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' => 1,
					'modificacion' =>1,
					'editar' =>1,
					'json_buscar_registro_empleado' =>1,
					'json_buscar_empleado' =>1,
					'json_buscar_registro_autorizante' =>1,
					'buscar_autorizante' =>1,
					'finalizar_contrato' => 1,
					'baja_principal' =>1,
					'enrolar'=>1,
					'buscar_template_por_access_id'=>1,
					'guardar_template_por_access_id'=>1,
					'distribuir_templates_de_empleado'=>1,
					'enviar_empleado_a_enrolador'=>1,
					'actualizar_ubicacion' => 1,
					'inactivos' =>1,
					'modificacion_contrato' =>1,
					'situaciones_revista' =>1,
					'buscarDatosEmpleadoAjax'	=>1,
				],
				'Personas' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_personas'	=>true,
					'buscarPersonaAjax'	=>true,
				],
				'Accesos'=>[
					'historico_visitas_contratistas' => 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'horas_trabajadas' => 1,
					'horas_trabajadas_excel' => 1,
					'ajax_horas_trabajadas' => 1,
					'reporte_excel_accesos_empleados_horas' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1,
					'exportar_excel_historico_empleados' => 1
				],
				'Relojes' =>[
					'index'  => 1,
					'ajax_relojes' => 1,
					'alta'   => 1,
					'baja'	=> 1,
					'modificacion' => 1,
					'sincronizacion' => 1,
					'ajax_sincronizacion' => 1,
					'sincronizacion_marcaciones' => 1,
					'ajax_sincronizacion_marcacion' => 1,
					'api' => 1,
					'enrolador' => 1,
					'actualizar_templates'	=> 1,
					'historicoLogsPorNodo' => 1,
					'ajax_historicoLogsPorNodo' => 1,
					'listar_logs' => 1,
					'alta_daemon'   => 1,
					'recargar_daemon'   => 1,
					'accesos_restringidos' => 1,
					'accesos_restringidosAlta' => 1,
					'accesos_restringidosBaja' => 1,
					'ajax_accesos_restringidos' => 1,
					'ajax_buscarPersona' => 1,
				],
				'AlertaRelojes' =>[
					'index'  => 1,
					'ajax'	=> 1,
					'alta' => 1,
					'buscar_user' => 1,
					'baja' => 1

				],
				'Visitas' =>[
					'index'  => 1,
					'ajax' => 1,
					'alta'   => 1,
					'modificacion' => 1,
					'baja'	=> 1,
					'api' => 1,
					'enrolar' => 1,
					'actualizar_templates'	=> 1,
					'json_visita'        => 1,
					'enrolar'            => 1,
					'json_buscar_empleados' => 1,
					'enrolar'				=>1,
					'inactivos' 			=>1,
					'actualizar_ubicacion' => 1,
					'guardar_template_por_access_id' => 1,
					'buscar_template_por_access_id' => 1,
					'enviar_visita_a_enrolador' => 1,
					'reporte_excel_visitas' => 1,
					'buscar_user' 		 => 1

				],
				'Accesosbio' =>[
					'index'  => 1,
					'sincronizar' => 1,
					'ajax_accesosbio' => true,
				],
				'Usuarios' =>[
					'procesar' =>1,
					'index' =>1,
					'buscar_user' =>1,
					'buscar_documento' =>1,
					'alta'		=> 1,
					'modificar'	=> 1,
					'baja'		=> 1,
					'ajax_usuarios' => 1,
					'buscarAutorizanteAjax' =>true,
				],
				'Tarjetas' =>[
					'index' =>1,
					'crear' =>1,
					'alta' =>1,
					'editar' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'buscar_documento' =>1,
					'json_buscar_registro_visitas'=>1,
					'buscar_user' 		 => 1,
					'actualizar_tarjeta' => 1,
					'actualizar_tarjeta_desenrolar' => 1,
					'actualizar_listaTM' => 1,


				],
				'Base' =>[
					'manual' => 1
				],
			]
		],
		self::ENROLADOR => [
			'nombre'=> 'ENROLADOR',
			'padre' => self::ROL_DEFAULT,
			'atributos_visibles' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR],
			'atributos_select' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR],
			'inicio'=> ['control' => 'empleados','accion' => 'index'],
			'manual' => 'enrolador.html',
			'permisos'=>[
				'Empleados' =>[
					'index' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'enrolar'=>1,
					'buscar_template_por_access_id'=>1,
					'guardar_template_por_access_id'=>1,
					'distribuir_templates_de_empleado'=>1,
					'enviar_empleado_a_enrolador'=>1,
					'actualizar_ubicacion' => 1,
					'inactivos' =>1,
					'json_empleado' => 1,
					'reporte_excel_empleados'=>1
				],
				'Base' =>[
					'manual' => 1
				],
			]
		],
		self::ROL_DIS => [
			'nombre'=> 'ROL DIS',
			'padre' => self::ROL_DEFAULT,
			'roles_permitidos' => [self::ADMINISTRADOR_CIET,self::EMPLEADO_CIET,self::REGISTRO_DE_ACCESO, self::AUDITOR,self::ADMINISTRADOR_RRHH,self::ADMINISTRADOR_CONVENIOS,self::ROL_DIS,self::ENROLADOR,self::ENROLADOR_DIS,self::RCA],
			'atributos_select' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR],
			'inicio'=> ['control' => 'usuarios','accion' => 'index'],
			'manual' => 'rol_dis.html',
			'permisos'=>[
				'Relojes' =>[
					'index'  => 1,
					'ajax_relojes' => 1,
					'crear'  => 1,
					'alta'   => 1,
					'editar' => 1,
					'modificacion' => 1,
					'baja'	=> 1,
					'api' => 1,
					'enrolador' => 1,
					'actualizar_templates'	=> 1,
					'historicoLogsPorNodo' => 1,
					'ajax_historicoLogsPorNodo' => 1,
					'listar_logs' => 1,
          			'sincronizacion'	=> 1,
					'ajax_sincronizacion' => 1,
          			'sincronizacion_marcaciones' => 1,
					'ajax_sincronizacion_marcacion' => 1,
          			'alta_daemon'   => 1,
          			'recargar_daemon'   => 1,
					'accesos_restringidos' => 1,
					'accesos_restringidosAlta' => 1,
					'accesos_restringidosBaja' => 1,
					'ajax_accesos_restringidos' => 1,
					'ajax_buscarPersona' => 1,
				],
				'Accesos'=>[
					'historico_visitas_contratistas' => 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'exportar_excel_historico_empleados' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1
				],

				'AlertaRelojes' =>[
					'index'  => 1,
					'ajax'	=> 1,
					'alta' => 1,
					'buscar_user' => 1,
					'baja' => 1
				],
				'Usuarios' =>[
					'procesar' =>1,
					'buscar_user' =>1,
					'buscar_documento' =>1,
					'index'		=> 1,
					'alta'		=> 1,
					'modificar'	=> 1,
					'baja'		=> 1,
					'ajax_usuarios' => 1,
					'buscarAutorizanteAjax' =>true,
				],
				'Tarjetas' =>[
					'index' =>1,
					'crear' =>1,
					'alta' =>1,
					'editar' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'buscar_documento' =>1,
					'json_buscar_registro_visitas'=>1,
					'buscar_user' 		 => 1,
					'actualizar_tarjeta' => 1,
					'actualizar_tarjeta_desenrolar' => 1,
					'actualizar_listaTM' => 1,
				],
				'Base' =>[
					'manual' => 1
				],
			],
		],
		self::ADMINISTRADOR_CONVENIOS => [
			'nombre' => 'ADMINISTRADOR CONVENIOS',
			'padre' => self::ROL_DEFAULT,
			'atributos_visibles' => [Empleado::PRESTACION_SERVICIOS],
			'atributos_select' => [Empleado::PRESTACION_SERVICIOS],

			'atributos' => ['tipo_contrato' => [
                Empleado::PRESTACION_SERVICIOS	=> ['Empleados' =>['modificacion_contrato' =>1]],
                Empleado::SINEP					=> ['Empleados' =>['alta' =>0],'Acceso' =>['planilla_reloj' => 0]],
                Empleado::PERSONAL_EMBARCADO	=> ['Empleados' =>['alta' =>0]], 
                Empleado::EXTRAESCALAFONARIO	=> ['Empleados' =>['alta' =>0]], 
                Empleado::AUTORIDAD_SUPERIOR	=> ['Empleados' =>['alta' =>0]],
                Empleado::OTRA					=> ['Empleados' =>['alta' =>0]],
            ]],
			'inicio' => ['control' => 'acceso','accion' => 'historico_empleados'],
			'manual' => 'admin_convenios.html',
			'permisos'=>[
				'Accesos' =>[
					'planilla_reloj' => 1,
					'planilla_reloj_pdf' => 1,
					'calendario_planilla_reloj' => 1,
					'detalle_calendario_reloj'	=> 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'exportar_excel_historico_empleados' => 1,
				],
				'Empleados' =>[
					'alta' =>1,
					'crear' =>1,
					'baja' =>1,
					'baja_principal' =>1,
					'baja_directa' =>1,
					'buscar_user' =>1,
					'index' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' => 1,
					'modificacion' =>1,
					'editar' =>1,
					'json_buscar_registro_empleado' =>1,
					'json_buscar_empleado' =>1,
					'json_buscar_registro_autorizante' =>1,
					'buscar_autorizante' =>1,
					'empleado_horarios' => 1,
					'inactivos' =>1,
					'modificacion_contrato' => 1,
				],
				'Personas' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_personas'	=>true,
					'buscarPersonaAjax'	=>true,
				],
				'Novedades' =>[
					'index' => 1,
					'ajax_novedades'	=> 1,
					'exportar_excel'=> 1,
					'actualizarTipoNovedad' => 1,
					'alta' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'buscarEmpleado'=>1,
					'ajax_ultimas_siete' => 1,
					'exportar_ultimas_siete_excel' => 1
				],
				'Horarios' =>[
					'index' => 1,
					'crear' => 1,
					'alta' => 1,
					'editar' => 1,
					'modificacion' => 1,
					'baja' => 1

				],
				'Base' =>[
					'manual' => 1
				],
			]
		],
		self::ADMINISTRADOR_RRHH => [
			'nombre' => 'ADMINISTRADOR RRHH',
			'padre' => self::ROL_DEFAULT,
			'roles_permitidos' => [self::ADMINISTRADOR_CIET,self::EMPLEADO_CIET,self::REGISTRO_DE_ACCESO, self::AUDITOR,self::ADMINISTRADOR_RRHH,self::ADMINISTRADOR_CONVENIOS,self::ENROLADOR,self::RCA],
			'inicio' => ['control' => 'empleados','accion' => 'index'],
			'manual' => 'admin_rrhh.html',
			'atributos_visibles' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos_select' => [Empleado::SINEP, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos' => [
				'tipo_contrato' => [
					Empleado::PRESTACION_SERVICIOS => [
						'Empleados' =>['modificacion_contrato' =>1],
						'Acceso' =>['planilla_reloj' => 1],
						'Solicitudes'=>['detalle'=>1]
					],
				],
            ],
			'permisos'=>[
				'Accesos' =>[
					'planilla_reloj' => 1,
					'planilla_reloj_pdf' => 1,
					'calendario_planilla_reloj' => 1,
					'detalle_calendario_reloj'	=> 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'exportar_excel_historico_empleados' => 1
				],
				'Direcciones' =>[
					'index' => 1,
					'alta' =>1,
					'crear' =>1,
					'modificacion' =>1,
					'editar' =>1
				],
				'Empleados' =>[
					'alta' =>1,
					'crear' =>1,
					'baja' =>1,
					'buscar_user' =>1,
					'index' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'modificacion' =>1,
					'editar' =>1,
					'json_buscar_registro_empleado' =>1,
					'json_buscar_empleado' =>1,
					'json_buscar_registro_autorizante' =>1,
					'buscar_autorizante' =>1,
					'finalizar_contrato' => 1,
					'baja_principal' =>1,
					'modificacion_contrato' =>1,
					'empleado_horarios' => 1,
					'alta_empleado_horario' => 1,
					'inactivos' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' => 1,
					'actualizar_ubicacion'	=> 1,
					'buscarDatosEmpleadoAjax'	=>1,
                ],
				'Personas' =>[
					'index'=>1,
					'alta'=>1,
					'modificacion'=>1,
					'baja'=>1,
					'ajax_personas'	=>1,
					'buscarPersonaAjax'	=>1,
				],
				'Usuarios' =>[
					'procesar' =>1,
					'buscar_user' =>1,
					'buscar_documento' =>1,
					'index'		=> 1,
					'alta'		=> 1,
					'modificar'	=> 1,
					'baja'		=> 1,
					'ajax_usuarios' => 1,
					'buscarAutorizanteAjax' =>true,
				],
				'Novedades' =>[
					'index' => 1,
					'ajax_novedades'	=> 1,
					'exportar_excel'=> 1,
					'actualizarTipoNovedad' => 1,
					'alta' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'buscarEmpleado'=>1,
					'ajax_ultimas_siete' => 1,
					'exportar_ultimas_siete_excel' => 1
				],
				'Ubicaciones' => [
					'crear' =>1,
					'alta' =>1,
					'baja' =>1,
					'modificacion' =>1,
					'editar' =>1,
					'index' =>1
				],
				'Horarios' =>[
					'index' =>1,
					'crear' =>1,
					'alta' =>1,
					'editar' =>1,
					'modificacion' =>1,
					'baja' =>1,
					'desvios_horarios' => 1,
					'json_desvios_horarios' => 1,
					'carga_masiva' => 1		
				],
				'Solicitudes' => [
					'index' => 1,
					'detalle' => 1,
					'comenzar' => 1,
					'resuelto' => 1,
					'notificacion' => 1,
					'comentario' => 1,
					'alta_comentario' => 1
				],
				'Registros' =>[
					'index' =>1,
					'carga_individual' =>1,
					'buscar_empleado' =>1,
					'buscar_persona' =>1,
					'registro_manual_empleado'=>1
				],
				'Reportes' => [
					'ausentismoOnline' => 1,
					'obtenerDatos' => 1,
					'direcciones' => 1
				],
				'Informes'	=> [
					'index'					=> 1,
					'alta'					=> 1,
					'crear'					=> 1,
					'json_buscar_empleado'	=> 1,
					'baja'					=> 1,
					'modificacion'			=> 1,
					'envio_manual'			=> 1
				],
				'Base' =>[
					'manual' => 1
				],
			]
		],
		self::AUDITOR => [
			'nombre' => 'AUDITOR',
			'padre' => self::ROL_DEFAULT,
			'inicio' => ['control' => 'acceso','accion' => 'historico_empleados'],
			'manual' => 'instructivo_AUDITOR.html',
			'atributos_select' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR],
			'permisos'=>[
				'Accesos' =>[
					'historico_visitas_contratistas' => 1,
					'planilla_reloj' => 1,
					'planilla_reloj_pdf' => 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'exportar_excel_historico_empleados' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1
				],
				'Base' =>[
					'manual' => 1
				]
			]
		],
		self::REGISTRO_DE_ACCESO => [
			'nombre'=> 'REGISTRO DE ACCESO',
			'padre' => self::ROL_DEFAULT,
			'inicio'=> ['control' => 'accesos','accion' => 'index'],
			'manual' => 'registro_acceso.html',
			'permisos'=>[
				'Accesos' =>[
					'buscar_documento' =>1,
					'buscar_autorizante' => 1,
					'index' =>1,
					'alta' => 1,
					'baja' => 1,
					'ajax_accesos' => 1,
					'verificar_cambios_de_accesos' => 1,
					'editar_observaciones' => 1,
					'cambiar_ubicacion' =>1

				],
				'Base' =>[
					'manual' => 1,
					'definirUbicacion' =>1
				],
				'Empleados' => [
					'json_buscar_empleado' => 1,
				]
			]
		],
		self::ADMINISTRADOR_CIET => [
			'nombre'=> 'ADMINISTRADOR CIET',
			'padre' => self::ROL_DEFAULT,
			'roles_permitidos' => [self::ADMINISTRADOR_CIET,self::EMPLEADO_CIET,self::REGISTRO_DE_ACCESO,self::ROL_DEFAULT],
			'atributos_visibles' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos_select' =>[Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
            'atributos' => [
                'situaciones_revista' => [
                    SituacionRevista::SINEP_PLANTA_PERMANENTE => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_LEY_MARCO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_DESIGNACION_TRANSITORIA_EN_CARGO_DE_PLANTA_PERMANENTE_CON_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_1109_17 => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_1109_17_CON_FINANCIMIENTO_EXTERNO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_ASISTENCIA_TECNICA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PERSONAL_EMBARCADO_CLM => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PERSONAL_EMBARCADO_PLANTA_PERMANENTE => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_COMISION_SERVICIOS => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_PLANTA_PERMANENTE_CON_DESIGNACION_TRANSITORIA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_GABINETE_DE_ASESORES => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::AUTORIDAD_SUPERIOR_AUTORIDAD_SUPERIOR => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::EXTRAESCALAFONARIO_EXTRAESCALAFONARIO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA_CON_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_ADSCRIPCION => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_DESIGNACION_TRANSITORIA_SIN_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_HORAS_CATEDRA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_OTRAS_MODALIDADES => [
                        'Empleados' => [
                            'modificacion_contrato' => 1,
                            'modificacion' => 1,
                            'baja' => 1,
                            'baja_principal' => 1,
						],
					],
                ],
                'campos' => ['observacion' => self::EDITAR],
            ],
			'inicio'=> ['control' => 'usuarios','accion' => 'index'],
			'manual' => 'admin_ciet.html',
			'permisos'=>[
				'Accesos' =>[
					'historico_visitas_contratistas' => 1,
					'ajax_historico_empleados' => 1,
					'historico_empleados' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'exportar_excel_historico_empleados' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1,
					'horas_trabajadas' => 1,
					'horas_trabajadas_excel' => 1,
					'ajax_horas_trabajadas' => 1,
					'reporte_excel_accesos_empleados_horas' => 1,
					'mis_horarios' => 1,
					'ajax_mis_horarios' =>1

				],
				'Relojes' =>[
					'index'  => 1,
					'ajax_relojes' => 1,
					'enrolador'  => 1,
					'alta_daemon'   => 1,
					'recargar_daemon'   => 1,
					'accesos_restringidos' => 1,
					'accesos_restringidosAlta' => 1,
					'accesos_restringidosBaja' => 1,
					'ajax_accesos_restringidos' => 1,
					'ajax_buscarPersona' => 1,
				],
				'Advertencias' =>[
					'index' =>1,
					'alta' => 1,
					'modificacion' =>1,
					'baja' => 1,
					'buscar_documento' => 1,
					'buscar_documento_solicitante' => 1,
					'remover_solicitante' => 1,
					'ajax_advertencias' => 1,
					'buscarPersonalAjax' => 1,
					'buscarSolicitanteAjax' => 1,

				],
				'Advertenciasgenericas' => [
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_advertenciasgenericas'=>true,

				],
				'Contratistaspersonal' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_contratistas_personal' =>true,
					'ubicaciones' =>true,
					'ajax_contratistas_ubicaciones' =>true,
					'buscarPersonalAjax' =>true,
					'buscarAutorizanteAjax' =>true,
					'ubicacion_baja' =>true,
					'ubicacion_editar' =>true,
				],
				'Contratistas' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_contratistas'	=>true,
					'buscar_contratista'	=>true,
					'ajax_localidades' => true,
				],
				'Empleados' =>[
					'index' =>1,
					'ajax_empleados'	=>1,
					'exportar_excel_empleados' =>1,
					'json_empleado' =>1,
					'reporte_excel_empleados' => 1,
					'json_buscar_registro_empleado' =>1,
					'json_buscar_empleado' =>1,
					'json_buscar_registro_autorizante' =>1,
					'buscar_autorizante' =>1,
					'enrolar'=>1,
					'buscar_template_por_access_id'=>1,
					'guardar_template_por_access_id'=>1,
					'distribuir_templates_de_empleado'=>1,
					'enviar_empleado_a_enrolador'=>1,
					'actualizar_ubicacion' => 1,
                    'alta' => 1,
                    'editar' => 1,
                    'modificacion' => 1,
                    'modificacion_contrato' => 1,
                    'inactivos' => 1,
                    'crear' =>1,
                    'buscar_user' =>1,
                    'baja' =>1,
					'baja_principal' =>1,	
					'finalizar_contrato' =>1,
					'buscarDatosEmpleadoAjax'	=>1,	
				],
				'Personas' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_personas'	=>true,
					'buscarPersonaAjax'	=>true,
				],
				'Registros' =>[
					'index' =>1,
					'carga_individual' =>1,
					'carga_individual_contratista' =>1,
					'carga_individual_visita' =>1,
					'accesos_sin_cierre' =>1,
					'buscar_empleado' =>1,
					'buscar_persona' =>1,
					'buscar_contratista' =>1,
					'buscar_visita' =>1,
					'solicitar_cierre' =>1,
					'cerrar_acceso' =>1,
					'ajax_sin_cierre' =>1
				],
				'Usuarios' =>[
					'procesar' =>1,
					'index' =>1,
					'buscar_user' =>1,
					'buscar_documento' =>1,
					'alta'		=> 1,
					'modificar'	=> 1,
					'baja'		=> 1,
					'ajax_usuarios' => 1,
					'buscarAutorizanteAjax' =>true,
				],
				'Base' =>[
					'manual' => 1
				],
				'Pertenencias' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_pertenencias'	=>true,
					'buscar_documento'	=>true,
					'buscar_documento_solicitante'	=>true,
				],
				'Visitas' =>[
					'index'  => 1,
					'ajax' => 1,
					'alta'   => 1,
					'modificacion' => 1,
					'baja'	=> 1,
					'api' => 1,
					'enrolar' => 1,
					'actualizar_templates'	=> 1,
					'json_visita'        => 1,
					'enrolar'            => 1,
					'json_buscar_empleados' => 1,
					'inactivos' 			=>1,
					'actualizar_ubicacion' => 1,
					'guardar_template_por_access_id' => 1,
					'buscar_template_por_access_id' => 1,
					'enviar_visita_a_enrolador' => 1,
					'reporte_excel_visitas' => 1,
					'buscar_user' 		 => 1
				],
				'Tarjetas' =>[
				'index' =>1,
				'crear' =>1,
				'alta' =>1,
				'editar' =>1,
				'modificacion' =>1,
				'baja' =>1,
				'buscar_documento' =>1,
				'json_buscar_registro_visitas'=>1,
				'buscar_user' 		 => 1,
				'actualizar_tarjeta' => 1,
				'actualizar_tarjeta_desenrolar' => 1,
				'actualizar_listaTM' => 1,
			],
			],
		],
		self::EMPLEADO_CIET => [
			'nombre'=> 'EMPLEADO CIET',
			'padre' => self::ROL_DEFAULT,
			'roles_permitidos' => [self::EMPLEADO_CIET,self::REGISTRO_DE_ACCESO],
			'atributos_visibles' => [Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
			'atributos_select' =>[Empleado::SINEP, Empleado::PRESTACION_SERVICIOS, Empleado::PERSONAL_EMBARCADO, Empleado::EXTRAESCALAFONARIO, Empleado::AUTORIDAD_SUPERIOR, Empleado::OTRA],
            'atributos' => [
                'situaciones_revista' => [
                    SituacionRevista::SINEP_PLANTA_PERMANENTE => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_LEY_MARCO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_DESIGNACION_TRANSITORIA_EN_CARGO_DE_PLANTA_PERMANENTE_CON_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_1109_17 => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_1109_17_CON_FINANCIMIENTO_EXTERNO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PRESTACION_DE_SERVICIOS_ASISTENCIA_TECNICA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PERSONAL_EMBARCADO_CLM => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::PERSONAL_EMBARCADO_PLANTA_PERMANENTE => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_COMISION_SERVICIOS => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_PLANTA_PERMANENTE_CON_DESIGNACION_TRANSITORIA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_GABINETE_DE_ASESORES => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::AUTORIDAD_SUPERIOR_AUTORIDAD_SUPERIOR => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::EXTRAESCALAFONARIO_EXTRAESCALAFONARIO => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_PLANTA_PERMANENTE_MTR_CON_DESIGNACION_TRANSITORIA_CON_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_ADSCRIPCION => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::SINEP_DESIGNACION_TRANSITORIA_SIN_FUNCION_EJECUTIVA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_HORAS_CATEDRA => ['Empleados' => ['modificacion_contrato' => 1,'modificacion' => 1,'baja' => 1,],],
                    SituacionRevista::OTRA_OTRAS_MODALIDADES => [
                        'Empleados' => [
                            'modificacion_contrato' => 1,
                            'modificacion' => 1,
                            'baja' => 1,
                            'baja_principal' => 1,
						],
					],
                ],
                'campos' => ['observacion' => self::EDITAR],
            ],
			'inicio'=> ['control' => 'Acceso','accion' => 'historico_visitas_contratistas'],
			'manual' => 'instructivo_ADMIN.html',
			'permisos'=>[
				'Accesos' =>[
					'historico_visitas_contratistas' => 1,
					'ajax_historico_visitas_contratistas' => 1,
					'exportar_historico_visitas_contratistas_csv' => 1,
					'mis_horarios' => 1,
					'ajax_mis_horarios' =>1

				],
				'Pertenencias' =>[
					'index'=>true,
					'alta'=>true,
					'modificacion'=>true,
					'baja'=>true,
					'ajax_pertenencias'	=>true,
					'buscar_documento'	=>true,
					'buscar_documento_solicitante'	=>true,
				],
				'Base' =>[
					'manual' => 1
				],
				
			],
			
		],
		self::ROL_DEFAULT =>[
			'nombre'=> 'MIS HORARIOS',
			'inicio'=> ['control' => 'acceso','accion' => 'mis_horarios'],
			'atributos' => [
				'tipo_contrato' => [
					\App\Modelo\Empleado::PRESTACION_SERVICIOS => [
						'Solicitudes' =>[
							'crear' => 0,
							'mis_solicitudes' => 0,
							'alta' => 0,
							'comenzar' => 0,
							'detalle' => 0,
						],
						'Accesos' => [
							'mis_horarios' => 1
						]
					],
					\App\Modelo\Empleado::SIN_CONTRATO => [
						'Solicitudes' => [
							'mis_solicitudes' => 0
						]
					]
				]
			],
			'permisos'=>[
				'Accesos' =>[
					'mis_horarios' =>1,
					'ajax_mis_horarios' =>1
				],
				'Informes' => [
					'mis_consultas' => 1
				],
				'Solicitudes' =>[
					'mis_solicitudes' =>1,
					'crear' =>1,
					'alta' =>1,
					'detalle' =>1
				],
			]
		]
	];

	public static function sin_permisos($accion){
		$vista = include (VISTAS_PATH.'/widgets/acceso_denegado_accion.php');
		return $vista;
	}

    public static function obtener_rol() {
    	return static::$rol;
    }

    public static function obtener_inicio() {
    	static::$rol = Usuarios::$usuarioLogueado['permiso'];
		static::$rol = (is_null(static::$rol))? 0 : static::$rol ;
    	$inicio = static::$permisos[static::$rol]['inicio'];
    	return $inicio;
    }

    public static function obtener_nombre_rol() {
    	$nombre = static::$permisos[static::$rol]['nombre'];
    	return $nombre;
    }

 	public static function obtener_manual() {
    	$manual = static::$permisos[static::$rol]['manual'];
    	return $manual;
    }

 	public static function obtener_atributos_visibles() {
		$atributo_visible = static::$permisos[static::$rol]['atributos_visibles'];
    		return $atributo_visible;
    }

    public static function obtener_atributos_select() {
		$atributos_select = static::$permisos[static::$rol]['atributos_select'];
    		return $atributos_select;
    }

    public static function puede_atributo($cont, $accion, $atributo, $id_atributo) {
		$flag = true;
		$rol = static::$rol;

	    while ($flag) {
		    if (isset(static::$permisos[$rol]['atributos'][$atributo][$id_atributo])) {
                if(isset(static::$permisos[$rol]['atributos'][$atributo][$id_atributo][$cont][$accion])) {
                    $puede = static::$permisos[$rol]['atributos'][$atributo][$id_atributo][$cont][$accion];
		            $flag = false;
		        }
		    }

		    if ($flag && isset(static::$permisos[$rol]['padre'])) {
                $rol = static::$permisos[$rol]['padre'];
            } else {
                $flag = false;
            }
        }

	    if (!isset($puede)) {
	        $puede = static::puede($cont, $accion);
	    }

	    return $puede;
	}

    public static function puede($cont, $accion) {
		$rol =  Usuarios::$usuarioLogueado['permiso'];

		if($rol) {
			$puede = parent::puede($cont, $accion);
		} else {
			$rol = 0;
			$puede = false;
            if (isset(static::$permisos[$rol]['permisos'][$cont][$accion])) {
                    $puede = static::$permisos[$rol]['permisos'][$cont][$accion];
			}
		}
		return $puede;
	}

/**
 * Se usa para consultar si un usuario logueado tiene permisos sobre el rol de otro.
 *
 * @param int $rol_externo El rol de un usuario distinto al logueado
 * @return boolean
*/
	public static function tiene_permiso_sobre($rol_externo=null){
		return in_array($rol_externo, (array)static::$permisos[static::$rol]['roles_permitidos']);
	}

	public static function obtener_listado() {
		$roles_permitidos = static::$permisos[static::$rol]['roles_permitidos'];
		$permisos = static::$permisos;
		foreach ($permisos as $key => $permiso) {
			if(!in_array( $key, $roles_permitidos )){
				unset($permisos[$key]);
			}
		}

		return $permisos;
	}

	public static function tiene_dependencias($rol) {
    	$rta = (isset(static::$permisos[$rol]['tiene_dependencias'])) ? true : false;
    	return $rta;
    }

    public static function tiene_permiso_situaciones_revista($situacion_revista_id = null){
        return key_exists($situacion_revista_id , (array)static::$permisos[static::$rol]['atributos']['situaciones_revista']);
    }

    public static function obtener_situaciones_revista_permitidas($rol = null){
        return
            !is_null($rol)
            && isset(static::$permisos[static::$rol]['atributos'])
            && isset(static::$permisos[static::$rol]['atributos']['situaciones_revista'])?
            array_keys(static::$permisos[static::$rol]['atributos']['situaciones_revista']) : [];
    }

    public static function puede_campo($campo = null){
	    return
        isset(static::$permisos[static::$rol]['atributos'])
            && isset(static::$permisos[static::$rol]['atributos']['campos'])
            && !is_null($campo) && isset(static::$permisos[static::$rol]['atributos']['campos'][$campo]) ?
                static::$permisos[static::$rol]['atributos']['campos'][$campo] : self::NO_ACCEDE;
    }

		public static function obtener_lista_roles_permitidos() {
			$flag = true;
			$rol = static::$rol;
			$roles = [];
				 if (isset(static::$permisos[$rol]['roles_permitidos'])) {
				foreach( static::$permisos[$rol]['roles_permitidos'] as $id) {
					$roles[$id] = static::$permisos[$id]['nombre'];
				}
			}
					return $roles;
		}
}