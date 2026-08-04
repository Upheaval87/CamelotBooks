<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Customer;
use App\Models\TodoTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTaskTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Company $otherCompany;
    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->otherCompany = Company::create([
            'company_code' => 'OTHCO',
            'name' => 'Other Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->user->companies()->attach($this->otherCompany->id, ['role' => 'company_admin']);
        $this->otherUser->companies()->attach($this->company->id, ['role' => 'accountant']);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->otherUser->assignRole('accountant');

        setPermissionsTeamId($this->otherCompany->id);
        $this->user->assignRole('company_admin');

        session(['current_company_id' => $this->company->id]);
    }

    private function createTask(User $user, array $overrides = []): TodoTask
    {
        return TodoTask::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'title' => 'Review Q3 numbers',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->toDateString(),
            'priority' => TodoTask::PRIORITY_MEDIUM,
            'status' => TodoTask::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_user_can_store_and_view_own_task(): void
    {
        $this->actingAs($this->user)
            ->post(route('todo.store'), [
                'title' => 'Follow up with ACME',
                'deadline_granularity' => TodoTask::GRANULARITY_MONTH,
                'deadline_date' => now()->endOfMonth()->toDateString(),
                'priority' => TodoTask::PRIORITY_HIGH,
            ])
            ->assertRedirect(route('todo.index'));

        $this->assertDatabaseHas('todo_tasks', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Follow up with ACME',
            'priority' => TodoTask::PRIORITY_HIGH,
        ]);

        $this->actingAs($this->user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee('Follow up with ACME')
            ->assertSee('Due this month');
    }

    public function test_tasks_are_strictly_personal(): void
    {
        $task = $this->createTask($this->user);

        // Another user in the same company cannot see it...
        $this->actingAs($this->otherUser)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertDontSee('Review Q3 numbers');

        // ...and cannot act on it.
        $this->actingAs($this->otherUser)
            ->post(route('todo.complete', $task))
            ->assertNotFound();

        $this->actingAs($this->otherUser)
            ->put(route('todo.update', $task), ['title' => 'Hijacked', 'priority' => TodoTask::PRIORITY_LOW])
            ->assertNotFound();

        $this->actingAs($this->otherUser)
            ->delete(route('todo.destroy', $task))
            ->assertNotFound();

        $this->assertDatabaseHas('todo_tasks', ['id' => $task->id, 'title' => 'Review Q3 numbers', 'status' => TodoTask::STATUS_ACTIVE]);
    }

    public function test_tasks_are_scoped_to_the_active_company(): void
    {
        $task = $this->createTask($this->user);

        session(['current_company_id' => $this->otherCompany->id]);

        $this->actingAs($this->user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertDontSee('Review Q3 numbers');

        $this->actingAs($this->user)
            ->post(route('todo.complete', $task))
            ->assertNotFound();
    }

    public function test_complete_and_reopen_cycle(): void
    {
        $task = $this->createTask($this->user);

        $this->actingAs($this->user)
            ->post(route('todo.complete', $task))
            ->assertRedirect(route('todo.index'));

        $task->refresh();
        $this->assertSame(TodoTask::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->completed_at);

        $this->actingAs($this->user)
            ->post(route('todo.reopen', $task))
            ->assertRedirect(route('todo.index'));

        $task->refresh();
        $this->assertSame(TodoTask::STATUS_ACTIVE, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_index_groups_tasks_into_deadline_buckets(): void
    {
        $this->createTask($this->user, [
            'title' => 'Overdue one',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->subDays(2)->toDateString(),
        ]);
        $this->createTask($this->user, [
            'title' => 'Due today',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->toDateString(),
        ]);
        $this->createTask($this->user, [
            'title' => 'This month',
            'deadline_granularity' => TodoTask::GRANULARITY_MONTH,
            'deadline_date' => now()->endOfMonth()->toDateString(),
        ]);
        $this->createTask($this->user, [
            'title' => 'This year',
            'deadline_granularity' => TodoTask::GRANULARITY_YEAR,
            'deadline_date' => now()->endOfYear()->toDateString(),
        ]);
        $this->createTask($this->user, [
            'title' => 'No deadline',
            'deadline_granularity' => null,
            'deadline_date' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('todo.index'))->assertOk();

        $response->assertSee('Overdue one')->assertSee('Due today')
            ->assertSee('This month')->assertSee('This year')->assertSee('No deadline')
            ->assertSee('Overdue')->assertSee('Due today')
            ->assertSee('This Month')->assertSee('This Year')->assertSee('No Deadline');
    }

    public function test_deadline_for_and_bucket_key(): void
    {
        $now = \Illuminate\Support\Carbon::parse('2026-08-04 12:00:00');

        $this->assertSame('2026-08-04', TodoTask::deadlineFor(TodoTask::GRANULARITY_DAY, $now)->format('Y-m-d'));
        $this->assertSame('2026-08-09', TodoTask::deadlineFor(TodoTask::GRANULARITY_WEEK, $now)->format('Y-m-d'));
        $this->assertSame('2026-08-31', TodoTask::deadlineFor(TodoTask::GRANULARITY_MONTH, $now)->format('Y-m-d'));
        $this->assertSame('2026-12-31', TodoTask::deadlineFor(TodoTask::GRANULARITY_YEAR, $now)->format('Y-m-d'));

        $this->assertSame(TodoTask::BUCKET_NO_DEADLINE, TodoTask::bucketKey(null, null, $now));
        $this->assertSame(TodoTask::BUCKET_OVERDUE, TodoTask::bucketKey($now->copy()->subDay()->startOfDay(), TodoTask::GRANULARITY_DAY, $now));
        $this->assertSame(TodoTask::BUCKET_TODAY, TodoTask::bucketKey($now->copy()->startOfDay(), TodoTask::GRANULARITY_DAY, $now));
        $this->assertSame(TodoTask::BUCKET_THIS_MONTH, TodoTask::bucketKey($now->copy()->endOfMonth(), TodoTask::GRANULARITY_MONTH, $now));
        $this->assertSame(TodoTask::BUCKET_THIS_YEAR, TodoTask::bucketKey($now->copy()->endOfYear(), TodoTask::GRANULARITY_YEAR, $now));
    }

    public function test_link_snapshot_survives_linked_record_deletion(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);

        $task = $this->createTask($this->user, [
            'linkable_type' => Customer::class,
            'linkable_id' => $customer->id,
            'link_label' => 'Widget Co',
            'link_url' => route('accounting.customers.show', $customer->id),
        ]);

        $customer->delete();

        $task->refresh();
        $this->assertNull($task->linkable);
        $this->assertSame('Widget Co', $task->link_label);

        $this->actingAs($this->user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee('Widget Co');
    }

    public function test_dashboard_exposes_personal_task_counts(): void
    {
        $this->createTask($this->user, [
            'title' => 'Overdue item',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->subDays(1)->toDateString(),
        ]);
        $this->createTask($this->user, [
            'title' => 'Today item',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->toDateString(),
        ]);
        // Someone else's overdue task must not count for this user.
        $this->createTask($this->otherUser, [
            'title' => 'Not mine',
            'deadline_granularity' => TodoTask::GRANULARITY_DAY,
            'deadline_date' => now()->subDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'))->assertOk();

        $response->assertViewHas('todoOverdue', 1);
        $response->assertViewHas('todoToday', 1);
        $response->assertSee('Overdue item');
        $response->assertDontSee('Not mine');
    }

    public function test_rejects_invalid_linkable_type(): void
    {
        $this->actingAs($this->user)
            ->from(route('todo.index'))
            ->post(route('todo.store'), [
                'title' => 'Bad link',
                'priority' => TodoTask::PRIORITY_MEDIUM,
                'linkable_type' => \App\Models\Company::class,
                'linkable_id' => $this->company->id,
                'link_label' => 'Bad',
                'link_url' => '/bad',
            ])
            ->assertSessionHasErrors('linkable_type');

        $this->assertDatabaseMissing('todo_tasks', ['title' => 'Bad link']);
    }

    public function test_rejects_invalid_granularity_and_priority(): void
    {
        $this->actingAs($this->user)
            ->from(route('todo.index'))
            ->post(route('todo.store'), [
                'title' => 'Invalid fields',
                'priority' => 'urgent',
                'deadline_granularity' => 'quarterly',
            ])
            ->assertSessionHasErrors(['priority', 'deadline_granularity']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('todo.index'))->assertRedirect(route('login'));
    }

    public function test_composer_renders_link_capture_bindings(): void
    {
        $response = $this->actingAs($this->user)->get(route('todo.index'))->assertOk();

        // Quick-add form must listen for item-selected or link picks are lost.
        $response->assertSee('@item-selected="onLinkSelected($event)"', false);
        $response->assertSee('name="linkable_type"', false);
        $response->assertSee('name="linkable_id"', false);
        $response->assertSee('name="link_label"', false);
        $response->assertSee('name="link_url"', false);
    }

    public function test_store_persists_record_link(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);

        $this->actingAs($this->user)
            ->post(route('todo.store'), [
                'title' => 'Call customer',
                'priority' => TodoTask::PRIORITY_MEDIUM,
                'linkable_type' => Customer::class,
                'linkable_id' => $customer->id,
                'link_label' => 'Widget Co',
                'link_url' => route('accounting.customers.show', $customer->id),
            ])
            ->assertRedirect(route('todo.index'));

        $this->assertDatabaseHas('todo_tasks', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'title' => 'Call customer',
            'linkable_type' => Customer::class,
            'linkable_id' => $customer->id,
            'link_label' => 'Widget Co',
        ]);

        $this->actingAs($this->user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee('Call customer')
            ->assertSee('Widget Co');
    }

    public function test_edit_row_prefills_title_and_link(): void
    {
        $customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Widget Co']);
        $this->createTask($this->user, [
            'linkable_type' => Customer::class,
            'linkable_id' => $customer->id,
            'link_label' => 'Widget Co',
            'link_url' => route('accounting.customers.show', $customer->id),
        ]);

        $this->actingAs($this->user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee("title: 'Review Q3 numbers'", false)
            ->assertSee('Widget Co');
    }
}
