<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Region;
use DB;

DB::enableQueryLog();

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
            return Region::where('id', $args['id'])->get();
        }

        if (isset($args['abbreviation'])) {
            return Region::where('abbreviation', $args['abbreviation'])->get();
        }

        if (isset($args['name'])) {
            return Region::where('name', $args['name'])->get();
        }

        return Region::all();
    }
}