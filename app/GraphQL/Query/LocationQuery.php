<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Location;
use DB;

class LocationQuery extends Query
{
    protected $attributes = [
        'name' => 'Location'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('Location'));
    }

    public function args ()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int()
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string()
            ],
            'country' => [
                'name' => 'country',
                'type' => Type::string()
            ],
            'countryCode' => [
                'name' => 'countryCode',
                'type' => Type::string()
            ],
            'countryID' => [
                'name' => 'countryID',
                'type' => Type::int()
            ],
            'region' => [
                'name' => 'region',
                'type' => Type::string()
            ],
            'regionCode' => [
                'name' => 'regionCode',
                'type' => Type::string()
            ],
            'regionID' => [
                'name' => 'regionID',
                'type' => Type::int()
            ],
        ];
    }

    public function resolve ($root, $args)
    {
        if (isset($args['id'])) {
            return Location::where('id', $args['id'])->get();
        }

        if (isset($args['slug'])) {
            return Location::where('slug', $args['slug'])->get();
        }

        $countryFilters = [ 'country', 'countryCode', 'countryID' ];
        $hasCountryFilter = !empty(array_intersect(array_keys($args), $countryFilters));
        if ($hasCountryFilter) {
            return Location::with('addresses.city.region.country')
                ->whereHas('addresses.city.region.country', function ($q) use ($args) {
                    if (isset($args['countryID'])) {
                        return $q->where('id', $args['countryID']);
                    }

                    if (isset($args['countryCode'])) {
                        return $q->where('abbreviation', $args['countryCode']);
                    }

                    return $q->where('name', $args['country']);
                })
                ->get();
        }

        $regionFilters = [ 'region', 'regionCode', 'regionID' ];
        $hasRegionFilter = !empty(array_intersect(array_keys($args), $regionFilters));
        if ($hasRegionFilter) {
            return Location::with('addresses.city.region')
                ->whereHas('addresses.city.region', function ($q) use ($args) {
                    if (isset($args['regionID'])) {
                        return $q->where('id', $args['regionID']);
                    }

                    if (isset($args['regionCode'])) {
                        return $q->where('abbreviation', $args['regionCode']);
                    }

                    return $q->where('name', $args['region']);
                })
                ->get();
        }
      
        return Location::all();
    }
}
