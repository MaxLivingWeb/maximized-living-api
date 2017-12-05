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
            'filter_by_radius' => [
                'name' => 'filter_by_radius',
                'type' => Type::boolean(),
                'description' => 'boolean if you want to filter location by radius'
            ],
            'latitude' => [
                'type' => Type::float(),
                'description' => 'latitude of the search point'
            ],
            'longitude' => [
                'type' => Type::float(),
                'description' => 'longitude of the search point'
            ],
            'distance' => [
                'type' => Type::int(),
                'description' => 'the radius of your query'
            ],
            'country' => [ //country name
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
            'city' => [
                'name' => 'city',
                'type' => Type::string()
            ],
            'cityID' => [
                'name' => 'cityID',
                'type' => Type::int()
            ]
        ];
    }

    public function resolve ($root, $args)
    {
        //we have the parameters need for a filter by radius
        if($args['filter_by_radius'] === TRUE && isset($args['latitude']) && isset($args['longitude']) && isset($args['distance'])) {
            return Location::filterByRadius($args['latitude'], $args['longitude'], $args['distance']);
        }

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
                        return $q->where('id', filter_var($args['countryID'], FILTER_SANITIZE_STRING));
                    }

                    if (isset($args['countryCode'])) {
                        return $q->where('abbreviation', filter_var($args['countryCode'], FILTER_SANITIZE_STRING));
                    }

                    return $q->where('name', filter_var($args['country'], FILTER_SANITIZE_STRING));
                })
                ->get();
        }

        $regionFilters = [ 'region', 'regionCode', 'regionID' ];
        $hasRegionFilter = !empty(array_intersect(array_keys($args), $regionFilters));
        if ($hasRegionFilter) {
            return Location::with('addresses.city.region')
                ->whereHas('addresses.city.region', function ($q) use ($args) {
                    if (isset($args['regionID'])) {
                        return $q->where('id', filter_var($args['regionID'], FILTER_SANITIZE_STRING));
                    }

                    if (isset($args['regionCode'])) {
                        return $q->where('abbreviation', filter_var($args['regionCode'], FILTER_SANITIZE_STRING));
                    }

                    return $q->where('name', filter_var($args['region'], FILTER_SANITIZE_STRING));
                })
                ->get();
        }

        $cityFilters = [ 'city', 'cityID' ];
        $hasCityFilter = !empty(array_intersect(array_keys($args), $cityFilters));
        if ($hasCityFilter) {
            return Location::with('addresses.city')
                ->whereHas('addresses.city', function ($q) use ($args) {
                    if (isset($args['cityID'])) {
                        return $q->where('id', filter_var($args['cityID'], FILTER_SANITIZE_STRING));
                    }

                    return $q->where('name', filter_var($args['city'], FILTER_SANITIZE_STRING));
                })
                ->get();
        }
      
        return Location::all();
    }
}
