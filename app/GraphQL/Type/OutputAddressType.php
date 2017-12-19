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

}
