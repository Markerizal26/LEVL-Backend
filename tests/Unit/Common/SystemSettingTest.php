<?php

use Modules\Common\Models\SystemSetting;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('get returns default when setting not exists', function () {
    $value = SystemSetting::get('non.existent.key', 'default-value');

    expect($value)->toEqual('default-value');
});

test('set and get string value', function () {
    SystemSetting::set('test.string', 'Hello World');

    $value = SystemSetting::get('test.string');

    expect($value)->toEqual('Hello World');
});

test('set and get number value', function () {
    SystemSetting::set('test.number', 42);

    $value = SystemSetting::get('test.number');

    expect($value)->toEqual(42);
    expect($value)->toBeInt();
});

test('set and get boolean value', function () {
    SystemSetting::set('test.boolean', true);

    $value = SystemSetting::get('test.boolean');

    expect($value)->toBeTrue();
    expect($value)->toBeBool();
});

test('set and get json value', function () {
    $data = ['key1' => 'value1', 'key2' => 'value2'];
    SystemSetting::set('test.json', $data);

    $value = SystemSetting::get('test.json');

    expect($value)->toEqual($data);
    expect($value)->toBeArray();
});

test('typed value attribute handles different types', function () {
    $setting = SystemSetting::create([
        'key' => 'test.number',
        'value' => '100',
        'type' => 'number',
    ]);

    expect($setting->typed_value)->toBeInt();
    expect($setting->typed_value)->toEqual(100);
});

test('typed value attribute handles float', function () {
    $setting = SystemSetting::create([
        'key' => 'test.float',
        'value' => '3.14',
        'type' => 'number',
    ]);

    expect($setting->typed_value)->toBeFloat();
    expect($setting->typed_value)->toEqual(3.14);
});