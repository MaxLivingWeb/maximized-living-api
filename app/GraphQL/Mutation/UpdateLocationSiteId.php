<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;

class UpdateLocationSiteID extends Mutation
{
    protected $attributes = [
        'name' => 'updateLocationSiteID'
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
                'id', $args['id']
            )
            ->update([
                'vanity_website_id' => $args['vanity_website_id']
            ]);

        return $args;
    }

}
