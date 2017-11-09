<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class RegionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Region',
        'description' => 'A region within a country'
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
                'description' => 'Name of the region'
            ],
            'abbreviation' => [
                'type' => Type::string(),
                'description' => 'Abbreviation of the region'
            ],
            'cities' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('City')),
                'description' => 'cities',
                'resolve' => function ($root, $args) {
                    return  $root->cities ;
                }
            ]
        ];
    }
}