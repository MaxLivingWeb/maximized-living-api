<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressType extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = ['name'];

    public function locations()
    {
        return $this->belongsToMany('App\Location');
    }

    public function addresses()
    {
        return $this->belongsToMany('App\Address', 'locations_addresses');
    }
}
