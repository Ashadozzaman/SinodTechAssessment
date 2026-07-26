<?php

namespace Tests\Feature;

use App\Enums\EngagementChannel;
use App\Enums\EngagementStatus;
use App\Mail\ReengagementMail;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReengagementTest extends TestCase
{
    use RefreshDatabase;

    private CrmService $crmService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crmService = app(CrmService::class);

        collect(['customers.view', 'crm.reengage'])
            ->each(fn (string $permission) => Permission::create(['name' => $permission]));
    }

    private function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission.'-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_email_channel_sends_mail_and_creates_a_sent_engagement_row(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000030', 'email' => 'jane@example.com']);
        $admin = User::factory()->create();

        $engagement = $this->crmService->sendReengagement($customer, 'email', 'Hi Jane, we miss you!', $admin);

        $this->assertSame(EngagementChannel::Email, $engagement->channel);
        $this->assertSame(EngagementStatus::Sent, $engagement->status);

        Mail::assertSent(ReengagementMail::class, fn (ReengagementMail $mail) => $mail->customer->id === $customer->id
            && $mail->message === 'Hi Jane, we miss you!');

        $this->assertDatabaseHas('customer_engagements', [
            'customer_id' => $customer->id,
            'channel' => 'email',
            'status' => 'sent',
            'triggered_by' => $admin->id,
        ]);
    }

    public function test_email_channel_marks_engagement_failed_when_customer_has_no_email_on_file(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'No Email', 'phone' => '01700000031']);

        $engagement = $this->crmService->sendReengagement($customer, 'email', 'Hi there!', null);

        $this->assertSame(EngagementStatus::Failed, $engagement->status);
        Mail::assertNothingSent();
    }

    public function test_sms_channel_creates_a_simulated_engagement_row_without_sending_any_mail(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'John Doe', 'phone' => '01700000032']);
        $admin = User::factory()->create();

        $engagement = $this->crmService->sendReengagement($customer, 'sms', 'Hi John, we miss you!', $admin);

        $this->assertSame(EngagementChannel::Sms, $engagement->channel);
        $this->assertSame(EngagementStatus::Simulated, $engagement->status);

        Mail::assertNothingSent();

        $this->assertDatabaseHas('customer_engagements', [
            'customer_id' => $customer->id,
            'channel' => 'sms',
            'status' => 'simulated',
            'triggered_by' => $admin->id,
        ]);
    }

    public function test_user_without_crm_reengage_permission_cannot_send_a_reengagement_message(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000033']);

        $this->actingAs($this->userWithPermission('customers.view'));

        $this->post("/customers/{$customer->id}/reengage", ['channel' => 'email', 'message' => 'Hi!'])
            ->assertForbidden();

        $this->assertDatabaseMissing('customer_engagements', ['customer_id' => $customer->id]);
    }

    public function test_user_with_crm_reengage_permission_can_send_a_reengagement_message(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000034', 'email' => 'jane@example.com']);

        $this->actingAs($this->userWithPermission('crm.reengage'));

        $this->post("/customers/{$customer->id}/reengage", ['channel' => 'email', 'message' => 'Hi Jane, we miss you!'])
            ->assertRedirect();

        $this->assertDatabaseHas('customer_engagements', [
            'customer_id' => $customer->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_reengage_request_rejects_an_unknown_channel(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000035']);

        $this->actingAs($this->userWithPermission('crm.reengage'));

        $this->post("/customers/{$customer->id}/reengage", ['channel' => 'carrier-pigeon', 'message' => 'Hi!'])
            ->assertSessionHasErrors('channel');

        $this->assertDatabaseMissing('customer_engagements', ['customer_id' => $customer->id]);
    }
}
