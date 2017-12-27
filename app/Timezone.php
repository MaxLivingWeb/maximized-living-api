<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timezone extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function locations() {
        return $this->hasMany('App\Location');
    }
}
