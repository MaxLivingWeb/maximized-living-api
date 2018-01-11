<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class UserGroupType extends GraphQLType
{
    protected $attributes = [
        'name' => 'User Group',
        'description' => 'A user group'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'User group ID'
            ]
        ];
    }
}
