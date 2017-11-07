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

        return Location::all();
    }
}