<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\Location;

class DeleteLocationMutation extends Mutation
{
    protected $attributes = [
        'name' => 'deleteLocation'
    ];

    public function type()
    {
        return GraphQL::type('Location');
    }

    public function args()
    {
        //accepts an id as an argument
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::int())],
        ];
    }

    public function resolve($root, $args)
    {
        //delete the location passed in as an argument
        $location = Location::find($args['id']);

        $location->delete();
    }

}