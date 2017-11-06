<?php


use example\Mutation\ExampleMutation;
use example\Query\ExampleQuery;

use App\GraphQL\Type\LocationType;

return [

    // The prefix for routes
    'prefix' => 'graphql',

    // The routes to make GraphQL request. Either a string that will apply
    // to both query and mutation or an array containing the key 'query' and/or
    // 'mutation' with the according Route
    //
    // Example:
    //
    // Same route for both query and mutation
    //
    // 'routes' => 'path/to/query/{graphql_schema?}',
    //
    // or define each route
    //
    // 'routes' => [
    //     'query' => 'query/{graphql_schema?}',
    //     'mutation' => 'mutation/{graphql_schema?}',
    // ]
    //
    'routes' => '{graphql_schema?}',

    // The controller to use in GraphQL request. Either a string that will apply
    // to both query and mutation or an array containing the key 'query' and/or
    // 'mutation' with the according Controller and method
    //
    // Example:
    //
    // 'controllers' => [
    //     'query' => '\Rebing\GraphQL\GraphQLController@query',
    //     'mutation' => '\Rebing\GraphQL\GraphQLController@mutation'
    // ]
    //
    'controllers' => \Folklore\GraphQL\GraphQLController::class . '@query',

    // Any middleware for the graphql route group
    'middleware' => [],

    // The name of the default schema used when no argument is provided
    // to GraphQL::schema() or when the route is used without the graphql_schema
    // parameter.
    'default_schema' => 'default',

    // The schemas for query and/or mutation. It expects an array of schemas to provide
    // both the 'query' fields and the 'mutation' fields.
    //
    // You can also provide a middleware that will only apply to the given schema
    //
    // Example:
    //
    //  'schema' => 'default',
    //
    //  'schemas' => [
    //      'default' => [
    //          'query' => [
    //              'users' => 'App\GraphQL\Query\UsersQuery'
    //          ],
    //          'mutation' => [
    //
    //          ]
    //      ],
    //      'user' => [
    //          'query' => [
    //              'profile' => 'App\GraphQL\Query\ProfileQuery'
    //          ],
    //          'mutation' => [
    //
    //          ],
    //          'middleware' => ['auth'],
    //      ]
    //  ]
    //
    'schemas' => [
        'default' => [
            'query' => [
                'countries' => 'App\GraphQL\Query\CountryQuery',
                'regions' => 'App\GraphQL\Query\RegionQuery',
                'cities' => 'App\GraphQL\Query\CityQuery',
                'locations' => 'App\GraphQL\Query\LocationQuery',
            ],
            'mutation' => [
                'addLocation' => 'App\GraphQL\Mutation\AddLocationMutation',
                'updateLocation' => 'App\GraphQL\Mutation\UpdateLocationMutation',
                'deleteLocation' => 'App\GraphQL\Mutation\DeleteLocationMutation',
            ]
        ],
    ],
    
    // The types available in the application. You can then access it from the
    // facade like this: GraphQL::type('user')
    //
    // Example:
    //
    // 'types' => [
    //     'user' => 'App\GraphQL\Type\UserType'
    // ]
    //
    'types' => [
        'Country' => 'App\GraphQL\Type\CountryType',
        'Region' => 'App\GraphQL\Type\RegionType',
        'City' => 'App\GraphQL\Type\CityType',
        'Location' => 'App\GraphQL\Type\LocationType',
        'Timezone' => 'App\GraphQL\Type\TimezoneType',
        'Address' => 'App\GraphQL\Type\AddressType',
        'AddressType' => 'App\GraphQL\Type\AddressTypeType'
    ],
    
    // This callable will be passed the Error object for each errors GraphQL catch.
    // The method should return an array representing the error.
    // Typically:
    // [
    //     'message' => '',
    //     'locations' => []
    // ]
    'error_formatter' => ['\Folklore\GraphQL\GraphQL', 'formatError'],

    // You can set the key, which will be used to retrieve the dynamic variables
    'params_key'    => 'params',
    
];
