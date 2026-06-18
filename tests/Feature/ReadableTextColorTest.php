<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReadableTextColorTest extends TestCase
{
    /** @test */
    public function giallo_chiaro_ritorna_testo_scuro(): void
    {
        $this->assertSame('#212529', readableTextColor('#ffeb3b'));
    }

    /** @test */
    public function blu_scuro_ritorna_testo_bianco(): void
    {
        $this->assertSame('#ffffff', readableTextColor('#0d47a1'));
    }

    /** @test */
    public function accent_default_ritorna_testo_scuro(): void
    {
        // #3c8dbc: luminanza ≈ 0.235 > 0.179 → testo scuro
        $this->assertSame('#212529', readableTextColor('#3c8dbc'));
    }

    /** @test */
    public function hex_shorthand_gestito_correttamente(): void
    {
        // #fff = #ffffff: luminanza 1.0 → testo scuro
        $this->assertSame('#212529', readableTextColor('#fff'));
    }

    /** @test */
    public function rosso_puro_ritorna_testo_bianco(): void
    {
        // #ff0000: luminanza ≈ 0.2126 > 0.179 → testo scuro
        // (nota: il rosso puro è borderline, luminanza 0.2126 > 0.179)
        $this->assertSame('#212529', readableTextColor('#ff0000'));
    }

    /** @test */
    public function nero_puro_ritorna_testo_bianco(): void
    {
        $this->assertSame('#ffffff', readableTextColor('#000000'));
    }
}
