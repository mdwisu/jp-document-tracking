<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Models\VehicleDepoLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Tests\TestCase;

class VehicleDepoLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.logbo' => config('database.connections.sqlite')]);
        Schema::connection('logbo')->create('Depo', function (Blueprint $table) {
            $table->string('KodeDepo');
            $table->string('NamaSingkat');
        });
        DB::connection('logbo')->table('Depo')->insert([
            ['KodeDepo' => 'A', 'NamaSingkat' => 'Depo A'],
            ['KodeDepo' => 'B', 'NamaSingkat' => 'Depo B'],
        ]);
    }

    public function test_public_link_is_scoped_to_depo_including_documents(): void
    {
        Storage::fake('vehicle_files');
        $link = VehicleDepoLink::create(['kode_depo' => 'A', 'token' => Str::random(64)]);
        $a = $this->vehicle('A');
        $b = $this->vehicle('B');
        $fileA = $this->file($a);
        $fileB = $this->file($b);

        $this->get(route('vehicles.public.index', $link->token))->assertOk()
            ->assertSee($a->no_polisi)->assertDontSee($b->no_polisi)->assertDontSee('Tambah Mobil');
        $this->get(route('vehicles.public.show', [$link->token, $a]))->assertOk()->assertDontSee('>Hapus', false);
        $this->get(route('vehicles.public.show', [$link->token, $b]))->assertNotFound();
        foreach (['preview', 'download'] as $action) {
            $this->get(route('vehicles.public.'.$action, [$link->token, $fileA]))->assertOk();
            $this->get(route('vehicles.public.'.$action, [$link->token, $fileB]))->assertNotFound();
        }
        $this->get(route('vehicles.public.index', ['token-tidak-valid']))->assertNotFound();
    }

    public function test_only_admin_can_generate_links_and_rotation_revokes_old_link(): void
    {
        $this->post(route('vehicles.saveDepoLink'), ['kode_depo' => 'A'])->assertRedirect(route('vehicles.unlockForm'));
        $this->assertDatabaseCount('vehicle_depo_links', 0);
        $this->withSession(['vehicles_unlocked' => true])->get(route('vehicles.settings'))->assertOk()->assertSee('Link Lihat Data Mobil per Depo');
        $this->post(route('vehicles.saveDepoLink'), ['kode_depo' => 'A'])->assertRedirect();
        $old = VehicleDepoLink::first()->token;
        $this->post(route('vehicles.saveDepoLink'), ['kode_depo' => 'A'])->assertRedirect();
        $new = VehicleDepoLink::first()->token;
        $this->assertNotSame($old, $new);
        $this->assertDatabaseCount('vehicle_depo_links', 1);
        $this->get(route('vehicles.public.index', $old))->assertNotFound();
        $this->get(route('vehicles.public.index', $new))->assertOk();
    }

    private function vehicle(string $depo): Vehicle
    {
        return Vehicle::create(['mobil_id' => Str::uuid(), 'kode_mobil' => 'M'.$depo, 'no_polisi' => 'B 123 '.$depo, 'kode_depo' => $depo]);
    }

    private function file(Vehicle $vehicle)
    {
        $path = 'vehicles/'.$vehicle->id.'/stnk.pdf';
        Storage::disk('vehicle_files')->put($path, 'document');
        return $vehicle->files()->create(['type' => 'stnk', 'original_filename' => 'stnk.pdf', 'stored_filename' => 'stnk.pdf', 'file_path' => $path, 'file_size' => 8]);
    }
}
