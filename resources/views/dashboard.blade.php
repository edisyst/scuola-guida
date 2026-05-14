<x-app-layout>
    <div class="py-8" style="background:#f4f6f8;min-height:calc(100vh - 64px);">
        <div class="sg-wrapper" style="padding:0 16px;">

            <div class="sg-header sg-flex-between">
                <div>
                    <p class="sg-header-subtitle">Bentornato, {{ Auth::user()->name }}</p>
                    <h1 class="sg-header-title">Dashboard</h1>
                </div>
                <div class="sg-header-actions">
                    <a href="{{ route('quiz.attempts.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-history"></i> I miei tentativi
                    </a>
                </div>
            </div>

            <div class="row" style="margin:0 -8px;">
                <div class="col-md-6" style="padding:0 8px;margin-bottom:16px;">
                    <a href="{{ route('quiz.play', 1) }}" class="sg-stat-card" style="text-decoration:none;">
                        <div class="sg-stat-icon grad-green"><i class="fas fa-play"></i></div>
                        <div>
                            <div class="sg-stat-value" style="font-size:1.1rem;">Inizia un nuovo quiz</div>
                            <div class="sg-stat-label">Simula l'esame di teoria</div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6" style="padding:0 8px;margin-bottom:16px;">
                    <a href="{{ route('profile.edit') }}" class="sg-stat-card" style="text-decoration:none;">
                        <div class="sg-stat-icon grad-blue"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="sg-stat-value" style="font-size:1.1rem;">Gestisci profilo</div>
                            <div class="sg-stat-label">Aggiorna i tuoi dati</div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="sg-card sg-mt-2">
                <div class="sg-card-body">
                    <p class="sg-mb-0">
                        <i class="fas fa-info-circle sg-text-muted"></i>
                        {{ __("Sei collegato. Usa la sidebar admin o le card qui sopra per iniziare.") }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
