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

    public function __construct()
    {
        $this->enabledStatus = $this->enabledStatusByDefault;
        $this->includeUserGroup = $this->includeUserGroupByDefault;
        $this->includeAddresses = $this->includeAddressesByDefault;
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

        return $this;
    }

    /**
     * Format single Location's data based on the provided params
     * @param null|\App\Location $location
     * @param null|bool $includeUserGroup If null, will default to the includeUserGroupByDefault value
     * @param null|bool $includeAddresses If null, will default to the includeAddressesByDefault value
     * @return \stdClass
     */
    public function formatLocationData(
        $location,
        $includeUserGroup = null,
        $includeAddresses = null
    ) {
        if (empty($location)) {
            return;
        }

        $includeUserGroup = $includeUserGroup ?? $this->includeUserGroup;
        $includeAddresses = $includeAddresses ?? $this->includeAddresses;

        if ($includeUserGroup) {
            $location->user_group = $location->userGroup;
        }

        if ($includeAddresses) {
            $location->addresses = $location->addresses;
        }

        return $location;
    }

    /**
     * Get All Locations, based on the parameters
     * @param null|string $enabledStatus Options are: 'enabled' (default), 'disabled', 'any'
     * @param null|bool $includeUserGroup
     * @param null|bool $includeAddresses
     * @return array
     */
    public function getAllLocations(
        $enabledStatus = null,
        $includeUserGroup = null,
        $includeAddresses = null
    ){
        $enabledStatus = $enabledStatus ?? $this->enabledStatus;
        $includeUserGroup = $includeUserGroup ?? $this->includeUserGroup;
        $includeAddresses = $includeAddresses ?? $this->includeAddresses;

        $locations = $this->getLocationsByEnabledStatus($enabledStatus);

        return collect($locations)
            ->transform(function($location) use($includeUserGroup, $includeAddresses){
                return $this->formatLocationData(
                    $location,
                    $includeUserGroup,
                    $includeAddresses
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
