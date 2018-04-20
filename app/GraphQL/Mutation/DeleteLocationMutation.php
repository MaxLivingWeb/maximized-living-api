<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\Helpers\TrackingHelper;

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
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::int())],
        ];
    }

    public function resolve($root, $args)
    {
        $location = Location::with(['addresses.city.region', 'addresses.city.market'])
            ->where('id', $args['id'])
            ->firstOrFail();
        $location->delete();

        $trackingHelper = new TrackingHelper();
        if ($location->addresses[0]->city->region) {
            $regionId = $location->addresses[0]->city->region->id;
            $trackingHelper->updateRegionalCount($regionId);
        }

        if ($location->addresses[0]->city->market) {
            $marketId = $location->addresses[0]->city->market->id;
            $trackingHelper->updateMarketCount($marketId);
        }
    }
}
