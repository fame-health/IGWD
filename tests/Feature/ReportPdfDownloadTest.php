<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_report_pdfs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        foreach (['dashboard', 'daily-monitoring', 'dialysis-sessions', 'risk-patients', 'risk-alerts'] as $report) {
            $response = $this->get("/admin/reports/{$report}/pdf");

            $response->assertOk();
            $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }
}
