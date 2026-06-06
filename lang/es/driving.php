<?php

return [

    // Títulos de sección
    'title_modules'         => 'Módulos de prácticas de conducción',
    'title_sessions'        => 'Sesiones registradas',
    'title_progress'        => 'Progreso en prácticas de conducción',

    // Tarjeta instructor
    'card_title'            => 'Prácticas de conducción',

    // Avisos
    'theory_warning'        => 'El alumno todavía no ha superado el examen teórico. Las prácticas de conducción aún no están disponibles.',
    'no_license_type'       => 'Ningún tipo de permiso asociado al alumno. Contacta con un administrador.',

    // Módulos — columnas tabla
    'col_code'              => 'Código',
    'col_name'              => 'Nombre',
    'col_license_type'      => 'Tipo de permiso',
    'col_required_hours'    => 'Horas requeridas',
    'col_sessions'          => 'N° sesiones',
    'col_actions'           => 'Acciones',

    // Módulos — campos formulario
    'field_license_type'    => 'Tipo de permiso',
    'field_code'            => 'Código (máx. 5 caracteres)',
    'field_name'            => 'Nombre del módulo',
    'field_description'     => 'Descripción',
    'field_required_hours'  => 'Horas requeridas',
    'field_sort_order'      => 'Orden',

    // Módulos — acciones
    'btn_new_module'        => 'Nuevo módulo',
    'btn_save'              => 'Guardar',
    'btn_cancel'            => 'Cancelar',
    'btn_edit'              => 'Editar',
    'btn_delete'            => 'Eliminar',

    // Módulos — estado vacío
    'modules_empty'         => 'No hay módulos configurados para este tipo de permiso.',
    'modules_empty_hint'    => 'Crea el primer módulo para empezar a estructurar las prácticas de conducción.',

    // Módulos — confirmaciones
    'module_delete_confirm' => '¿Seguro que deseas eliminar este módulo? Se eliminarán todas las sesiones asociadas.',

    // Módulos — filtro
    'filter_all_types'      => 'Todos los tipos',
    'filter_label'          => 'Filtrar por tipo de permiso',

    // Módulos — títulos de página
    'create_title'          => 'Nuevo módulo de prácticas',
    'edit_title'            => 'Editar módulo',

    // Sesiones — campos formulario
    'field_module'          => 'Módulo',
    'field_conducted_at'    => 'Fecha de sesión',
    'field_duration'        => 'Duración (min)',
    'field_notes'           => 'Notas',

    // Sesiones — acciones
    'register_session'      => 'Registrar sesión',
    'session_delete_confirm'=> '¿Eliminar esta sesión?',

    // Sesiones — columnas
    'session_date'          => 'Fecha',
    'session_module'        => 'Módulo',
    'session_duration'      => 'Duración',
    'session_notes'         => 'Notas',

    // Sesiones — estado
    'session_none'          => 'No hay sesiones registradas.',

    // Progreso
    'progress_title'        => 'Completado',
    'progress_completed'    => 'Completado',
    'progress_sessions'     => 'sesiones',
    'progress_empty'        => 'No hay módulos disponibles. Contacta con tu instructor o la autoescuela.',
    'progress_all_done'     => '¡Enhorabuena! Has completado todas las horas de prácticas de conducción requeridas.',
    'progress_hours'        => ':completed / :required horas',
    'progress_pct'          => ':pct% completado',

    // Errores de servicio
    'module_has_sessions'   => 'No se puede eliminar el módulo: existen sesiones registradas. Elimina primero las sesiones.',

    // Títulos alternativos
    'title_create'          => 'Nuevo módulo',
    'title_edit'            => 'Editar módulo',

];
