<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_from_android_api(): void
    {
        $response = $this->postJson('/api/v1/register-patient', [
            'name' => 'Andi Pasien',
            'email' => 'andi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nik' => '3171000000000010',
            'birth_date' => '1985-05-12',
            'gender' => 'laki-laki',
            'address' => 'Jakarta',
            'phone' => '081234567890',
            'responsible_person_name' => 'Siti',
            'responsible_person_phone' => '081298765432',
            'payment_status' => 'BPJS',
            'medical_profile' => [
                'main_diagnosis' => 'CKD Stage 5',
                'hemodialysis_frequency' => '2x per minggu',
                'dry_weight' => 60.5,
                'vascular_access' => 'AV Fistula',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registrasi pasien berhasil.')
            ->assertJsonPath('data.user.email', 'andi@example.com')
            ->assertJsonPath('data.user.role', 'pasien')
            ->assertJsonPath('data.patient.name', 'Andi Pasien')
            ->assertJsonPath('data.medical_profile.main_diagnosis', 'CKD Stage 5')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role', 'patient_id', 'is_active'],
                    'patient' => ['id', 'medical_record_number', 'name', 'gender', 'patient_status'],
                    'medical_profile',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'andi@example.com',
            'role' => 'pasien',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'Andi Pasien',
            'nik' => '3171000000000010',
            'patient_status' => 'Aktif',
        ]);

        $this->assertDatabaseHas('patient_medical_profiles', [
            'main_diagnosis' => 'CKD Stage 5',
            'hemodialysis_frequency' => '2x per minggu',
        ]);
    }

    public function test_patient_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'duplikat@example.com']);

        $response = $this->postJson('/api/v1/register-patient', [
            'name' => 'Pasien Baru',
            'email' => 'duplikat@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'perempuan',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_patient_can_register_without_medical_profile(): void
    {
        $response = $this->postJson('/api/v1/register-patient', [
            'name' => 'Pasien Tanpa Profil Medis',
            'email' => 'tanpa.profil@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'perempuan',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.medical_profile', null);

        $this->assertDatabaseHas('patients', [
            'name' => 'Pasien Tanpa Profil Medis',
            'patient_status' => 'Aktif',
        ]);

        $this->assertDatabaseCount('patient_medical_profiles', 0);
    }
}
