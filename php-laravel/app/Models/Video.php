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
        'index_path',
        'path',
        'mime_type',
        'size',
        'chunks',
        'total_chunks',
        'status'
    ];

    /**
     * 需要被转换成日期的属性。
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 为日期属性设置统一的序列化格式
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
