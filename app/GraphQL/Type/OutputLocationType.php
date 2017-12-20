<?php

namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class OutputLocationType extends LocationType
{
    protected $inputObject = false;

    protected $attributes = [
        'name' => 'OutputLocation',
        'description' => 'A type location type for outputting'
    ];

    public function fields() {

        $fields = parent::fields();

        $fields['addresses']  = [
            'name' => 'addresses',
            'type' => Type::listOf(GraphQL::type('OutputAddress'))
        ];

        return $fields;
    }
}
