<?php

namespace Webkul\Discount\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventorySource as InventorySourceContract;

class CatalogRule extends Model implements InventorySourceContract
{
    protected $guarded = ['created_at', 'updated_at'];
}