<?php

namespace App;

use App\Helpers\CognitoHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MarketSubscriptionCount extends Model
{
    public function region() {
        return $this->hasOne('App\Market', 'id', 'market_id');
    }
}
