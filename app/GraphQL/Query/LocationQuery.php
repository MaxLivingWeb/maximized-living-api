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
                'description' => 'latitude'
            ],
            'longitude' => [
                'type' => Type::float(),
                'description' => 'longitude'
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

            $lat = $args['latitude'];
            $long = $args['longitude'];
            $distance = $args['distance'];
            $filtered_locations = DB::select("SELECT * FROM Locations WHERE acos(sin(1.3963) * sin($lat) + cos(1.3963) * cos($lat) * cos($long - (-0.6981))) * 6371 <= $distance");

            return $filtered_locations;
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