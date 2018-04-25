<?php

namespace App\Helpers;

use App\Location;

class LocationHelper
{
    /**
     * Current setting for filtering locations by 'enabled status'. Options are: 'enabled' (default), 'disabled', 'any'
     * @var null|bool
     */
    private $enabledStatus = 'enabled';

    /**
     * Current setting for getting Location UserGroup
     * @var null|bool
     */
    private $includeUserGroup = true;

    /**
     * Current setting for getting Location Addresses
     * @var null|bool
     */
    private $includeAddresses = false;

    /**
     * Current setting for condensing Location Addresses
     * @var null|bool
     */
    private $condensedAddresses = false;

    /**
     * Parse Request data which will then be used to get Location data
     * @param array $params
     * @return $this
     */
    public function parseData(array $params)
    {
        if (array_key_exists('enabled_status', $params)) {
            $this->enabledStatus = $params['enabled_status'];
        }

        if (array_key_exists('include_user_group', $params)) {
            $this->includeUserGroup = filter_var($params['include_user_group'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('include_addresses', $params)) {
            $this->includeAddresses = filter_var($params['include_addresses'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('condensed_addresses', $params)) {
            $this->condensedAddresses = filter_var($params['condensed_addresses'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this;
    }

    /**
     * Format single Location's data based on the provided params
     * @param null|\App\Location $location
     * @param null|bool $includeUserGroup If null, will default to the includeUserGroupByDefault value
     * @param null|bool $includeAddresses If null, will default to the includeAddressesByDefault value
     * @param null|bool $condensedAddresses If null, will default to the condensedAddressesByDefault value. ALSO, $includeAddresses must be set to true for this parameter to take affect.
     * @return \stdClass
     */
    public function formatLocationData(
        $location,
        $includeUserGroup = null,
        $includeAddresses = null,
        $condensedAddresses = null
    ) {
        if (empty($location)) {
            return;
        }

        $includeUserGroup = $includeUserGroup ?? $this->includeUserGroup;
        $includeAddresses = $includeAddresses ?? $this->includeAddresses;
        $condensedAddresses = $condensedAddresses ?? $this->condensedAddresses;

        if ((bool)$includeUserGroup) {
            // TODO - Include a method to condense UserGroup data?
            $location->user_group = $location->userGroup;
        }

        if ((bool)$includeAddresses) {
            if ((bool)$condensedAddresses) {
                $location->addresses = $location->addresses
                    ->transform(function($address){
                        $simplifiedAddress = $address
                            ->only([
                                'id',
                                'address_1',
                                'address_2',
                                'zip_postal_code',
                                'region',
                                'country',
                                'city'
                            ]);

                        return collect($simplifiedAddress)
                            ->transform(function($value, $key){
                                if ($key === 'region' || $key === 'country') {
                                    return $value->only(['id', 'name', 'abbreviation']);
                                }

                                if ($key == 'city') {
                                    return $value->only(['id', 'name']);
                                }

                                return $value;
                            });
                    })
                    ->all();
            }
            else {
                $location->addresses = $location->addresses;
            }
        }

        return $location;
    }

    /**
     * Get All Locations, based on the parameters
     * @param null|string $enabledStatus Options are: 'enabled' (default), 'disabled', 'any'
     * @param null|bool $includeUserGroup
     * @param null|bool $includeAddresses
     * @param null|bool $condensedAddresses If null, will default to the condensedAddressesByDefault value. ALSO, $includeAddresses must be set to true for this parameter to take affect.
     * @return array
     */
    public function getAllLocations(
        $enabledStatus = null,
        $includeUserGroup = null,
        $includeAddresses = null,
        $condensedAddresses = null
    ){
        $enabledStatus = $enabledStatus ?? $this->enabledStatus;
        $includeUserGroup = $includeUserGroup ?? $this->includeUserGroup;
        $includeAddresses = $includeAddresses ?? $this->includeAddresses;
        $condensedAddresses = $condensedAddresses ?? $this->condensedAddresses;

        $locations = $this->getLocationsByEnabledStatus($enabledStatus);

        return collect($locations)
            ->transform(function($location) use($includeUserGroup, $includeAddresses, $condensedAddresses){
                return $this->formatLocationData(
                    $location,
                    $includeUserGroup,
                    $includeAddresses,
                    $condensedAddresses
                );
            });
    }

    /**
     * Get Locations based on the provided $enabledStatus parameter
     * @param string $enabledStatus Options are: 'enabled' (default), 'disabled', 'any'
     * @return array
     */
    public function getLocationsByEnabledStatus($enabledStatus)
    {
        switch ($enabledStatus) {
            case 'any':
                return Location::withTrashed()->get();
                break;
            case 'disabled':
                return Location::onlyTrashed()->get();
                break;
            case 'enabled':
            default:
                return Location::all();
                break;
        }
    }
}
