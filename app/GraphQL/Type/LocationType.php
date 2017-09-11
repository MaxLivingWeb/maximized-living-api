<?php

namespace App\GraphQL\Type;

use Rebing\GraphQL\Support\Type as GraphQLType;

class LocationType extends GraphQLType
{
    protected $attributes = [
        'name' => 'LocationType',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [

        ];
    }
}