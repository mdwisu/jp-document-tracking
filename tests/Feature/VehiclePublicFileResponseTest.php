<?php

namespace Tests\Feature;

use App\Http\Controllers\VehicleController;
use App\Models\Mobil;
use App\Models\Vehicle;
use App\Models\VehicleDepoLink;
use App\Models\VehicleFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class VehiclePublicFileResponseTest extends TestCase
{
    public function test_public_preview_and_download_return_file_with_privacy_headers(): void
    {
        Storage::fake('vehicle_files');
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        Storage::disk('vehicle_files')->put('vehicles/barcode.png', $contents);

        $vehicle = new Vehicle;
        $mobil = new Mobil;
        $mobil->KodeDepo = 'A';
        $vehicle->setRelation('mobil', $mobil);
        $file = new VehicleFile([
            'file_path' => 'vehicles/barcode.png',
            'original_filename' => 'barcode.png',
        ]);
        $file->setRelation('vehicle', $vehicle);
        $link = new VehicleDepoLink(['kode_depo' => 'A']);
        $controller = new VehicleController;

        foreach (['publicPreview', 'publicDownload'] as $action) {
            $response = $controller->$action($link, $file);

            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame($contents, file_get_contents($response->getFile()->getPathname()));
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
            if ($action === 'publicPreview') {
                $this->assertSame('image/png', $response->headers->get('Content-Type'));
            } else {
                $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
            }
        }
    }
}
