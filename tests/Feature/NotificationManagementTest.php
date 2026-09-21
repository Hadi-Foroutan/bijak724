<?php

use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->adminToken = $this->admin->createToken('admin-notifications')->plainTextToken;
    $this->userToken = $this->user->createToken(
        'user-notifications',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
});

test('admin sends a realtime notification without deleting previous messages', function () {
    Event::fake([NotificationSent::class]);

    Notification::factory()
        ->for($this->admin, 'sender')
        ->create(['message' => 'پیام قبلی']);

    $response = $this->withToken($this->adminToken)->postJson('/api/admin/notifications', [
        'type' => NotificationType::Warning->value,
        'should_remove_previous' => true,
        'message' => 'اختلال موقت در سامانه',
    ]);

    $notificationId = $response
        ->assertCreated()
        ->assertJsonPath('data.type', NotificationType::Warning->value)
        ->assertJsonPath('data.type_label', 'هشدار')
        ->assertJsonPath('data.should_remove_previous', true)
        ->assertJsonPath('data.sender_id', $this->admin->id)
        ->assertJsonPath('data.sender.id', $this->admin->id)
        ->assertJsonPath('data.sender.username', $this->admin->username)
        ->json('data.id');

    expect(Notification::query()->count())->toBe(2);

    $this->assertDatabaseHas('notifications', [
        'id' => $notificationId,
        'sender_id' => $this->admin->id,
        'type' => NotificationType::Warning->value,
        'should_remove_previous' => true,
        'message' => 'اختلال موقت در سامانه',
    ]);

    Event::assertDispatched(
        NotificationSent::class,
        fn (NotificationSent $event): bool => $event->notification->id === $notificationId,
    );
});

test('admin sees full notification data including sender details', function () {
    $notification = Notification::factory()
        ->for($this->admin, 'sender')
        ->create([
            'type' => NotificationType::Success,
            'should_remove_previous' => false,
            'message' => 'عملیات با موفقیت انجام شد',
        ]);

    $this->withToken($this->adminToken)
        ->getJson('/api/admin/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('data.0.sender.email', $this->admin->email)
        ->assertJsonPath('data.0.sender.username', $this->admin->username);
});

test('users can list notifications but cannot send them', function () {
    $notification = Notification::factory()
        ->for($this->admin, 'sender')
        ->create([
            'type' => NotificationType::Success,
            'should_remove_previous' => false,
            'message' => 'عملیات با موفقیت انجام شد',
        ]);

    $this->withToken($this->userToken)
        ->getJson('/api/user/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('data.0.sender.id', $this->admin->id)
        ->assertJsonPath('data.0.sender.full_name', $this->admin->full_name)
        ->assertJsonMissingPath('data.0.sender.email')
        ->assertJsonMissingPath('data.0.sender.username');

    $this->withToken($this->userToken)
        ->postJson('/api/user/notifications', [
            'type' => NotificationType::Info->value,
            'message' => 'پیام غیرمجاز',
        ])
        ->assertMethodNotAllowed();
});

test('notification validation accepts only supported message types', function () {
    $this->withToken($this->adminToken)
        ->postJson('/api/admin/notifications', [
            'type' => 'unknown',
            'should_remove_previous' => 'not-a-boolean',
            'message' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'type',
            'should_remove_previous',
            'message',
        ]);
});

test('notification event broadcasts the user payload on the private notifications channel', function () {
    $notification = Notification::factory()
        ->for($this->admin, 'sender')
        ->create([
            'type' => NotificationType::Info,
            'should_remove_previous' => true,
            'message' => 'اطلاعیه جدید',
        ])
        ->load('sender');

    $event = new NotificationSent($notification);
    $payload = $event->broadcastWith();

    expect($event->broadcastAs())->toBe('notification.sent')
        ->and($event->broadcastOn()[0]->name)->toBe('private-notifications')
        ->and($payload['notification']['id'])->toBe($notification->id)
        ->and($payload['notification']['sender']['id'])->toBe($this->admin->id)
        ->and($payload['notification']['should_remove_previous'])->toBeTrue();
});

test('authenticated users can authorize the private notifications channel', function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app');
    Broadcast::setDefaultDriver('reverb');
    Broadcast::channel(
        'notifications',
        fn (User $user): bool => $user->exists,
        ['guards' => ['sanctum']],
    );

    $this->withToken($this->userToken)
        ->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-notifications',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});
