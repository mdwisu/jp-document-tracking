<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Illuminate\Http\UploadedFile;
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
        Schema::connection('logbo')->create('Mobil', function (Blueprint $table) {
            $table->string('MobilId')->primary();
            $table->string('KodeMobil')->default('MBL-TEST');
            $table->string('NoPolisi')->default('B 123 TEST');
            $table->integer('SoftDelete')->default(0);
            $table->string('KodeDepo')->nullable();
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
        $this->get(route('vehicles.public.show', [$link->token, $a]))->assertOk()->assertSee('>Hapus', false);
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

    public function test_moving_vehicle_changes_public_access_immediately(): void
    {
        Storage::fake('vehicle_files');
        $a = VehicleDepoLink::create(['kode_depo' => 'A', 'token' => Str::random(64)]);
        $b = VehicleDepoLink::create(['kode_depo' => 'B', 'token' => Str::random(64)]);
        $vehicle = $this->vehicle('A');
        $file = $this->file($vehicle);
        DB::connection('logbo')->table('Mobil')->where('MobilId', $vehicle->mobil_id)->update(['KodeDepo' => 'B']);

        $this->get(route('vehicles.public.index', $a->token))->assertOk()->assertDontSee($vehicle->no_polisi);
        $this->get(route('vehicles.public.index', $b->token))->assertOk()->assertSee($vehicle->no_polisi);
        $this->get(route('vehicles.public.show', [$a->token, $vehicle]))->assertNotFound();
        $this->get(route('vehicles.public.show', [$b->token, $vehicle]))->assertOk()->assertSee('Depo B');
        foreach (['preview', 'download'] as $action) {
            $this->get(route('vehicles.public.'.$action, [$a->token, $file]))->assertNotFound();
            $this->get(route('vehicles.public.'.$action, [$b->token, $file]))->assertOk();
        }
    }

    public function test_deleting_document_requires_admin_and_preserves_physical_file(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $old = $this->file($vehicle);
        $new = $vehicle->files()->create([
            'type' => 'stnk', 'original_filename' => 'new.pdf', 'stored_filename' => 'new.pdf',
            'file_path' => 'vehicles/new.pdf', 'file_size' => 8,
        ]);
        Storage::disk('vehicle_files')->put($new->file_path, 'new document');
        $this->get(route('vehicles.public.index', VehicleDepoLink::create([
            'kode_depo' => 'A', 'token' => Str::random(64),
        ])->token))->assertOk()->assertSee('1/4');
        $this->delete(route('vehicleFiles.destroy', $new))->assertRedirect(route('vehicles.unlockForm'));
        Storage::disk('vehicle_files')->assertExists($new->file_path);
        $this->withSession(['vehicles_unlocked' => true])->delete(route('vehicleFiles.destroy', $new))
            ->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertSoftDeleted('vehicle_files', ['id' => $new->id]);
        Storage::disk('vehicle_files')->assertExists($new->file_path);
        Storage::disk('vehicle_files')->assertExists($old->file_path);
        $this->assertSame($old->id, $vehicle->fresh()->currentFileOfType('stnk')->id);
    }

    public function test_incomplete_package_is_rejected_without_saving_files(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $payload = $this->completePackage($vehicle);
        unset($payload['files']['foto_kanan']);
        $this->postJson(route('vehicles.savePhotos', VehicleSetting::current()->create_token), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('files.foto_kanan');
        $this->assertDatabaseCount('vehicle_files', 0);
        $this->assertSame([], Storage::disk('vehicle_files')->allFiles());
    }

    public function test_complete_package_saves_all_eight_photos(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $this->postJson(route('vehicles.savePhotos', VehicleSetting::current()->create_token), $this->completePackage($vehicle))->assertOk();
        $this->assertDatabaseCount('vehicle_files', 8);
        $this->assertCount(8, Storage::disk('vehicle_files')->allFiles());
        $this->assertSame(0, $vehicle->fresh()->document_count);
    }

    public function test_document_can_be_saved_independently_without_photos(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $payload = $this->completePackage($vehicle);
        $this->postJson(route('vehicles.saveDocument', VehicleSetting::current()->create_token), [
            'mobil_id' => $vehicle->mobil_id, 'type' => 'stnk',
            'file' => $payload['files']['foto_depan'], 'expiry_date' => '2028-01-01',
        ])->assertOk();
        $this->assertDatabaseCount('vehicle_files', 1);
        $this->assertSame(1, $vehicle->fresh()->document_count);
    }

    public function test_public_deletion_is_limited_to_current_depo_and_valid_token(): void
    {
        Storage::fake('vehicle_files');
        $link = VehicleDepoLink::create(['kode_depo' => 'A', 'token' => Str::random(64)]);
        $a = $this->vehicle('A');
        $b = $this->vehicle('B');
        $fileA = $this->file($a);
        $fileB = $this->file($b);
        $this->delete(route('vehicles.public.destroyFile', ['invalid', $fileA]))->assertNotFound();
        $this->delete(route('vehicles.public.destroyFile', [$link->token, $fileB]))->assertNotFound();
        $this->delete(route('vehicles.public.destroy', [$link->token, $b]))->assertNotFound();
        Storage::disk('vehicle_files')->assertExists($fileB->file_path);
        $this->delete(route('vehicles.public.destroyFile', [$link->token, $fileA]))
            ->assertRedirect(route('vehicles.public.show', [$link->token, $a]));
        Storage::disk('vehicle_files')->assertExists($fileA->file_path);
        $this->assertSoftDeleted('vehicle_files', ['id' => $fileA->id]);
        DB::connection('logbo')->table('Mobil')->where('MobilId', $a->mobil_id)->update(['KodeDepo' => 'B']);
        $this->delete(route('vehicles.public.destroy', [$link->token, $a]))->assertNotFound();
        DB::connection('logbo')->table('Mobil')->where('MobilId', $a->mobil_id)->update(['KodeDepo' => 'A']);
        $this->delete(route('vehicles.public.destroy', [$link->token, $a]))
            ->assertRedirect(route('vehicles.public.index', $link->token));
        $this->assertSoftDeleted('vehicles', ['id' => $a->id]);
        $this->assertNotSoftDeleted('vehicles', ['id' => $b->id]);
    }

    public function test_only_logged_in_admin_can_restore_or_permanently_delete_trashed_files(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $file = $this->file($vehicle);
        $file->delete();
        $this->post(route('vehicleFiles.restore', $file->id))->assertRedirect(route('vehicles.unlockForm'));
        $this->delete(route('vehicleFiles.forceDelete', $file->id))->assertRedirect(route('vehicles.unlockForm'));
        $this->withSession(['vehicles_unlocked' => true])->get(route('vehicles.trash'))->assertOk()->assertSee($file->original_filename);
        $this->post(route('vehicleFiles.restore', $file->id))->assertRedirect();
        $this->assertNotSoftDeleted('vehicle_files', ['id' => $file->id]);
        $file->fresh()->delete();
        $this->delete(route('vehicleFiles.forceDelete', $file->id))->assertRedirect();
        $this->assertDatabaseMissing('vehicle_files', ['id' => $file->id]);
        Storage::disk('vehicle_files')->assertMissing($file->file_path);
    }

    public function test_restoring_vehicle_keeps_files_in_trash_until_chosen_for_restore(): void
    {
        Storage::fake('vehicle_files');
        $vehicle = $this->vehicle('A');
        $file = $this->file($vehicle);
        $vehicle->delete();
        $this->withSession(['vehicles_unlocked' => true])->post(route('vehicles.restore', $vehicle->id))->assertRedirect();
        $this->assertNotSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertSoftDeleted('vehicle_files', ['id' => $file->id]);
        Storage::disk('vehicle_files')->assertExists($file->file_path);
        $this->post(route('vehicleFiles.restore', $file->id))->assertRedirect();
        $this->assertNotSoftDeleted('vehicle_files', ['id' => $file->id]);
        $vehicle->fresh()->delete();
        $this->delete(route('vehicles.forceDelete', $vehicle->id))->assertRedirect();
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('vehicle_files', ['id' => $file->id]);
        Storage::disk('vehicle_files')->assertMissing($file->file_path);
    }

    private function completePackage(Vehicle $vehicle): array
    {
        $files = [];
        foreach (['foto_depan', 'foto_belakang', 'foto_kiri', 'foto_kanan', 'foto_depan_kiri', 'foto_depan_kanan', 'foto_belakang_kiri', 'foto_belakang_kanan'] as $type) {
            $files[$type] = UploadedFile::fake()->createWithContent($type.'.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        }
        return ['mobil_id' => $vehicle->mobil_id, 'files' => $files, 'expiry_dates' => ['stnk' => '2028-01-01', 'kir' => '2028-01-01', 'pajak' => '2028-01-01']];
    }

    private function vehicle(string $depo): Vehicle
    {
        $id = (string) Str::uuid();
        DB::connection('logbo')->table('Mobil')->insert(['MobilId' => $id, 'KodeDepo' => $depo]);
        return Vehicle::create(['mobil_id' => $id, 'kode_mobil' => 'M'.$depo, 'no_polisi' => 'B 123 '.$depo, 'kode_depo' => $depo]);
    }

    private function file(Vehicle $vehicle)
    {
        $path = 'vehicles/'.$vehicle->id.'/stnk.pdf';
        Storage::disk('vehicle_files')->put($path, 'document');
        return $vehicle->files()->create(['type' => 'stnk', 'original_filename' => 'stnk.pdf', 'stored_filename' => 'stnk.pdf', 'file_path' => $path, 'file_size' => 8]);
    }
}
