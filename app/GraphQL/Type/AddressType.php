<?php
namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class AddressType extends GraphQLType
{
    protected $inputObject = true;

    protected $attributes = [
        'name' => 'Address',
        'description' => 'An address'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::int(),
                'description' => 'ID of the region'
            ],
            'shopify_id' => [
                'type' => Type::int(),
                'description' => 'Shopify Address ID'
            ],
            'address_1' => [
                'type' => Type::string(),
                'description' => 'address 1'
            ],
            'address_2' => [
                'type' => Type::string(),
                'description' => 'address 2'
            ],
            'latitude' => [
                'type' => Type::float(),
                'description' => 'Latitude of location'
            ],
            'longitude' => [
                'type' => Type::float(),
                'description' => 'Longitude of location'
            ],
            'region' => [
                'name' => 'region',
                'type' => Type::string()
            ],
            'city' => [
                'name' => 'city',
                'type' => Type::string()
            ],
            'country' => [
                'name' => 'country',
                'type' => Type::string()
            ],
            'address_type' => [
                'name' => 'address_type',
                'type' => Type::int()
            ],
            'zip_postal_code' => [
                'type' => Type::string(),
                'description' => 'Zip/Postal Code'
            ]
        ];
    }
}
