<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    ];

    /**
     * Get the typed value based on the type field.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'number' => is_numeric($this->value) ? (str_contains($this->value, '.') ? (float) $this->value : (int) $this->value) : 0,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    /**
     * Set the value and automatically determine type if not specified.
     */
    public function setValue($value, $type = null): void
    {
        if ($type === null) {
            $type = $this->determineType($value);
        }

        $this->value = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $this->type = $type;
    }

    /**
     * Determine the type of value.
     */
    protected function determineType($value): string
    {
        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        return 'string';
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
            ['value' => null, 'type' => 'string']
        );
        
        if ($setting) {
            $setting->setValue($value);
            $setting->save();
        }
    }
}

