<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Region;
use DB;

class RegionQuery extends Query
{
    protected $attributes = [
        'name' => 'Region'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('Region'));
    }

    public function args ()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int()
            ],
            'abbreviation' => [
                'name' => 'abbreviation',
                'type' => Type::string()
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
            return Region::where('id', filter_var($args['id'], FILTER_SANITIZE_STRING) )->get();
        }

        if (isset($args['abbreviation'])) {
            return Region::where('abbreviation', filter_var($args['abbreviation'], FILTER_SANITIZE_STRING))->get();
        }

        if (isset($args['name'])) {
            return Region::where('name', filter_var($args['name'], FILTER_SANITIZE_STRING))->get();
        }

        return Region::all();
    }
}