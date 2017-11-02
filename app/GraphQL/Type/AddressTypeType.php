<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class AddressTypeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Address Type',
        'description' => 'An type of address'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the region'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'name of the address type'
            ],
        ];
    }
}