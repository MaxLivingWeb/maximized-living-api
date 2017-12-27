<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Country;
use DB;

class CountryQuery extends Query
{
    protected $attributes = [
        'name' => 'Country'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('Country'));
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
            ]
        ];
    }

    public function resolve ($root, $args)
    {
        if (isset($args['id'])) {
            return Country::where('id', filter_var($args['id'], FILTER_SANITIZE_STRING) )->get();
        }

        if (isset($args['abbreviation'])) {
            return Country::where('abbreviation', filter_var($args['abbreviation'], FILTER_SANITIZE_STRING))->get();
        }

        return Country::all();
    }
}