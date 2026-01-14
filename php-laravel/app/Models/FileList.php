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
}
