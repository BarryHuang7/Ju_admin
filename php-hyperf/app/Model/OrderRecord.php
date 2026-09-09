<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id 
 * @property string $ip 
 * @property string $user_id 
 * @property string $order_no 
 * @property string $order_name 
 * @property int $product_stock_id 
 * @property string $product_name 
 * @property string $product_number 
 * @property int $stock 
 * @property string $remark 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class OrderRecord extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'order_record';

    /**
     * The attributes that are mass assignable.
     * @var list<string>
     */
    protected array $fillable = [
        'ip',
        'user_id',
        'order_no',
        'order_name',
        'product_stock_id',
        'product_name',
        'product_number',
        'stock',
        'remark'
    ];

    /**
     * The attributes that should be cast to native types.
     * @var array<string, mixed>
     */
    protected array $casts = ['id' => 'integer', 'stock' => 'integer', 'product_stock_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
