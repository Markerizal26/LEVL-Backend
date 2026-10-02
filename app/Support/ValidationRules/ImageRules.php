<?php

namespace App\Support\ValidationRules;


class ImageRules
{
    
    public static function avatar(): array
    {
        return [
            'required',
            'image',
            'mimes:jpeg,png,jpg,gif',
            'max:2048', 
        ];
    }

    
    public static function avatarOptional(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,gif',
            'max:2048',
        ];
    }

    
    public static function thumbnail(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg',
            'max:2048',
        ];
    }

    
    public static function banner(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg',
            'max:2048',
        ];
    }

    
    public static function content(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,gif,webp',
            'max:2048',
        ];
    }

    
    public static function profilePicture(): array
    {
        return [
            'required',
            'image',
            'mimes:jpeg,png,jpg',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ];
    }

    
    public static function icon(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,svg',
            'max:2048',
        ];
    }

    
    public static function custom(
        int $maxSizeKb = 2048,
        array $mimes = ['jpeg', 'png', 'jpg'],
        bool $required = false
    ): array {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:'.implode(',', $mimes),
            'max:'.$maxSizeKb,
        ];
    }
}
