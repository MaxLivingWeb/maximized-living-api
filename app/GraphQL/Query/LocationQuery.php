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
            'countryID' => [ //country ID
                'name' => 'countryID',
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

        //query in browser: base_url.com/graphql?query=query+query{locations(countryID:1){name}}
        if (isset($args['countryID'])) {
            return Location::with('addresses.city.region.country')
                ->whereHas('addresses.city.region.country', function ($q) use ($args) {
                    $q->where('id', $args['countryID']);
                })->get();
        }

        return Location::all();
    }
}