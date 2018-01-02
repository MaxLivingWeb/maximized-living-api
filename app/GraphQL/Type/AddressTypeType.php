<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class AddressTypeType extends GraphQLType
{
    protected $attributes = [
        'name' => 'AddressType',
        'description' => 'An address type'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::int(),
                'description' => 'ID of an address type'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'name'
            ]
        ];
    }
}
