<?php

namespace App\Http\Controllers;

use App\{Location,UserGroup};
use App\Helpers\CognitoHelper;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get ALL Locations
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        $locations = Location::all();

        return collect($locations)
            ->each(function($location){
                $location->user_group = $location->userGroup;
            });
    }

    /**
     * Retrieves a list of all Cognito users associated with a given location.
     * @param Request $request
     * @param integer $id The ID of the location to retrieve users for.
     * @return array
     */
    public function getUsersById(Request $request, $id)
    {
        $location = Location::with('userGroup')->findOrFail($id);
        $enabledStatus = $request->input('enabled_status') ?? null;
        return response()->json($location->listUsers($enabledStatus));
    }

    /**
     * Get UserGroup for this Location
     * @param $id
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|void
     */
    public function getUserGroupById($id)
    {
        $location = Location::with('userGroup')->findOrFail($id);

        if (empty($location->userGroup)) {
            return;
        }

        return UserGroup::with(['location', 'commission'])->findOrFail($location->userGroup->id);
    }

    /**
     * Use Soft Deletes to deactivate this Location, and any associated users as well
     * @param $id
     */
    public function deactivateLocation($id)
    {
        $cognito = new CognitoHelper();
        $location = Location::with('userGroup')->findOrFail($id);

        // No Affiliate/Clinic data set up for this Location, no users can be associated to this Location...
        if (empty($location->userGroup)) {
            $location->delete();
            return response()->json();
        }

        $userGroup = UserGroup::findOrFail($location->userGroup->id);
        $users = $userGroup->listUsers('any');

        // Delete this Location, and the associated Users within the Location's UserGroup
        try {
            collect($users)->each(function($user) use($cognito){
                $cognito->deactivateUser($user->id);
            });

            $location->delete();

            return response()->json();
        }
        // Rollback, and set all users to be Enabled again
        catch (AwsException $e) {
            collect($users)->each(function($user) use($cognito){
                $cognito->activateUser($user->id);
            });
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch(\Exception $e) {
            collect($users)->each(function($user) use($cognito){
                $cognito->activateUser($user->id);
            });
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Reactivate this Location, and any associated users as well
     * @param $id
     */
    public function reactivateLocation($id)
    {
        $cognito = new CognitoHelper();
        $location = Location::withTrashed()->with('userGroup')->findOrFail($id); //retreive items even with 'deleted_at' status

        // No Affiliate/Clinic data set up for this Location, no users can be associated to this Location...
        if (empty($location->userGroup)) {
            $location->restore();
            return response()->json();
        }

        $userGroup = UserGroup::findOrFail($location->userGroup->id);
        $users = $userGroup->listUsers('any');

        try {
            // Activate all Users in this UserGroup
            collect($users)->each(function($user) use($cognito){
                $cognito->activateUser($user->id);
            });

            // Restore from soft deletion
            $location->restore();

            return response()->json();
        }
        // Rollback, and set all users to be Disabled again
        catch (AwsException $e) {
            collect($users)->each(function($user) use($cognito){
                $cognito->deactivateUser($user->id);
            });
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch(\Exception $e) {
            collect($users)->each(function($user) use($cognito){
                $cognito->deactivateUser($user->id);
            });
            return response()->json($e->getMessage(), 500);
        }
    }
}
