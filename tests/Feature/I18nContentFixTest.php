<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class I18nContentFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_reports_col_type_resolves_in_italian(): void
    {
        $this->app->setLocale('it');
        $this->assertNotEquals('editor.reports_col_type', __('editor.reports_col_type'));
    }

    public function test_editor_reports_col_reporter_resolves_in_italian(): void
    {
        $this->app->setLocale('it');
        $this->assertNotEquals('editor.reports_col_reporter', __('editor.reports_col_reporter'));
    }

    public function test_editor_reports_col_date_resolves_in_italian(): void
    {
        $this->app->setLocale('it');
        $this->assertNotEquals('editor.reports_col_date', __('editor.reports_col_date'));
    }

    public function test_common_all_returns_tutti_in_italian(): void
    {
        $this->app->setLocale('it');
        $this->assertEquals('Tutti', __('common.all'));
    }

    public function test_guest_homepage_does_not_contain_english_placeholder(): void
    {
        \DB::table('system_settings')->updateOrInsert(
            ['key' => 'school.tagline'],
            ['type' => 'string', 'group' => 'school', 'label' => 'Slogan / tagline', 'value' => '']
        );

        $response = $this->get('/');
        $response->assertDontSee('Edoardo is building ScuolaGUIDA');
    }
}
