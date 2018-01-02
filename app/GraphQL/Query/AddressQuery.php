<?php

namespace App\GraphQL\Query;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Query;
use App\Address;
use App\Location;
use DB;

class AddressQuery extends Query
{
    protected $attributes = [
        'name' => 'Address'
    ];

    public function type ()
    {
        return Type::listOf(GraphQL::type('OutputAddress'));
    }

    public function args ()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::int()
            ],
            'location_id' => [
                'name' => 'location_id',
                'type' => Type::int()
            ]
        ];
    }

    public function resolve ($root, $args)
    {
        if (isset($args['location_id'])) {

            return Location::find($args['location_id'])->addresses()->get();
        }

        return Address::all();
    }
}