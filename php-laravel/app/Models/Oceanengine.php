<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oceanengine extends Model
{
    protected $table = 'oceanengine_open_platform';

    protected $fillable = [
        'auth_code',
        'scope',
        'material_auth_status',
        'state',
        'uid',
        'access_token',
        'refresh_token',
        'cc_account_id',
        'cc_account_name',
        'account_role',
        'is_valid'
    ];
}
