<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use App\RegionalSubscriptionCount;
use App\MarketSubscriptionCount;

class TrackingHelper {
    public function updateRegionalCount($regionId) {
        $countQuery = 'select count(ug.id) as count from user_groups ug
            inner join locations l
                on l.id = ug.location_id
            inner join locations_addresses la
                on la.location_id = l.id
            inner join addresses a
                on a.id = la.address_id
            inner join cities c
                on c.id = a.city_id
            inner join regions r
                on r.id = c.region_id
            where r.id = ' . $regionId .
            ' and (ug.premium = true or ug.event_promoter = true)
              and l.deleted_at is not null;';
        $count = DB::select($countQuery)[0]->count;

        $regionalSubscriptionCount = new RegionalSubscriptionCount();
        $regionalSubscriptionCount->region_id = $regionId;
        $regionalSubscriptionCount->count = $count;
        $regionalSubscriptionCount->save();
    }

    public function updateMarketCount($marketId) {
        $countQuery = 'select count(ug.id) as count from user_groups ug
            inner join locations l
                on l.id = ug.location_id
            inner join locations_addresses la
                on la.location_id = l.id
            inner join addresses a
                on a.id = la.address_id
            inner join cities c
                on c.id = a.city_id
            inner join markets m
                on m.id = c.market_id
            where m.id = ' . $marketId .
            ' and (ug.premium = true or ug.event_promoter = true)
              and l.deleted_at is not null;';
        $count = DB::select($countQuery)[0]->count;

        $msc = new MarketSubscriptionCount();
        $msc->market_id = $marketId;
        $msc->count = $count;
        $msc->save();
    }
}
