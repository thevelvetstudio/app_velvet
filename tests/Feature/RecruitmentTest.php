<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function applicationPayload(string $type = 'MODEL'): array
    {
        return ['candidate_type' => $type, 'first_name' => 'Laura', 'last_name' => 'Gomez', 'sex' => 'WOMAN', 'phone' => '3001234567', 'email' => "laura-{$type}@example.com", 'city' => 'Bogota', 'birth_date' => '1998-01-01', 'experience' => 'Experiencia previa', 'availability' => 'Tiempo completo', 'work_mode' => $type === 'MONITOR' ? 'En estudio' : 'Presencial', 'source' => 'Instagram', 'data_consent' => true];
    }

    public function test_public_model_application_creates_a_lead_with_tracking(): void
    {
        $response = $this->withHeaders(['referer' => 'https://instagram.com/velvet'])->post('/apply?utm_source=instagram&utm_campaign=talent', $this->applicationPayload());
        $response->assertOk();
        $this->assertDatabaseHas('leads', ['candidate_type' => 'MODEL', 'email' => 'laura-MODEL@example.com', 'utm_source' => 'instagram', 'utm_campaign' => 'talent']);
        $this->assertDatabaseHas('lead_activities', ['type' => 'created']);
    }

    public function test_monitor_application_creates_a_monitor_lead(): void
    {
        $this->post('/apply', $this->applicationPayload('MONITOR'))->assertOk();
        $this->assertDatabaseHas('leads', ['candidate_type' => 'MONITOR']);
    }

    public function test_monitor_application_only_allows_studio_work_mode(): void
    {
        $payload = $this->applicationPayload('MONITOR');
        $payload['work_mode'] = 'Desde casa';

        $this->from('/apply')->post('/apply', $payload)->assertRedirect('/apply')->assertSessionHasErrors('work_mode');
    }

    public function test_authenticated_recruiter_can_convert_a_lead_only_once(): void
    {
        $payload = $this->applicationPayload();
        unset($payload['data_consent']);
        $lead = Lead::create(['code' => 'VEL-LEAD-000001', 'status' => 'NEW', ...$payload]);
        $user = User::factory()->create()->assignRole('recruiter');
        $this->actingAs($user)->post("/admin/leads/{$lead->id}/convert")->assertRedirect();
        $this->assertDatabaseHas('candidates', ['lead_id' => $lead->id, 'candidate_type' => 'MODEL']);
        $this->actingAs($user)->post("/admin/leads/{$lead->id}/convert")->assertStatus(422);
    }

    public function test_authenticated_recruiter_can_load_the_real_dashboard_data(): void
    {
        $payload = $this->applicationPayload();
        unset($payload['data_consent']);
        Lead::create(['code' => 'VEL-LEAD-000002', 'status' => 'NEW', ...$payload]);
        $user = User::factory()->create()->assignRole('recruiter');

        $response = $this->actingAs($user)->get('/admin/recruitment?period=30d&type=MODEL');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Recruitment/Dashboard')
            ->has('metrics', 6)
            ->has('pipeline', 8)
            ->has('chart')
            ->where('filters.type', 'MODEL'));
    }
}
