<?php

use Modules\Common\Models\Audit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('system audit can be created', function () {
    $user = \Modules\Auth\Models\User::factory()->create();

    $audit = Audit::create([
        'action' => 'create',
        'user_id' => $user->id,
        'module' => 'Schemes',
        'target_table' => 'courses',
        'target_id' => 1,
        'context' => 'system',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Agent',
        'meta' => ['test' => 'data'],
    ]);

    assertDatabaseHas('audits', [
        'id' => $audit->id,
        'action' => 'create',
        'module' => 'Schemes',
        'context' => 'system',
    ]);
});

test('system audit meta is casted to array', function () {
    $audit = Audit::create([
        'action' => 'update',
        'context' => 'system',
        'meta' => ['key' => 'value'],
    ]);

    $audit->refresh();
    expect($audit->meta)->toBeArray();
    expect($audit->meta['key'])->toEqual('value');
});

test('system audit scope filters by context', function () {
    Audit::create([
        'action' => 'create',
        'context' => 'system',
        'module' => 'Schemes',
    ]);

    Audit::create([
        'action' => 'create',
        'context' => 'application',
        'module' => 'Schemes',
    ]);

    $systemAudits = Audit::system()->get();
    expect($systemAudits)->toHaveCount(1);
    expect($systemAudits->first()->context)->toEqual('system');
});
