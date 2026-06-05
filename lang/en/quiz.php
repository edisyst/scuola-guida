<?php

return [
    // Page titles
    'title'    => 'Quiz Management',
    'subtitle' => 'Catalogue',
    'create'   => 'New quiz',
    'edit'     => 'Edit quiz',
    'list'     => 'Quiz list',

    // Table columns
    'col_id'        => 'ID',
    'col_title'     => 'Title',
    'col_status'    => 'Status',
    'col_questions' => 'Questions',
    'col_actions'   => 'Actions',

    // Lifecycle statuses
    'status_draft'     => 'Draft',
    'status_published' => 'Published',
    'status_confirmed' => 'Confirmed',

    // Status descriptions
    'status_draft_desc'     => 'Quiz under preparation. Visible and editable only by admin/editor; not playable by viewers.',
    'status_published_desc' => 'Available to all users in training mode. Can still be edited or moved back to draft.',
    'status_confirmed_desc' => 'Quiz locked for official exam. No longer editable; viewers can only take it after approved enrollment.',

    // Status legend
    'states_legend' => 'Quiz statuses',

    // Actions
    'action_new'         => 'New Quiz',
    'action_random'      => 'Random Quiz',
    'action_publish'     => 'Publish',
    'action_unpublish'   => 'Move to draft',
    'action_confirm'     => 'Confirm (lock)',
    'action_summary'     => 'Summary',
    'action_schedule'    => 'Enrollment schedule',
    'action_questions'   => 'Manage questions',
    'action_fill_random' => 'Add random questions',
    'action_delete'      => 'Delete',
    'action_play'        => 'Play',

    // Disabled tooltips
    'tooltip_no_questions'          => 'No questions in quiz',
    'tooltip_questions_locked'      => 'Confirmed quiz: questions locked',
    'tooltip_already_has_questions' => 'Quiz already has questions',

    // JS confirmations
    'confirm_confirm_lock' => 'Once confirmed, the quiz can no longer be modified. Continue?',
    'confirm_delete'       => 'Are you sure?',
];
