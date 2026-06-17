<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $registrationFields = json_encode([
            ['key' => 'first_name', 'label_key' => 'forms.field_first_name', 'enabled' => false, 'required' => false, 'type' => 'text'],
            ['key' => 'last_name',  'label_key' => 'forms.field_last_name',  'enabled' => false, 'required' => false, 'type' => 'text'],
        ]);

        $enrollmentFields = json_encode([
            ['key' => 'first_name',  'label_key' => 'forms.field_first_name',  'enabled' => true, 'required' => true, 'type' => 'text'],
            ['key' => 'last_name',   'label_key' => 'forms.field_last_name',   'enabled' => true, 'required' => true, 'type' => 'text'],
            ['key' => 'address',     'label_key' => 'forms.field_address',     'enabled' => true, 'required' => true, 'type' => 'text'],
            ['key' => 'birth_date',  'label_key' => 'forms.field_birth_date',  'enabled' => true, 'required' => true, 'type' => 'date'],
            ['key' => 'birth_place', 'label_key' => 'forms.field_birth_place', 'enabled' => true, 'required' => true, 'type' => 'text'],
            ['key' => 'fiscal_code', 'label_key' => 'forms.field_fiscal_code', 'enabled' => true, 'required' => true, 'type' => 'text'],
            ['key' => 'id_document', 'label_key' => 'forms.field_id_document', 'enabled' => true, 'required' => true, 'type' => 'file'],
        ]);

        $now = now();

        DB::table('system_settings')->upsert(
            [
                [
                    'key'        => 'forms.registration_fields',
                    'type'       => 'json',
                    'group'      => 'forms',
                    'label'      => 'Campi form registrazione',
                    'value'      => $registrationFields,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key'        => 'forms.enrollment_fields',
                    'type'       => 'json',
                    'group'      => 'forms',
                    'label'      => 'Campi form iscrizione anagrafica',
                    'value'      => $enrollmentFields,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['key'],
            ['label', 'type', 'group', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['forms.registration_fields', 'forms.enrollment_fields'])
            ->delete();
    }
};
