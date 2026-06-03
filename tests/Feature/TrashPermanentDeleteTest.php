<?php

namespace Tests\Feature;

use App\Models\Depo;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrashPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_employee_can_be_permanently_deleted_with_files(): void
    {
        Storage::fake('employee_files');

        $depo = Depo::create([
            'name' => 'Depo A',
            'password' => 'secret',
        ]);

        $employee = $this->createEmployee($depo, 'Budi');
        $path = "depos/{$depo->id}/{$employee->id}/ktp.pdf";
        Storage::disk('employee_files')->put($path, 'file-content');

        $file = $employee->files()->create([
            'type' => 'ktp',
            'original_filename' => 'Budi_KTP.pdf',
            'stored_filename' => 'ktp.pdf',
            'file_path' => $path,
            'file_size' => 12,
        ]);

        $employee->delete();

        $this->withSession(['master_unlocked' => true])
            ->delete(route('trash.forceDeleteEmployee', $employee->id))
            ->assertRedirect(route('trash.index'));

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        $this->assertDatabaseMissing('employee_files', ['id' => $file->id]);
        Storage::disk('employee_files')->assertMissing($path);
    }

    public function test_deleted_depo_can_be_permanently_deleted_with_all_employee_files(): void
    {
        Storage::fake('employee_files');

        $depo = Depo::create([
            'name' => 'Depo B',
            'password' => 'secret',
        ]);

        $employeeA = $this->createEmployee($depo, 'Andi');
        $employeeB = $this->createEmployee($depo, 'Sari');
        $pathA = "depos/{$depo->id}/{$employeeA->id}/ktp.pdf";
        $pathB = "depos/{$depo->id}/{$employeeB->id}/kk.pdf";

        Storage::disk('employee_files')->put($pathA, 'file-a');
        Storage::disk('employee_files')->put($pathB, 'file-b');

        $fileA = $employeeA->files()->create([
            'type' => 'ktp',
            'original_filename' => 'Andi_KTP.pdf',
            'stored_filename' => 'ktp.pdf',
            'file_path' => $pathA,
            'file_size' => 12,
        ]);
        $fileB = $employeeB->files()->create([
            'type' => 'kk',
            'original_filename' => 'Sari_KK.pdf',
            'stored_filename' => 'kk.pdf',
            'file_path' => $pathB,
            'file_size' => 12,
        ]);

        $depo->delete();

        $this->withSession(['master_unlocked' => true])
            ->delete(route('trash.forceDeleteDepo', $depo->id))
            ->assertRedirect(route('trash.index'));

        $this->assertDatabaseMissing('depos', ['id' => $depo->id]);
        $this->assertDatabaseMissing('employees', ['id' => $employeeA->id]);
        $this->assertDatabaseMissing('employees', ['id' => $employeeB->id]);
        $this->assertDatabaseMissing('employee_files', ['id' => $fileA->id]);
        $this->assertDatabaseMissing('employee_files', ['id' => $fileB->id]);
        Storage::disk('employee_files')->assertMissing($pathA);
        Storage::disk('employee_files')->assertMissing($pathB);
    }

    private function createEmployee(Depo $depo, string $name): Employee
    {
        return $depo->employees()->create([
            'name' => $name,
            'ktp_number' => '1234567890123456',
            'kk_number' => '6543210987654321',
            'address' => 'Jl. Contoh',
            'phone' => '081234567890',
            'email' => strtolower($name) . '@example.com',
            'tanggal_mulai_kerja' => '2026-06-03',
        ]);
    }
}
