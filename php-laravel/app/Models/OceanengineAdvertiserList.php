<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OceanengineAdvertiserList extends Model
{
    protected $table = 'oceanengine_advertiser_list';

    protected $fillable = [
        'oceanengine_id',
        'advertiser_id',
        'advertiser_name',
        'advertiser_type',
        'company',
        'first_industry_name',
        'second_industry_name'
    ];
}
