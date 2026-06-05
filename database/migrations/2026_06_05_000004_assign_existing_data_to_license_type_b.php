<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bId = DB::table('license_types')
            ->where('code', 'B')
            ->value('id');

        if (!$bId) {
            DB::table('license_types')->insertOrIgnore([
                'code'              => 'B',
                'name'              => 'Patente B',
                'description'       => null,
                'exam_questions'    => 30,
                'exam_minutes'      => 20,
                'exam_max_errors'   => 3,
                'sort_order'        => 0,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $bId = DB::table('license_types')
                ->where('code', 'B')
                ->value('id');
        }

        $categories = DB::table('categories')->get();
        $now = now();

        if ($categories->isNotEmpty()) {
            $pivots = [];
            foreach ($categories as $category) {
                $pivots[] = [
                    'category_id'      => $category->id,
                    'license_type_id'  => $bId,
                ];
            }

            DB::table('category_license_type')->insertOrIgnore($pivots);
        }

        $quizzes = DB::table('quizzes')->whereNull('license_type_id')->count();
        if ($quizzes > 0) {
            DB::table('quizzes')
                ->whereNull('license_type_id')
                ->update(['license_type_id' => $bId]);
        }
    }

    public function down(): void
    {
        $bId = DB::table('license_types')
            ->where('code', 'B')
            ->value('id');

        if ($bId) {
            DB::table('category_license_type')
                ->where('license_type_id', $bId)
                ->delete();

            DB::table('quizzes')
                ->where('license_type_id', $bId)
                ->update(['license_type_id' => null]);
        }
    }
};
