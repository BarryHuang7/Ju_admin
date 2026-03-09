<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileList extends Model
{
    protected $table = 'file_list';

    protected $fillable = [
        'title',
        'content',
        'file_name',
        'file_url',
        'file_date',
        'is_admin'
    ];

    /**
     * 为日期属性设置统一的序列化格式
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
