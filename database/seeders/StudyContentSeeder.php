<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\StudyContent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudyContentSeeder extends Seeder
{
    private const CONTENTS = [
        [
            'title' => 'Segnali di precedenza: come comportarsi agli incroci',
            'body'  => "<p>Agli incroci regolati da segnali di precedenza, il conducente che si trova sulla strada con il segnale <strong>«Stop»</strong> deve fermarsi alla linea di arresto e cedere la precedenza a tutti i veicoli provenienti da destra e da sinistra sulla strada con diritto di precedenza.</p><p>Il segnale di <strong>«Dare precedenza»</strong> impone di rallentare fino ad arrestarsi se necessario. In assenza di segnali, vige la regola della destra: si deve cedere la precedenza ai veicoli provenienti da destra.</p><h4>Punti chiave</h4><ul><li>Stop: fermata obbligatoria, anche in assenza di traffico.</li><li>Dare precedenza: rallentare e fermarsi solo se necessario.</li><li>Senza segnali: precedenza a destra.</li></ul>",
        ],
        [
            'title' => 'Distanza di sicurezza: calcolo e normativa',
            'body'  => "<p>La distanza di sicurezza è lo spazio minimo da mantenere rispetto al veicolo che precede per potersi fermare in sicurezza in caso di frenata improvvisa. Non è fissa: dipende dalla velocità, dalle condizioni del fondo stradale e dallo stato di usura dei pneumatici.</p><p>Una regola pratica è quella dei <strong>due secondi</strong>: scegliere un punto fisso e verificare che passino almeno due secondi dal momento in cui il veicolo davanti lo supera a quando lo supera il proprio veicolo. Con pioggia o fondo scivoloso raddoppiare l'intervallo.</p><p>Secondo il Codice della Strada (art. 149), in autostrada l'indicatore di distanza è obbligatorio per i mezzi pesanti.</p>",
        ],
        [
            'title' => 'Limiti di velocità: riepilogo per tipo di strada',
            'body'  => "<p>I limiti di velocità in Italia variano in base al tipo di strada e al tipo di veicolo. Per le autovetture i limiti ordinari sono:</p><ul><li><strong>Strade urbane:</strong> 50 km/h</li><li><strong>Strade extraurbane secondarie:</strong> 90 km/h</li><li><strong>Strade extraurbane principali:</strong> 110 km/h</li><li><strong>Autostrade:</strong> 130 km/h</li></ul><p>In caso di pioggia o neve, i limiti in autostrada scendono rispettivamente a 110 e 90 km/h. La segnaletica verticale può sempre imporre limiti inferiori rispetto a quelli ordinari.</p>",
        ],
        [
            'title' => 'Uso corretto delle luci: quando e quali accendere',
            'body'  => "<p>L'uso delle luci è regolato dagli articoli 152 e 153 del Codice della Strada. Di notte e nelle gallerie è obbligatorio accendere almeno gli <strong>anabbaglianti</strong>. Gli abbaglianti si usano fuori dai centri abitati quando non ci sono veicoli in senso contrario o nella stessa direzione a distanza ravvicinata.</p><p>Le luci di posizione (o di ingombro) da sole non sono sufficienti durante la marcia; servono solo da sosta. Le <strong>luci di emergenza</strong> (quattro frecce) si usano in caso di pericolo improvviso o quando si è costretti a fermarsi su strada in condizioni di scarsa visibilità.</p>",
        ],
        [
            'title' => 'Sorpasso: regole e divieti',
            'body'  => "<p>Il sorpasso deve essere effettuato sempre sul lato sinistro, tranne nei casi previsti dal Codice (veicolo che svolta a sinistra, doppia carreggiata con sensi di marcia separati). Prima di sorpassare è obbligatorio verificare che:</p><ol><li>La strada davanti sia libera per una distanza sufficiente.</li><li>Il veicolo che segue non abbia già iniziato il sorpasso.</li><li>Il veicolo da sorpassare non abbia segnalato l'intenzione di svoltare.</li></ol><p>Il sorpasso è vietato in prossimità di incroci non regolati, curve, dossi, passaggi a livello e nelle gallerie prive di corsia aggiuntiva.</p>",
        ],
        [
            'title' => 'Pneumatici invernali e catene: obblighi e normativa',
            'body'  => "<p>Dal 15 novembre al 15 aprile (con possibili variazioni per ordinanza locale), molte strade italiane impongono l'uso di pneumatici invernali (M+S) o il trasporto di catene a bordo. I pneumatici invernali sono riconoscibili dal simbolo <strong>M+S</strong> o dal simbolo montagna con fiocco di neve.</p><p>Le catene vanno montate sulle ruote motrici e non si possono superare i 50 km/h con esse montate. La mancata osservanza comporta sanzioni e, in caso di incidente, può invalidare la copertura assicurativa.</p>",
        ],
        [
            'title' => 'Guida in autostrada: norme fondamentali',
            'body'  => "<p>In autostrada è obbligatorio percorrere la corsia di destra, usando le corsie di sinistra solo per il sorpasso e rientrando immediatamente dopo. La <strong>corsia di emergenza</strong> è riservata ai veicoli in panne, ai soccorsi e alle forze dell'ordine; circolarvi è vietato e molto pericoloso.</p><p>Prima di immettersi in autostrada, usare la rampa di accelerazione per raggiungere una velocità compatibile con il traffico. All'uscita, usare la rampa di decelerazione senza frenare in corsia.</p><p>In caso di nebbia fitta o maltempo, è obbligatorio usare le luci fendinebbia anteriori e posteriori quando la visibilità scende sotto i 50 m.</p>",
        ],
        [
            'title' => 'Cinture di sicurezza e sistemi di ritenuta per bambini',
            'body'  => "<p>L'uso delle cinture di sicurezza è obbligatorio per tutti gli occupanti del veicolo, sia davanti che dietro. Il conducente è responsabile del corretto utilizzo da parte dei passeggeri di età inferiore ai 14 anni.</p><p>I bambini fino a 150 cm di altezza devono essere trasportati su seggiolini omologati adeguati al peso e all'altezza. I seggiolini rivolti all'indietro non possono essere installati nel sedile anteriore se è presente un airbag frontale attivo, salvo che quest'ultimo sia disattivato.</p>",
        ],
    ];

    public function run(): void
    {
        $editor     = User::where('role', User::ROLE_EDITOR)->first();
        $admin      = User::where('role', User::ROLE_ADMIN)->first();
        $authorId   = $editor?->id ?? $admin?->id;
        $categories = Category::inRandomOrder()->take(5)->get();

        if ($categories->isEmpty()) {
            $this->command->warn('Nessuna categoria trovata: StudyContentSeeder saltato.');
            return;
        }

        $contentCount = 0;
        $readCount    = 0;

        foreach ($categories as $index => $category) {
            // 1-2 articoli per categoria
            $numContents = fake()->numberBetween(1, 2);
            $pool        = collect(self::CONTENTS)->shuffle();

            for ($i = 0; $i < $numContents; $i++) {
                $template  = $pool->get(($index * 2 + $i) % count(self::CONTENTS));
                $createdAt = Carbon::now()->subDays(fake()->numberBetween(5, 60));

                $content = StudyContent::create([
                    'studyable_type' => Category::class,
                    'studyable_id'   => $category->id,
                    'title'          => $template['title'],
                    'body'           => $template['body'],
                    'is_published'   => true,
                    'order'          => $i + 1,
                    'created_by'     => $authorId,
                    'updated_by'     => $authorId,
                    'created_at'     => $createdAt,
                    'updated_at'     => $createdAt,
                ]);

                $contentCount++;

                // 60% dei viewer ha letto questo contenuto
                $viewers = User::where('role', User::ROLE_VIEWER)->get();
                foreach ($viewers as $viewer) {
                    if (! fake()->boolean(60)) {
                        continue;
                    }

                    $readAt = $createdAt->copy()->addDays(fake()->numberBetween(1, 10));

                    DB::table('study_content_user')->insertOrIgnore([
                        'study_content_id' => $content->id,
                        'user_id'          => $viewer->id,
                        'read_at'          => $readAt,
                        'created_at'       => $readAt,
                    ]);
                    $readCount++;
                }
            }
        }

        $this->command->info("CREATI {$contentCount} CONTENUTI STUDIO, {$readCount} LETTURE (StudyContent)");
    }
}
