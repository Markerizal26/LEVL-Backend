<?php

declare(strict_types=1);

use App\Support\ValidationRules\UploadSizeRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

it('accepts a regular file up to 2 MB', function (): void {
    $file = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => ['required', 'file', new UploadSizeRule()]],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects a regular file larger than 2 MB', function (): void {
    $file = UploadedFile::fake()->create('document.pdf', 2049, 'application/pdf');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => ['required', 'file', new UploadSizeRule()]],
    );

    expect($validator->fails())->toBeTrue();
});

it('accepts a video up to 50 MB', function (): void {
    $file = UploadedFile::fake()->create('lesson.mp4', 51200, 'video/mp4');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => ['required', 'file', new UploadSizeRule()]],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects a video larger than 50 MB', function (): void {
    $file = UploadedFile::fake()->create('lesson.mp4', 51201, 'video/mp4');

    $validator = Validator::make(
        ['file' => $file],
        ['file' => ['required', 'file', new UploadSizeRule()]],
    );

    expect($validator->fails())->toBeTrue();
});
