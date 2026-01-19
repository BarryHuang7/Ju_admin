<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $table = 'videos';

    protected $fillable = [
        'uuid',
        'original_name',
        'file_name',
        'path',
        'mime_type',
        'size',
        // 'duration',
        // 'width',
        // 'height',
        'chunks',
        'total_chunks',
        'status',
        // 'thumbnail_path'
    ];
}
