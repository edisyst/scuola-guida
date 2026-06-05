<?php

return [
    // Estados de inscripción
    'reg_approved'          => 'Estás autorizado para inscribirte en los <strong>exámenes oficiales</strong> de conducción.',
    'reg_approved_on'       => 'Aprobado el :date',
    'reg_approved_by'       => 'por :name',
    'reg_resubmit_warn'     => 'Si modificas tus datos personales y los reenvías, deberás ser reautorizado por el administrador antes de participar en nuevos exámenes.',
    'reg_pending'           => 'Tu solicitud está <strong>pendiente de aprobación</strong>.',
    'reg_pending_sent'      => 'Enviada el :date',
    'reg_pending_practice'  => 'Puedes seguir <strong>practicando libremente con los quizzes</strong> mientras esperas la revisión.',
    'reg_rejected'          => 'Tu solicitud ha sido <strong>rechazada</strong>.',
    'reg_rejected_reason'   => 'Motivo:',
    'reg_rejected_fix'      => 'Corrige los datos y vuelve a enviar la solicitud.',
    'reg_none'              => 'Para inscribirte en los <strong>exámenes oficiales</strong> de conducción primero debes enviar tus datos personales y esperar la aprobación del administrador.',
    'reg_practice_meanwhile'=> 'Mientras tanto, siempre puedes <strong>practicar con los quizzes</strong> libremente.',

    // Campos del formulario
    'field_first_name'      => 'Nombre',
    'field_last_name'       => 'Apellidos',
    'field_address'         => 'Dirección de residencia',
    'field_address_ph'      => 'Calle, número, ciudad, código postal',
    'field_birth_date'      => 'Fecha de nacimiento',
    'field_birth_place'     => 'Lugar de nacimiento',
    'field_fiscal_code'     => 'Código fiscal / NIF',
    'field_document'        => 'Documento de identidad',
    'document_uploaded'     => 'Documento cargado',
    'document_replace'      => 'sube un nuevo archivo solo si quieres reemplazarlo',
    'document_formats'      => 'Formatos admitidos: PDF, JPG, PNG. Tamaño máximo: 5 MB.',

    // Botones submit
    'submit_first'          => 'Enviar solicitud de inscripción',
    'submit_update_pending' => 'Actualizar solicitud pendiente',
    'submit_rejected'       => 'Reenviar solicitud',
    'submit_reapprove'      => 'Reenviar datos (requiere nueva aprobación)',

    // Confirm dialogs
    'confirm_first_send'    => '¿Confirmas el envío de tus datos para la inscripción en los exámenes oficiales?',
    'confirm_reapprove'     => 'Al reenviar los datos perderás temporalmente la autorización para exámenes hasta la reaprobación del administrador. ¿Proceder?',

    // TTS / Accesibilidad
    'tts_title'             => 'Accesibilidad',
    'tts_enabled_label'     => 'Activar síntesis de voz (TTS)',
    'tts_autoplay_label'    => 'Reproducción automática de voz',

    // Página de perfil — secciones y títulos
    'page_title'            => 'Perfil',
    'account_subtitle'      => 'Cuenta',
    'my_profile'            => 'Mi perfil',
    'info_section'          => 'Información del perfil',
    'reg_section'           => 'Inscripción en exámenes oficiales',
    'password_section'      => 'Actualizar contraseña',
    'twofa_section'         => 'Autenticación de dos factores',
    'tts_desc'              => 'Activa la lectura automática de las preguntas para replicar el apoyo DSA requerido por el examen ministerial (D.Lgs. 62/2017).',
    'tts_field_label'       => 'Lectura de audio de las preguntas',
    'tts_autoplay_field_label' => 'Inicio automático en cada pregunta',
    'save_prefs'            => 'Guardar preferencias',
    'push_section'          => 'Notificaciones push',
    'push_active'           => 'Activas',
    'push_inactive'         => 'No activas',
    'push_desc'             => 'Recibe notificaciones nativas incluso con la app cerrada (insignias ganadas, aprobación de inscripción, recordatorios de repaso SM-2).',
    'push_not_supported'    => 'Tu navegador no admite notificaciones push o el sitio no se sirve a través de HTTPS.',
    'push_subscribe'        => 'Activar notificaciones push',
    'push_subscribing'      => 'Activando…',
    'push_unsubscribe'      => 'Desactivar notificaciones push',
    'push_unsubscribing'    => 'Desactivando…',
    'push_permission_denied'=> 'Permiso denegado. Activa las notificaciones en la configuración de tu navegador.',
    'push_activate_error'   => 'Error durante la activación: ',
    'push_deactivate_error' => 'Error durante la desactivación: ',
    'gdpr_section'          => 'Portabilidad de datos',
    'gdpr_desc'             => 'Descarga un archivo ZIP con todos tus datos personales en formato JSON (RGPD art. 20 — derecho a la portabilidad). El archivo incluye quizzes, favoritos, insignias, actividad y, si está cargado, tu documento de identidad.',
    'gdpr_download'         => 'Descargar mis datos',
    'delete_section'        => 'Eliminar cuenta',
    'profile_updated'       => 'Perfil actualizado con éxito.',
    'password_updated'      => 'Contraseña actualizada con éxito.',

    // Formulario de información del perfil
    'name_label'          => 'Nombre',
    'email_label'         => 'Correo electrónico',
    'email_unverified'    => 'Tu dirección de correo electrónico no está verificada.',
    'send_verification'   => 'Haz clic aquí para reenviar el correo de verificación.',
    'verification_sent'   => 'Se ha enviado un nuevo enlace de verificación a tu dirección de correo.',

    // Formulario de contraseña
    'current_password'    => 'Contraseña actual',
    'new_password'        => 'Nueva contraseña',
    'confirm_password'    => 'Confirmar contraseña',
    'update_password_btn' => 'Actualizar contraseña',

    // Eliminar cuenta
    'delete_account'             => 'Eliminar cuenta',
    'delete_account_desc'        => 'Una vez eliminada tu cuenta, todos sus recursos y datos serán eliminados permanentemente. Antes de continuar, descarga los datos que desees conservar.',
    'delete_account_confirm_title' => '¿Seguro que deseas eliminar tu cuenta?',
    'delete_account_confirm_desc'  => 'Una vez eliminada tu cuenta, todos sus recursos y datos serán eliminados permanentemente. Introduce tu contraseña para confirmar la eliminación.',

    // 2FA
    'twofa_active_since'      => '2FA activo desde :date.',
    'twofa_disable_btn'       => 'Desactivar 2FA',
    'twofa_regenerate_btn'    => 'Regenerar códigos de recuperación',
    'twofa_disable_title'     => 'Desactivar autenticación de dos factores',
    'twofa_disable_desc'      => 'Introduce tu contraseña actual para confirmar la desactivación del 2FA.',
    'twofa_regen_title'       => 'Regenerar códigos de recuperación',
    'twofa_regen_desc'        => 'Los códigos actuales quedarán invalidados. Introduce tu contraseña para continuar.',
    'twofa_regen_btn'         => 'Regenerar códigos',
    'twofa_not_enabled'       => 'El 2FA aún no está activado en tu cuenta. Es obligatorio para acceder al área de administración.',
    'twofa_enable_btn'        => 'Activar 2FA',
    'twofa_platform_disabled' => 'El 2FA está actualmente desactivado en la plataforma.',

    // Insignia de estado de inscripción
    'status_approved_badge' => 'Aprobada',
    'status_pending_badge'  => 'Pendiente de aprobación',
    'status_rejected_badge' => 'Rechazada',
    'status_none_badge'     => 'Sin presentar',
];
