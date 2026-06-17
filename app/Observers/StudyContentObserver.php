<?php

namespace App\Observers;

use App\Models\StudyContent;

class StudyContentObserver
{
    // La pivot study_content_user ha cascadeOnDelete() via FK.
    // Nessuna azione aggiuntiva necessaria qui.
    // Il trait Auditable gestisce already created/updated/deleted logging.
}
