<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanceActivity extends Model
{
    protected $fillable = [
        'instance_id',
        'action',
        'details'
    ];

    protected $casts = [
        'details' => 'array'
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
