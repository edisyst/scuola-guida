<?php

return [
    // Registration approved
    'reg_approved_subject'    => 'Personal data enrollment approved',
    'reg_approved_mail_title' => 'Enrollment approved',
    'reg_approved_mail_body'  => 'your personal data enrollment has been **approved** by the administrator. You can now request enrollment in official driving quizzes.',
    'reg_approved_mail_cta'   => 'Go to quiz catalog',
    'reg_approved_mail_closing' => 'Good luck on your exams!',
    'reg_approved_db_title'   => 'Enrollment approved',
    'reg_approved_db_body'    => 'Your personal data enrollment has been approved: you can now enroll in official exams.',
    'reg_approved_push_title' => 'Enrollment approved',
    'reg_approved_push_body'  => 'Your enrollment has been approved: you can now sign up for official exams.',
    'reg_approved_push_action'=> 'Open dashboard',

    // Registration rejected
    'reg_rejected_subject'    => 'Personal data enrollment not approved',
    'reg_rejected_mail_title' => 'Enrollment not approved',
    'reg_rejected_mail_body'  => 'your personal data enrollment request was not approved. Check the reason in your personal area and submit a new request with the correct information.',
    'reg_rejected_mail_cta'   => 'Go to profile',
    'reg_rejected_db_title'   => 'Enrollment rejected',
    'reg_rejected_db_body'    => 'Your personal data enrollment request was not approved. Check the reasons in your personal area.',

    // Quiz enrollment approved
    'enrollment_approved_subject'    => 'Quiz enrollment approved',
    'enrollment_approved_mail_title' => 'Quiz enrollment approved',
    'enrollment_approved_mail_body'  => 'your enrollment in quiz **:title** has been **approved**. You can now take the quiz from the enrollment area.',
    'enrollment_approved_mail_cta'   => 'Go to enrollments',
    'enrollment_approved_db_title'   => 'Quiz enrollment approved',
    'enrollment_approved_db_body'    => 'Your enrollment in quiz ":title" has been approved.',

    // Quiz enrollment rejected
    'enrollment_rejected_subject'    => 'Quiz enrollment not approved',
    'enrollment_rejected_mail_title' => 'Quiz enrollment rejected',
    'enrollment_rejected_mail_body'  => 'your enrollment request for quiz **:title** was not approved.',
    'enrollment_rejected_mail_cta'   => 'Go to enrollments',
    'enrollment_rejected_db_title'   => 'Quiz enrollment rejected',
    'enrollment_rejected_db_body'    => 'Your enrollment request for quiz ":title" was not approved.',

    // Quiz enrollment reopened
    'enrollment_reopened_subject'    => 'Quiz enrollment reopened',
    'enrollment_reopened_mail_title' => 'Quiz enrollment reopened',
    'enrollment_reopened_mail_body'  => 'your enrollment for quiz **:title** has been reopened.',
    'enrollment_reopened_mail_cta'   => 'Go to enrollments',
    'enrollment_reopened_db_title'   => 'Quiz enrollment reopened',
    'enrollment_reopened_db_body'    => 'Your enrollment for quiz ":title" has been reopened.',

    // Quiz confirmed
    'quiz_confirmed_subject'    => 'Quiz confirmed: enrollments open',
    'quiz_confirmed_mail_title' => 'Quiz confirmed',
    'quiz_confirmed_mail_body'  => 'quiz **:title** has been confirmed and enrollments are now open.',
    'quiz_confirmed_mail_cta'   => 'Enroll now',
    'quiz_confirmed_db_title'   => 'Quiz confirmed',
    'quiz_confirmed_db_body'    => 'Quiz ":title" has been confirmed.',

    // Exam completed (admin notification)
    'exam_completed_subject'  => 'Exam completed: :name',
    'exam_completed_db_title' => 'Exam completed',

    // Badge earned
    'badge_db_title'    => 'You earned a badge: :name',
    'badge_push_title'  => 'New badge: :name',
    'badge_push_body'   => 'You earned a new badge!',
    'badge_push_action' => 'View badges',

    // Spaced repetition
    'sr_push_title'      => 'Smart review',
    'sr_push_body_one'   => 'You have 1 question due today — spend 2 minutes on it!',
    'sr_push_body_many'  => 'You have :count questions due today — spend a few minutes on them!',
    'sr_push_action'     => 'Start review',

    // Role updated
    'role_updated_subject'  => 'Your role has been updated',
    'role_updated_db_title' => 'Role updated',

    // Personal data modified
    'anagrafica_modified_subject'  => 'Personal data modified',
    'anagrafica_modified_db_title' => 'Personal data modified',

    // New enrollment (admin)
    'new_enrollment_subject'   => 'New enrollment request',
    'new_enrollment_db_title'  => 'New enrollment',
    'new_enrollment_db_body'   => ':name has requested enrollment in quiz ":title".',
];
