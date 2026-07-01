<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditoriaExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->signIn();
    }

    public function test_export_returns_valid_xlsx_with_data(): void
    {
        $response = $this->get('/export/auditoria');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=auditoria.xlsx');

        // Save the response content to a temp file and check it's a valid xlsx
        $content = $response->streamedContent();
        $this->assertNotNull($content, 'streamedContent should not be null');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_audit_xlsx_');
        file_put_contents($tmpFile, $content);

        $fileSize = filesize($tmpFile);
        $this->assertGreaterThan(1000, $fileSize, 'XLSX file should be > 1000 bytes (headers + potential data)');

        // Verify it's a valid ZIP (xlsx is a zip)
        $zip = new \ZipArchive;
        $open = $zip->open($tmpFile);
        $this->assertTrue($open === true, 'XLSX should be a valid ZIP archive');
        $zip->close();

        unlink($tmpFile);
    }
}
