<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Laravel;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['data' => 'array'];
}
