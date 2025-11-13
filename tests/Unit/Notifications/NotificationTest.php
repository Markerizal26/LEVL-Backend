<?php

use Modules\Notifications\Models\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('notification can be created', function () {
    $notification = Notification::create([
        'type' => 'system',
        'title' => 'New Enrollment',
        'message' => 'You have been enrolled in a course',
    ]);

    assertDatabaseHas('notifications', [
        'id' => $notification->id,
        'type' => 'system',
    ]);
});

test('notification can be marked as sent', function () {
    $notification = Notification::factory()->create(['sent_at' => null]);

    $notification->update(['sent_at' => now()]);

    expect($notification->sent_at)->not->toBeNull();
});