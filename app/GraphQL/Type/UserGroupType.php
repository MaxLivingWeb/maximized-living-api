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
            ],
            'wholesaler' => [
                'type' => Type::nonNull(Type::int()),
                'description' => "User group's wholesaler status. If true, user receives wholesale discounts through the store"
            ],
            'premium' => [
                'type' => Type::nonNull(Type::int()),
                'description' => "User group's premium status"
            ],
            'event_promoter' => [
                'type' => Type::nonNull(Type::int()),
                'description' => "User group's event promoter status"
            ]
        ];
    }
}
