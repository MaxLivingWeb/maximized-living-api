<?php

namespace App\Helpers;

use App\Location;
use Illuminate\Http\Request;

class LocationHelper
{
    /**
     * Current setting for filtering locations by 'enabled status'. Options are: 'enabled' (default), 'disabled', 'any'
     * @var null|bool
     */
    private $enabledStatus = null;

    /**
     * Default setting for filtering locations by 'enabled status'. Options are: 'enabled' (default), 'disabled', 'any'
     * @var string
     */
    protected $enabledStatusByDefault = 'enabled';

    /**
     * Current setting for getting Location UserGroup
     * @var null|bool
     */
    private $includeUserGroup = null;

    /**
     * Default setting for getting Location UserGroup
     * @var bool
     */
    protected $includeUserGroupByDefault = true;

    /**
     * Current setting for getting Location Addresses
     * @var null|bool
     */
    private $includeAddresses = null;

    /**
     * Default setting for getting Location Addresses
     * @var bool
     */
    protected $includeAddressesByDefault = false;

    /**
     * Current setting for condensing Location Addresses
     * @var null|bool
     */
    private $condensedAddresses = null;

    /**
     * Default setting for condensing Location Addresses
     * @var bool
     */
    protected $condensedAddressesByDefault = false;

    /**
     * LocationHelper constructor.
     */
    public function __construct()
    {
        $this->enabledStatus = $this->enabledStatusByDefault;
        $this->includeUserGroup = $this->includeUserGroupByDefault;
        $this->includeAddresses = $this->includeAddressesByDefault;
        $this->condensedAddresses = $this->condensedAddressesByDefault;
    }

    /**
     * Parse Request data which will then be used to get Location data
     * @param Request $request
     * @return $this
     */
    public function parseRequestData(Request $request)
    {
        $this->enabledStatus = $request->input('enabled_status');
        $this->includeUserGroup = $request->input('include_user_group');
        $this->includeAddresses = $request->input('include_addresses');
        $this->condensedAddresses = $request->input('condensed_addresses');

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

        if ($includeUserGroup) {
            // TODO - Include a method to condense UserGroup data?
            $location->user_group = $location->userGroup;
        }

        if ($includeAddresses) {
            if ($condensedAddresses) {
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
