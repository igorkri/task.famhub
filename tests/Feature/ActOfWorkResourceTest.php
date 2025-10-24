<?php

namespace Tests\Feature;

use App\Filament\Resources\ActOfWorks\Pages\CreateActOfWork;
use App\Filament\Resources\ActOfWorks\Pages\EditActOfWork;
use App\Filament\Resources\ActOfWorks\Pages\ListActOfWorks;
use App\Models\ActOfWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActOfWorkResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_act_of_works(): void
    {
        $acts = ActOfWork::factory()->count(3)->create();

        Livewire::test(ListActOfWorks::class)
            ->assertCanSeeTableRecords($acts);
    }

    public function test_can_create_act_of_work(): void
    {
        Livewire::test(CreateActOfWork::class)
            ->fillForm([
                'number' => '12345',
                'status' => 'pending',
                'user_id' => $this->user->id,
                'date' => '2025-10-24',
                'total_amount' => 1000.00,
                'paid_amount' => 500.00,
                'type' => 'act',
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('act_of_works', [
            'number' => '12345',
            'status' => 'pending',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_edit_act_of_work(): void
    {
        $act = ActOfWork::factory()->create();

        Livewire::test(EditActOfWork::class, ['record' => $act->id])
            ->fillForm([
                'status' => 'done',
                'paid_amount' => $act->total_amount,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('act_of_works', [
            'id' => $act->id,
            'status' => 'done',
            'paid_amount' => $act->total_amount,
        ]);
    }

    public function test_can_search_act_of_works(): void
    {
        $act1 = ActOfWork::factory()->create(['number' => 'ACT-001']);
        $act2 = ActOfWork::factory()->create(['number' => 'ACT-002']);

        Livewire::test(ListActOfWorks::class)
            ->searchTable('ACT-001')
            ->assertCanSeeTableRecords([$act1])
            ->assertCanNotSeeTableRecords([$act2]);
    }

    public function test_can_filter_act_of_works_by_status(): void
    {
        $pendingAct = ActOfWork::factory()->create(['status' => 'pending']);
        $doneAct = ActOfWork::factory()->create(['status' => 'done']);

        Livewire::test(ListActOfWorks::class)
            ->filterTable('status', 'done')
            ->assertCanSeeTableRecords([$doneAct])
            ->assertCanNotSeeTableRecords([$pendingAct]);
    }
}
