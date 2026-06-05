<?php

return [
    // Registro aprobado
    'reg_approved_subject'    => 'Inscripción de datos personales aprobada',
    'reg_approved_mail_title' => 'Inscripción aprobada',
    'reg_approved_mail_body'  => 'tu inscripción de datos personales ha sido **aprobada** por el administrador. Ahora puedes solicitar inscripción en los quizzes oficiales de conducción.',
    'reg_approved_mail_cta'   => 'Ir al catálogo de quizzes',
    'reg_approved_mail_closing' => '¡Mucha suerte en los exámenes!',
    'reg_approved_db_title'   => 'Inscripción aprobada',
    'reg_approved_db_body'    => 'Tu inscripción de datos personales ha sido aprobada: ya puedes inscribirte en los exámenes oficiales.',
    'reg_approved_push_title' => 'Inscripción aprobada',
    'reg_approved_push_body'  => 'Tu inscripción ha sido aprobada: ya puedes inscribirte en los exámenes oficiales.',
    'reg_approved_push_action'=> 'Abrir panel',

    // Registro rechazado
    'reg_rejected_subject'    => 'Inscripción de datos personales no aprobada',
    'reg_rejected_mail_title' => 'Inscripción no aprobada',
    'reg_rejected_mail_body'  => 'tu solicitud de inscripción de datos personales no fue aprobada. Revisa el motivo en tu área personal y envía una nueva solicitud con los datos correctos.',
    'reg_rejected_mail_cta'   => 'Ir al perfil',
    'reg_rejected_db_title'   => 'Inscripción rechazada',
    'reg_rejected_db_body'    => 'Tu solicitud de inscripción de datos personales no fue aprobada. Revisa los motivos en tu área personal.',

    // Inscripción al quiz aprobada
    'enrollment_approved_subject'    => 'Inscripción al quiz aprobada',
    'enrollment_approved_mail_title' => 'Inscripción al quiz aprobada',
    'enrollment_approved_mail_body'  => 'tu inscripción al quiz **:title** ha sido **aprobada**. Ya puedes realizar el quiz desde el área de inscripciones.',
    'enrollment_approved_mail_cta'   => 'Ir a inscripciones',
    'enrollment_approved_db_title'   => 'Inscripción al quiz aprobada',
    'enrollment_approved_db_body'    => 'Tu inscripción al quiz ":title" ha sido aprobada.',

    // Inscripción al quiz rechazada
    'enrollment_rejected_subject'    => 'Inscripción al quiz no aprobada',
    'enrollment_rejected_mail_title' => 'Inscripción al quiz rechazada',
    'enrollment_rejected_mail_body'  => 'tu solicitud de inscripción al quiz **:title** no fue aprobada.',
    'enrollment_rejected_mail_cta'   => 'Ir a inscripciones',
    'enrollment_rejected_db_title'   => 'Inscripción al quiz rechazada',
    'enrollment_rejected_db_body'    => 'Tu solicitud de inscripción al quiz ":title" no fue aprobada.',

    // Inscripción al quiz reabierta
    'enrollment_reopened_subject'    => 'Inscripción al quiz reabierta',
    'enrollment_reopened_mail_title' => 'Inscripción al quiz reabierta',
    'enrollment_reopened_mail_body'  => 'tu inscripción al quiz **:title** ha sido reabierta.',
    'enrollment_reopened_mail_cta'   => 'Ir a inscripciones',
    'enrollment_reopened_db_title'   => 'Inscripción al quiz reabierta',
    'enrollment_reopened_db_body'    => 'Tu inscripción al quiz ":title" ha sido reabierta.',

    // Quiz confirmado
    'quiz_confirmed_subject'    => 'Quiz confirmado: inscripciones abiertas',
    'quiz_confirmed_mail_title' => 'Quiz confirmado',
    'quiz_confirmed_mail_body'  => 'el quiz **:title** ha sido confirmado y las inscripciones están ahora abiertas.',
    'quiz_confirmed_mail_cta'   => 'Inscribirse ahora',
    'quiz_confirmed_db_title'   => 'Quiz confirmado',
    'quiz_confirmed_db_body'    => 'El quiz ":title" ha sido confirmado.',

    // Examen completado (notificación admin)
    'exam_completed_subject'  => 'Examen completado: :name',
    'exam_completed_db_title' => 'Examen completado',

    // Insignia ganada
    'badge_db_title'    => 'Has ganado una insignia: :name',
    'badge_push_title'  => 'Nueva insignia: :name',
    'badge_push_body'   => '¡Has ganado una nueva insignia!',
    'badge_push_action' => 'Ver insignias',

    // Repaso espaciado
    'sr_push_title'      => 'Repaso inteligente',
    'sr_push_body_one'   => 'Tienes 1 pregunta para hoy — ¡dedícale 2 minutos!',
    'sr_push_body_many'  => 'Tienes :count preguntas para hoy — ¡dedícales unos minutos!',
    'sr_push_action'     => 'Iniciar repaso',

    // Rol actualizado
    'role_updated_subject'  => 'Tu rol ha sido actualizado',
    'role_updated_db_title' => 'Rol actualizado',

    // Datos personales modificados
    'anagrafica_modified_subject'  => 'Datos personales modificados',
    'anagrafica_modified_db_title' => 'Datos personales modificados',

    // Nueva inscripción (admin)
    'new_enrollment_subject'   => 'Nueva solicitud de inscripción',
    'new_enrollment_db_title'  => 'Nueva inscripción',
    'new_enrollment_db_body'   => ':name ha solicitado inscripción al quiz ":title".',
    'backup_failed_subject'    => 'Error en la copia de seguridad',
    'backup_failed_mail_title' => 'Error en la copia de seguridad automática',
    'backup_failed_mail_body'  => 'La copia de seguridad automática programada encontró un error. Revisa los registros del sistema y resuélvelo antes del próximo ciclo.',
    'backup_failed_mail_cta'   => 'Ir a Estado del sistema',
    'new_report_subject'    => 'Nuevo reporte de pregunta',
    'new_report_db_title'   => 'Nuevo reporte',
    'new_report_db_body'    => ':user reportó la pregunta #:id.',
    'outcome_subject'       => 'Estudiante completó un quiz',
    'outcome_db_title'      => 'Quiz completado',
    'outcome_db_body'       => ':name completó el quiz «:quiz» con puntuación :score%.',
    'outcome_mail_title'    => 'Quiz completado por un estudiante',
    'outcome_mail_body'     => ':name completó el quiz «:quiz». Puntuación: :score%.',
    'outcome_mail_cta'      => 'Ir al detalle del estudiante',
];
