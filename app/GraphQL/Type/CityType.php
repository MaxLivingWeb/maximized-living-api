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
            'slug' => [
                'type' => Type::string(),
                'description' => 'Slug of the city'
            ],
            'region' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('Region')),
                'description' => 'regions',
                'resolve' => function ($root, $args) {
                    return  [ $root->region ] ;
                }
            ],
            'addresses' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('OutputAddress')),
                'description' => 'addresses',
                'resolve' => function ($root, $args) {
                    return  $root->addresses ;
                }
            ]
        ];
    }
}
