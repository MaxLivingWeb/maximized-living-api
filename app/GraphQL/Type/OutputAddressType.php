<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class OutputAddressType extends AddressType
{
    protected $inputObject = false;

    protected $attributes = [
        'name' => 'OutputAddress',
        'description' => 'An address for outputting'
    ];

    public function fields() {
        $fields = parent::fields();

        $fields['city'] = [
            'args' => [
                'id' => [
                    'type' => Type::int(),
                    'name' => 'id'
                ]
            ],
            'type' => Type::listOf(GraphQL::type('City')),
            'description' => 'cities',
            'resolve' => function ($root, $args) {
                return  [ $root->city ] ;
            }
        ];

        $fields['locations'] = [
            'args' => [
                'id' => [
                    'type' => Type::int(),
                    'name' => 'id'
                ]
            ],
            'type' => Type::listOf(GraphQL::type('OutputLocation')),
            'description' => 'locations',
            'resolve' => function ($root, $args) {
                return  $root->locations ;
            }
        ];

        return $fields;
    }

}
