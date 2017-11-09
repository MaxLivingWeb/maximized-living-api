<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class CityType extends GraphQLType
{
    protected $attributes = [
        'name' => 'City',
        'description' => 'A city within a region'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the city'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Name of the city'
            ],
            'addresses' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('Address')),
                'description' => 'addresses',
                'resolve' => function ($root, $args) {
                    return  $root->addresses ;
                }
            ]
        ];
    }
}
