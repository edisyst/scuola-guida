composer create-project --prefer-dist laravel/laravel:^11.0 scuola-guida
composer create-project laravel/laravel scuola-guida

# INSTALL
cd scuola-guida
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
COPIARE IMMAGINI IN STORAGE

# RUN
npm install
npm run dev
php artisan serve
http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scuola_guida
DB_USERNAME=root
DB_PASSWORD=

composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run dev
php artisan migrate

php artisan make:migration add_is_admin_to_users_table
php artisan migrate
php artisan make:seeder AdminUserSeeder
php artisan db:seed

composer require jeroennoten/laravel-adminlte
php artisan adminlte:install
// testare su /login con user=admin@test.com password=password poi andare su /admin

php artisan make:model Category -mcr
php artisan make:model Question -mcr
php artisan migrate

php artisan storage:link

php artisan make:factory QuestionFactory
php artisan make:factory CategoryFactory
php artisan migrate:fresh --seed


php artisan make:model Quiz -m
php artisan make:migration create_quiz_question_table
php artisan migrate

php artisan make:controller QuizController


php artisan make:request StoreQuestionRequest
php artisan make:request UpdateQuestionRequest


php artisan make:model QuizResult -m
php artisan migrate

php artisan test
php artisan make:test CategoryTest
php artisan make:test QuestionTest

php artisan make:test QuizServiceTest

php artisan make:request StoreQuestionRequest

// DATATABLES
@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@stop
@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
@stop

composer require barryvdh/laravel-debugbar --dev

ATTIVA NEL PHP.INI extension=zip
composer require maatwebsite/excel:^3.1 
php artisan make:export QuestionsExport --model=Question
php artisan make:import QuestionsImport --model=Question

// questo non l'ho fatto, mi sa che è inutile al momento
php artisan queue:table
php artisan migrate
php artisan make:job ImportQuestionsJob

php artisan make:migration create_audit_logs_table
📁 app/Models/AuditLog.php
📁 app/Traits/Auditable.php
php artisan make:test AuditLogTest

php artisan make:migration add_permissions_to_users_table
