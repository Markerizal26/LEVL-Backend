<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Common\Enums\SettingType;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'system_settings';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'key';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'string',
        'type' => SettingType::class,
    ];

    /**
     * Get the typed value based on the type field.
     */
    public function getTypedValueAttribute()
    {
        $type = $this->type instanceof SettingType ? $this->type->value : $this->type;

        return match ($type) {
            'number' => is_numeric($this->value) ? (str_contains($this->value, '.') ? (float) $this->value : (int) $this->value) : 0,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    /**
     * Set the value and automatically determine type if not specified.
     */
    public function setValue($value, SettingType|string|null $type = null): void
    {
        if ($type === null) {
            $type = $this->determineType($value);
        }

        // Convert string to enum if needed
        if (is_string($type)) {
            $type = SettingType::from($type);
        }

        $this->value = match ($type) {
            SettingType::Json => json_encode($value),
            SettingType::Boolean => $value ? '1' : '0',
            default => (string) $value,
        };

        $this->type = $type;
    }

    /**
     * Determine the type of value.
     */
    protected function determineType($value): SettingType
    {
        if (is_array($value) || is_object($value)) {
            return SettingType::Json;
        }

        if (is_bool($value)) {
            return SettingType::Boolean;
        }

        if (is_numeric($value)) {
            return SettingType::Number;
        }

        return SettingType::String;
    }

    /**
     * Get a setting by key.
     */
    public static function get(string $key, $default = null): mixed
    {
        $setting = static::find($key);

        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set a setting by key.
     */
    public static function set(string $key, $value): void
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => null, 'type' => SettingType::String]
        );

        if ($setting) {
            $setting->setValue($value);
            $setting->save();
        }
    }
}
