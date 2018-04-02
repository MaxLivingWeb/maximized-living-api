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
    protected $enabledStatusByDefault = 'enabled';

    public function parseRequestData(Request $request)
    {
        $this->enabledStatus = $request->input('enabled_status') ?? $this->enabledStatusByDefault;

        return $this;
    }

    /**
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
                return Location::withTrashed()->where('deleted_at', '!=', null)->get();
                break;
            case 'enabled':
            default:
                return Location::all();
                break;
        }
    }
}
