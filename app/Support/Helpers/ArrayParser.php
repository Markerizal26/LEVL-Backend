<?php

namespace App\Support\Helpers;

/**
 * Array Parser Helper
 *
 * Provides utilities for parsing various array input formats,
 * particularly for filter parameters that may come as JSON strings,
 * URL-encoded arrays, or native arrays.
 */
class ArrayParser
{
    /**
     * Parse filter value that may be array, JSON string, or URL-encoded.
     *
     * Handles multiple input formats:
     * - Native PHP array: ['value1', 'value2']
     * - JSON string: '["value1", "value2"]'
     * - URL-encoded JSON: '%5B%22value1%22%2C%22value2%22%5D'
     * - Single value string: 'value1'
     * - Empty string: ''
     *
     * @param  mixed  $value  The input value to parse
     * @return array Parsed array of values
     *
     * @example
     * ```php
     * ArrayParser::parseFilter(['tag1', 'tag2']); // ['tag1', 'tag2']
     * ArrayParser::parseFilter('["tag1","tag2"]'); // ['tag1', 'tag2']
     * ArrayParser::parseFilter('tag1'); // ['tag1']
     * ArrayParser::parseFilter(''); // []
     * ```
     */
    public static function parseFilter($value): array
    {
        // If already an array, return as-is
        if (is_array($value)) {
            return $value;
        }

        // Handle string values
        if (is_string($value)) {
            $trim = trim($value);

            // Empty string returns empty array
            if ($trim === '') {
                return [];
            }

            // Check if it's a JSON array (starts with [ or %5B for URL-encoded)
            if ($trim[0] === '[' || str_starts_with($trim, '%5B')) {
                // Try to decode as JSON
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                // If failed, try URL-decoding first then JSON decode
                $urldecoded = urldecode($trim);
                $decoded = json_decode($urldecoded, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            // If not JSON, treat as single value
            return [$trim];
        }

        // For any other type, return empty array
        return [];
    }

    /**
     * Parse comma-separated string into array.
     *
     * @param  string|array  $value  Comma-separated string or array
     * @param  bool  $trimValues  Whether to trim whitespace from values
     * @return array
     *
     * @example
     * ```php
     * ArrayParser::parseCommaSeparated('tag1, tag2, tag3'); // ['tag1', 'tag2', 'tag3']
     * ArrayParser::parseCommaSeparated('tag1,tag2,tag3'); // ['tag1', 'tag2', 'tag3']
     * ```
     */
    public static function parseCommaSeparated($value, bool $trimValues = true): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $parts = explode(',', $value);

        if ($trimValues) {
            return collect($parts)->map(fn($item) => trim($item))->all();
        }

        return $parts;
    }

    /**
     * Ensure value is an array, wrap scalar values.
     *
     * @param  mixed  $value
     * @return array
     *
     * @example
     * ```php
     * ArrayParser::ensureArray('value'); // ['value']
     * ArrayParser::ensureArray(['value']); // ['value']
     * ArrayParser::ensureArray(null); // []
     * ```
     */
    public static function ensureArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    /**
     * Parse pipe-separated string into array (useful for Laravel validation).
     *
     * @param  string|array  $value  Pipe-separated string or array
     * @return array
     *
     * @example
     * ```php
     * ArrayParser::parsePipeSeparated('option1|option2|option3'); // ['option1', 'option2', 'option3']
     * ```
     */
    public static function parsePipeSeparated($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return explode('|', $value);
    }
}
