<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'image_path',
        'generated_prompt',
        'original_filename',
        'file_size',
        'mime_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
