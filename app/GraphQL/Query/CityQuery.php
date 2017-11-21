<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\City;
use DB;

class CityQuery extends Query
{
    protected $attributes = [
        'name' => 'City'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('City'));
    }

    public function args ()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int()
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string()
            ]
        ];
    }

    public function resolve ($root, $args)
    {
        if (isset($args['id'])) {
            return City::where('id', $args['id'])->get();
        }

        if (isset($args['name'])) {
            return City::where('name', $args['name'])->get();
        }

        return City::all();
    }
}