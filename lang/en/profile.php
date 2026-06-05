<?php

return [
    // Enrollment status
    'reg_approved'          => 'You are authorized to enroll in <strong>official driving exams</strong>.',
    'reg_approved_on'       => 'Approved on :date',
    'reg_approved_by'       => 'by :name',
    'reg_resubmit_warn'     => 'If you modify your personal data and resubmit, you will need to be re-authorized by the administrator before participating in new exams.',
    'reg_pending'           => 'Your request is <strong>pending approval</strong>.',
    'reg_pending_sent'      => 'Submitted on :date',
    'reg_pending_practice'  => 'You can still <strong>practice freely with quizzes</strong> while waiting for review.',
    'reg_rejected'          => 'Your request has been <strong>rejected</strong>.',
    'reg_rejected_reason'   => 'Reason:',
    'reg_rejected_fix'      => 'Correct the information and resubmit your request.',
    'reg_none'              => 'To enroll in <strong>official driving exams</strong> you must first submit your personal data and wait for administrator approval.',
    'reg_practice_meanwhile'=> 'In the meantime, you can always <strong>practice with quizzes</strong> freely.',

    // Form fields
    'field_first_name'      => 'First name',
    'field_last_name'       => 'Last name',
    'field_address'         => 'Home address',
    'field_address_ph'      => 'Street, number, city, postcode',
    'field_birth_date'      => 'Date of birth',
    'field_birth_place'     => 'Place of birth',
    'field_fiscal_code'     => 'Tax ID / Fiscal code',
    'field_document'        => 'Identity document',
    'document_uploaded'     => 'Document uploaded',
    'document_replace'      => 'upload a new file only if you want to replace it',
    'document_formats'      => 'Allowed formats: PDF, JPG, PNG. Maximum size: 5 MB.',

    // Submit buttons
    'submit_first'          => 'Submit enrollment request',
    'submit_update_pending' => 'Update pending request',
    'submit_rejected'       => 'Resubmit request',
    'submit_reapprove'      => 'Resubmit data (requires new approval)',

    // Confirm dialogs
    'confirm_first_send'    => 'Do you confirm submitting your data for official exam enrollment?',
    'confirm_reapprove'     => 'By resubmitting your data you will temporarily lose exam authorization until the administrator re-approves. Proceed?',

    // TTS / Accessibility
    'tts_title'             => 'Accessibility',
    'tts_enabled_label'     => 'Enable text-to-speech (TTS)',
    'tts_autoplay_label'    => 'Auto-play voice',

    // Profile page — sections and titles
    'page_title'            => 'Profile',
    'account_subtitle'      => 'Account',
    'my_profile'            => 'My profile',
    'info_section'          => 'Profile information',
    'reg_section'           => 'Official exam enrollment',
    'password_section'      => 'Update password',
    'twofa_section'         => 'Two-factor authentication',
    'tts_desc'              => 'Enable automatic audio reading of questions to replicate DSA support required by the ministerial exam (D.Lgs. 62/2017).',
    'tts_field_label'       => 'Audio reading of questions',
    'tts_autoplay_field_label' => 'Auto-start on each question',
    'save_prefs'            => 'Save preferences',
    'push_section'          => 'Push notifications',
    'push_active'           => 'Active',
    'push_inactive'         => 'Inactive',
    'push_desc'             => 'Receive native notifications even when the app is closed (badges earned, enrollment approval, SM-2 review reminders).',
    'push_not_supported'    => 'Your browser does not support push notifications or the site is not served over HTTPS.',
    'push_subscribe'        => 'Enable push notifications',
    'push_subscribing'      => 'Activating…',
    'push_unsubscribe'      => 'Disable push notifications',
    'push_unsubscribing'    => 'Deactivating…',
    'push_permission_denied'=> 'Permission denied. Enable notifications in your browser settings.',
    'push_activate_error'   => 'Error during activation: ',
    'push_deactivate_error' => 'Error during deactivation: ',
    'gdpr_section'          => 'Data portability',
    'gdpr_desc'             => 'Download a ZIP archive with all your personal data in JSON format (GDPR art. 20 — right to data portability). The file includes quizzes, bookmarks, badges, activity and, if uploaded, your identity document.',
    'gdpr_download'         => 'Download my data',
    'delete_section'        => 'Delete account',
    'profile_updated'       => 'Profile updated successfully.',
    'password_updated'      => 'Password updated successfully.',

    // Profile info form
    'name_label'          => 'Name',
    'email_label'         => 'Email',
    'email_unverified'    => 'Your email address is not verified.',
    'send_verification'   => 'Click here to re-send the verification email.',
    'verification_sent'   => 'A new verification link has been sent to your email address.',

    // Password form
    'current_password'    => 'Current password',
    'new_password'        => 'New password',
    'confirm_password'    => 'Confirm password',
    'update_password_btn' => 'Update password',

    // Delete account
    'delete_account'             => 'Delete account',
    'delete_account_desc'        => 'Once your account is deleted, all its resources and data will be permanently deleted. Before proceeding, please download any data you wish to retain.',
    'delete_account_confirm_title' => 'Are you sure you want to delete your account?',
    'delete_account_confirm_desc'  => 'Once your account is deleted, all its resources and data will be permanently deleted. Please enter your password to confirm account deletion.',

    // 2FA
    'twofa_active_since'      => '2FA active since :date.',
    'twofa_disable_btn'       => 'Disable 2FA',
    'twofa_regenerate_btn'    => 'Regenerate recovery codes',
    'twofa_disable_title'     => 'Disable two-factor authentication',
    'twofa_disable_desc'      => 'Enter your current password to confirm disabling 2FA.',
    'twofa_regen_title'       => 'Regenerate recovery codes',
    'twofa_regen_desc'        => 'Current codes will be invalidated. Enter your password to proceed.',
    'twofa_regen_btn'         => 'Regenerate codes',
    'twofa_not_enabled'       => '2FA is not yet enabled on your account. It is required to access the admin area.',
    'twofa_enable_btn'        => 'Enable 2FA',
    'twofa_platform_disabled' => '2FA is currently disabled on this platform.',

    // Registration status badge
    'status_approved_badge' => 'Approved',
    'status_pending_badge'  => 'Pending approval',
    'status_rejected_badge' => 'Rejected',
    'status_none_badge'     => 'Not submitted',
];
