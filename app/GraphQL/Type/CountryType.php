<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class CountryType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Country',
        'description' => 'A country'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the country'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Name of the country'
            ],
            'abbreviation' => [
                'type' => Type::string(),
                'description' => 'Abbreviation of the country'
            ]
        ];
    }
}