<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class AddressType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Address',
        'description' => 'An address'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the region'
            ],
            'address_1' => [
                'type' => Type::string(),
                'description' => 'address 1'
            ],
            'address_2' => [
                'type' => Type::string(),
                'description' => 'address 2'
            ],
            'type' => [
                'type' => Type::listOf(GraphQL::type('AddressType')),
                'description' => 'locations associated with an address',
                'resolve' => function ($root, $args) {
                    return $root->types;
                }
            ],
            'locations' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('City')),
                'description' => 'locations associated with an address',
                'resolve' => function ($root, $args) {
                    return  $root->locations;
                }
            ]
        ];
    }
}