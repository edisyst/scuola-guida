<?php

return [
    'page_title'                => 'Feature Toggle',
    'section_platform'          => 'Gestionadas por la plataforma',
    'section_platform_desc'     => 'Activa o desactiva funciones en caliente. El cambio es inmediato y reversible.',
    'section_config'            => 'Gestionadas por configuración',
    'section_config_desc'       => 'Estos indicadores están controlados por variables de entorno o archivos de configuración. No los modifiques desde aquí.',
    'flag'                      => 'Indicador',
    'current_value'             => 'Valor actual',
    'hint'                      => 'Cómo modificar',
    'enabled'                   => 'Activo',
    'disabled'                  => 'Inactivo',
    'toggled_on'                => 'Función activada.',
    'toggled_off'               => 'Función desactivada.',

    // Etiquetas de los toggles gestionados por DB
    'gamification_enabled'       => 'Gamificación (insignias y racha)',
    'gamification_enabled_desc'  => 'Muestra u oculta insignias, racha y la sección de progreso en el panel.',
    'web_push_enabled'           => 'Notificaciones Web Push',
    'web_push_enabled_desc'      => 'Habilita la suscripción push y el envío de recordatorios de repaso SM-2.',
    'guest_homepage_enabled'     => 'Página de inicio pública',
    'guest_homepage_enabled_desc'=> 'Si está desactivada, la raíz "/" redirige directamente al inicio de sesión.',
    'exam_translations_enabled'  => 'Selector de idioma de interfaz',
    'exam_translations_enabled_desc' => 'Muestra u oculta el menú de selección de idioma en la interfaz.',
    'driving_practice_enabled'   => 'Módulo de prácticas de conducción',
    'driving_practice_enabled_desc' => 'Habilita la gestión de módulos y sesiones de prácticas (Feature 9.x).',
    'eu_categories_visible'      => 'Categorías UE en estudio',
    'eu_categories_visible_desc' => 'Muestra u oculta las categorías de directiva UE en las pantallas de estudio.',
    'study_content_enabled'      => 'Contenidos formativos (StudyContent)',
    'study_content_enabled_desc' => 'Habilita el visor de contenidos formativos vinculados a categorías y módulos.',

    // Sugerencias para los flags gestionados por configuración
    'hint_two_factor'  => 'Seguridad: solo editable desde <code>.env</code> (clave <code>TWO_FACTOR_ENABLED</code>). Cambia el valor y ejecuta <code>php artisan config:clear</code>.',
    'hint_messaging'   => 'Requiere credenciales Twilio válidas. Configura en <code>.env</code> (clave <code>MESSAGING_ENABLED</code>) y limpia la config.',
    'hint_cache'       => 'Interruptor maestro de caché. Desactivar solo para depuración; requiere <code>config:clear</code>. Clave <code>.env</code>: <code>CACHE_ENABLED</code>.',
    'hint_debug'       => 'Nunca <code>true</code> en producción. Solo editable desde <code>.env</code> (clave <code>APP_DEBUG</code>).',
    'hint_queue'       => 'Cambiar el backend de colas requiere reiniciar los workers. Clave <code>.env</code>: <code>QUEUE_CONNECTION</code>.',
    'hint_session'     => 'Cambiar el driver de sesión desconecta a los usuarios activos. Clave <code>.env</code>: <code>SESSION_DRIVER</code>.',
    'hint_simulator_questions' => 'Número de preguntas por examen. Editable en <code>config/simulator.php</code>. Predeterminado: 30.',
    'hint_simulator_time_limit' => 'Límite de tiempo en minutos para el examen. Editable en <code>config/simulator.php</code>. Predeterminado: 20.',
    'hint_simulator_max_errors' => 'Errores máximos permitidos. Editable en <code>config/simulator.php</code>. Predeterminado: 3.',
];
