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
     * Parse Request data which will then be used to get Location data
     * @param Request $request
     * @return $this
     */
    public function parseRequestData(Request $request)
    {
        $this->enabledStatus = $request->input('enabled_status') ?? $this->enabledStatusByDefault;

        return $this;
    }

    /**
     * Get All Locations, based on the parameters
     * @param null|string $enabledStatus Options are: 'enabled' (default), 'disabled', 'any'
     * @return array
     */
    public function getAllLocations($enabledStatus = null)
    {
        $enabledStatus = $enabledStatus ?? $this->enabledStatus;

        $locations = $this->getLocationsByEnabledStatus($enabledStatus);

        return collect($locations)
            ->each(function($location){
                $location->user_group = $location->userGroup;
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
