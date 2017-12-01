<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommissionGroup extends Model
{
    protected $fillable = ['percentage', 'description', 'store_tax_number'];
}
