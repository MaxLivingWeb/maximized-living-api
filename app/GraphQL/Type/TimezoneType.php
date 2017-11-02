<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class TimezoneType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Timezone',
        'description' => 'A timezone'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the timezone'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Name of the timezone'
            ]
        ];
    }
}