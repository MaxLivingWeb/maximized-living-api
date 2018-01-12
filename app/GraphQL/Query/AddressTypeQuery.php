<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\AddressType;
use DB;

class AddressTypeQuery extends Query
{
    protected $attributes = [
        'name' => 'AddressType'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('AddressType'));
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
        return AddressType::all();
    }
}