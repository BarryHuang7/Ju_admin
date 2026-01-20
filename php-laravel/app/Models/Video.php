<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    // 软删除
    use SoftDeletes;

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

    /**
     * 需要被转换成日期的属性。
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
