<?php

return [

    // Section titles
    'title_modules'         => 'Driving practice modules',
    'title_sessions'        => 'Recorded sessions',
    'title_progress'        => 'Driving practice progress',

    // Instructor card
    'card_title'            => 'Driving practice',

    // Warnings
    'theory_warning'        => 'The student has not yet passed the theory exam. Practical driving is not yet available.',
    'no_license_type'       => 'No license type associated with this student. Contact an administrator.',

    // Modules — table columns
    'col_code'              => 'Code',
    'col_name'              => 'Name',
    'col_license_type'      => 'License type',
    'col_required_hours'    => 'Required hours',
    'col_sessions'          => 'Sessions',
    'col_actions'           => 'Actions',

    // Modules — form fields
    'field_license_type'    => 'License type',
    'field_code'            => 'Code (max 5 chars)',
    'field_name'            => 'Module name',
    'field_description'     => 'Description',
    'field_required_hours'  => 'Required hours',
    'field_sort_order'      => 'Sort order',

    // Modules — actions
    'btn_new_module'        => 'New module',
    'btn_save'              => 'Save',
    'btn_cancel'            => 'Cancel',
    'btn_edit'              => 'Edit',
    'btn_delete'            => 'Delete',

    // Modules — empty state
    'modules_empty'         => 'No modules configured for this license type.',
    'modules_empty_hint'    => 'Create the first module to start structuring driving practice.',

    // Modules — confirmations
    'module_delete_confirm' => 'Are you sure you want to delete this module? All associated sessions will be deleted.',

    // Modules — filter
    'filter_all_types'      => 'All types',
    'filter_label'          => 'Filter by license type',

    // Modules — page titles
    'create_title'          => 'New driving practice module',
    'edit_title'            => 'Edit module',

    // Sessions — form fields
    'field_module'          => 'Module',
    'field_conducted_at'    => 'Session date',
    'field_duration'        => 'Duration (min)',
    'field_notes'           => 'Notes',

    // Sessions — actions
    'register_session'      => 'Record session',
    'session_delete_confirm'=> 'Delete this session?',

    // Sessions — columns
    'session_date'          => 'Date',
    'session_module'        => 'Module',
    'session_duration'      => 'Duration',
    'session_notes'         => 'Notes',

    // Sessions — status
    'session_none'          => 'No sessions recorded.',

    // Progress
    'progress_title'        => 'Completion',
    'progress_completed'    => 'Completed',
    'progress_sessions'     => 'sessions',
    'progress_empty'        => 'No modules available. Contact your instructor or driving school.',
    'progress_all_done'     => 'Congratulations! You have completed all required driving practice hours.',
    'progress_hours'        => ':completed / :required hours',
    'progress_pct'          => ':pct% completed',

    // Service errors
    'module_has_sessions'   => 'Cannot delete the module: recorded sessions exist. Delete the sessions first.',

    // Alternative titles
    'title_create'          => 'New module',
    'title_edit'            => 'Edit module',

];
