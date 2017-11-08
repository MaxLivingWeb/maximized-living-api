<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Location;
use DB;

DB::enableQueryLog();

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
            'country' => [ //country name
                'name' => 'country',
                'type' => Type::string()
            ],
            'countryCode' => [ //two digit country abbreviation
                'name' => 'countryCode',
                'type' => Type::string()
            ],
            'region' => [ //region name
                'name' => 'region',
                'type' => Type::string()
            ],
            'regionCode' => [ //two digit region abbreviation
                'name' => 'regionCode',
                'type' => Type::string()
            ],
            'city' => [ //city name
                'name' => 'city',
                'type' => Type::string()
            ],
            'cityID' => [ //city id
                'name' => 'cityID',
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

        //query in browser: base_url.com/graphql?query=query+query{locations(country:"Canada"){name}}
        if (isset($args['country'])) {
            return Location::with('addresses.city.region.country')
                ->whereHas('addresses.city.region.country', function ($q) use ($args) {
                    $q->where('name', $args['country']);
                })->get();
        }

        //query in browser: base_url.com/graphql?query=query+query{locations(countryCode:"CA"){name}}
        if (isset($args['countryCode'])) {
            return Location::with('addresses.city.region.country')
                ->whereHas('addresses.city.region.country', function ($q) use ($args) {
                    $q->where('abbreviation', $args['countryCode']);
                })->get();
        }

        //query in browser: base_url.com/graphql?query=query+query{locations(region:"Ontario"){name}}
        if (isset($args['region'])) {
            return Location::with('addresses.city.region')
                ->whereHas('addresses.city.region', function ($q) use ($args) {
                    $q->where('name', $args['region']);
                })->get();
        }

        //query in browser: base_url.com/graphql?query=query+query{locations(regionCode:"ON"){name}}
        if (isset($args['regionCode'])) {
            return Location::with('addresses.city.region')
                ->whereHas('addresses.city.region', function ($q) use ($args) {
                    $q->where('abbreviation', $args['regionCode']);
                })->get();
        }

        //query in browser: base_url.com/graphql?query=query+query{locations(city:"Toronto"){name}}
        if (isset($args['city'])) {
            return Location::with('addresses.city')
                ->whereHas('addresses.city', function ($q) use ($args) {
                    $q->where('name', $args['city']);
                })->get();
        }

        //query in browser: base_url.com/graphql?query=query+query{locations(cityID:2){name}}
        if (isset($args['cityID'])) {
            return Location::with('addresses.city')
                ->whereHas('addresses.city', function ($q) use ($args) {
                    $q->where('id', $args['cityID']);
                })->get();
        }

        return Location::all();
    }
}