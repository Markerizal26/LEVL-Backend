<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'type', 'generated_by', 'filters', 'file_path', 'notes', 'generated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'generated_at' => 'datetime',
    ];

    public function generator()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'generated_by');
    }
}
