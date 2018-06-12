<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;

class UpdateLocationSEMPageDesc extends Mutation
{
    protected $attributes = [
        'name' => 'updateLocationSEMPageDesc'
    ];

    public function type()
    {
        return GraphQL::type('Location');
    }

    public function args()
    {
        $locationType = new LocationType();
        return $locationType->fields();
    }

    public function resolve($root, $args)
    {

        $location = Location
            ::where(
                'vanity_website_id', $args['vanity_website_id']
            )
            ->update([
                'sem_page_desc' => $args['sem_page_desc']
            ]);

        return $args;
    }

}
