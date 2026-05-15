<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Domande reali di produzione.
        // category_id deve corrispondere agli id definiti in CategorySeeder:
        //  1=Semafori, 2=Obbligo, 3=Cartelli, 4=Guida, 5=Motore,
        //  6=Incroci, 7=Pedoni, 8=Pericolo, 9=Divieto, 10=Motociclo
        //
        // image: null oppure path relativo a storage (es. 'questions/images/production/1.png')
        $questions = [
            // --- Semafori (1) ---
            ['category_id' => 1, 'question' => 'Il semaforo con luce rossa fissa obbliga a fermarsi prima della linea di arresto.', 'is_true' => true,  'image' => null],
            ['category_id' => 1, 'question' => 'Il semaforo con luce gialla lampeggiante indica obbligo di arresto.',                  'is_true' => false, 'image' => null],

            // --- Obbligo (2) ---
            ['category_id' => 2, 'question' => 'I segnali di obbligo hanno forma circolare e fondo blu.', 'is_true' => true,  'image' => null],
            ['category_id' => 2, 'question' => 'Il segnale "direzione obbligatoria a destra" consente di proseguire dritto.', 'is_true' => false, 'image' => null],

            // --- Cartelli (3) ---
            ['category_id' => 3, 'question' => 'I cartelli di indicazione forniscono informazioni utili alla guida.', 'is_true' => true,  'image' => null],
            ['category_id' => 3, 'question' => 'Tutti i cartelli stradali hanno forma triangolare.', 'is_true' => false, 'image' => null],

            // --- Guida (4) ---
            ['category_id' => 4, 'question' => 'Durante la guida occorre mantenere una distanza di sicurezza dal veicolo che precede.', 'is_true' => true,  'image' => null],
            ['category_id' => 4, 'question' => 'È consentito utilizzare il cellulare tenendolo in mano durante la guida.', 'is_true' => false, 'image' => null],

            // --- Motore (5) ---
            ['category_id' => 5, 'question' => 'Il motore a scoppio trasforma l\'energia chimica del carburante in energia meccanica.', 'is_true' => true,  'image' => null],
            ['category_id' => 5, 'question' => 'L\'olio motore non deve mai essere controllato durante la manutenzione ordinaria.', 'is_true' => false, 'image' => null],

            // --- Incroci (6) ---
            ['category_id' => 6, 'question' => 'In un incrocio non regolamentato vige la regola della precedenza a destra.', 'is_true' => true,  'image' => null],
            ['category_id' => 6, 'question' => 'In presenza del segnale "STOP" è consentito non fermarsi se non si vedono altri veicoli.', 'is_true' => false, 'image' => null],

            // --- Pedoni (7) ---
            ['category_id' => 7, 'question' => 'Sulle strisce pedonali il pedone ha sempre la precedenza.', 'is_true' => true,  'image' => null],
            ['category_id' => 7, 'question' => 'Il conducente può accelerare in prossimità di un attraversamento pedonale.', 'is_true' => false, 'image' => null],

            // --- Pericolo (8) ---
            ['category_id' => 8, 'question' => 'I segnali di pericolo hanno forma triangolare con bordo rosso.', 'is_true' => true,  'image' => null],
            ['category_id' => 8, 'question' => 'I segnali di pericolo possono essere ignorati su strade poco trafficate.', 'is_true' => false, 'image' => null],

            // --- Divieto (9) ---
            ['category_id' => 9, 'question' => 'I segnali di divieto hanno forma circolare con bordo rosso.', 'is_true' => true,  'image' => null],
            ['category_id' => 9, 'question' => 'Il segnale di divieto di sosta consente la fermata per il carico e scarico.', 'is_true' => true,  'image' => null],

            // --- Motociclo (10) ---
            ['category_id' => 10, 'question' => 'Sul motociclo è obbligatorio l\'uso del casco omologato.', 'is_true' => true,  'image' => null],
            ['category_id' => 10, 'question' => 'In autostrada è consentito viaggiare con motocicli di cilindrata inferiore a 150 cc.', 'is_true' => false, 'image' => null],
        ];

        $now = now();
        foreach ($questions as &$q) {
            $q['created_at'] = $now;
            $q['updated_at'] = $now;
        }
        unset($q);

        Question::insert($questions);

        $this->command->info('CREATE ' . count($questions) . ' DOMANDE DI PRODUZIONE');
    }
}
