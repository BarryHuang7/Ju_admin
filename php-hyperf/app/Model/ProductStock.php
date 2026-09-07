<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id 
 * @property string $product_name 
 * @property string $product_number 
 * @property int $stock 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $deleted_at 
 */
class ProductStock extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'product_stock';

    /**
     * The attributes that are mass assignable.
     * @var list<string>
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     * @var array<string, mixed>
     */
    protected array $casts = ['id' => 'integer', 'stock' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
