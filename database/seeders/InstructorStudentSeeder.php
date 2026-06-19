<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InstructorStudentSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('role', User::ROLE_INSTRUCTOR)->first();
        $admin      = User::where('role', User::ROLE_ADMIN)->first();

        if (! $instructor) {
            $this->command->warn('Nessun instructor trovato: InstructorStudentSeeder saltato.');
            return;
        }

        $viewers = User::where('role', User::ROLE_VIEWER)->take(8)->get();

        if ($viewers->isEmpty()) {
            $this->command->warn('Nessun viewer trovato: InstructorStudentSeeder saltato.');
            return;
        }

        $count = 0;
        foreach ($viewers as $viewer) {
            DB::table('instructor_student')->insertOrIgnore([
                'instructor_id' => $instructor->id,
                'student_id'    => $viewer->id,
                'assigned_at'   => Carbon::now()->subDays(fake()->numberBetween(5, 60)),
                'assigned_by'   => $admin?->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $count++;
        }

        $this->command->info("ASSEGNATI {$count} STUDENTI ALL'ISTRUTTORE (Feature 6.6)");
    }
}
