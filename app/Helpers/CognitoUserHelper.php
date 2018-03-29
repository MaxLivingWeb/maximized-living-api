<?php

namespace App\Helpers;

use App\Helpers\CognitoHelper;
use GuzzleHttp\Exception\ClientException;
use Aws\Exception\AwsException;

class CognitoUserHelper
{
    /**
     * List Users from Cognito
     * @param null|string $groupName Get Cognito users by a specific UserGroup. To get ALL Cognito users, enter "ALL_COGNITO_USERS"
     * @param null|string $enabledStatus Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @param null|\Carbon\Carbon $createdOnDate Carbonized Date - User was created exactly on this date ("yyyy-mm-dd")
     * @param null|\Carbon\Carbon $createdBeforeDate Carbonized Date - User was created before this date ("yyyy-mm-dd")
     * @param null|\Carbon\Carbon $createdAfterDate Carbonized Date - User was created after this date ("yyyy-mm-dd")
     * @param null|array $permissions List of user permissions
     * @param bool $sendbackResultAsJSON (Sendback result as JSON format)
     * @param bool $condensed (Sendback condensed user data)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public static function listUsers(
        $groupName = NULL,
        $enabledStatus = NULL,
        $createdOnDate = NULL,
        $createdBeforeDate = NULL,
        $createdAfterDate = NULL,
        $permissions = NULL,
        $sendbackResultAsJSON = TRUE,
        $condensed = FALSE
    ){
        $cognito = new CognitoHelper();
        try {
            $result = $cognito->listUsers(
                $groupName,
                $enabledStatus,
                $createdOnDate,
                $createdBeforeDate,
                $createdAfterDate,
                $permissions,
                $condensed
            );

            if(is_null($result)) {
                return response()->json('No users', 404);
            }

            return ($sendbackResultAsJSON === TRUE)
                ? response()->json($result)
                : $result;
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Get ALL Users. Can not pass any custom parameters to filter users.
     * @param bool $sendbackResultsAsJSON
     * @param bool $condensed
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    private static function listAllUsers(
        $sendbackResultsAsJSON = TRUE,
        $condensed = FALSE
    ) {
        $groupName = 'ALL_COGNITO_USERS';
        $enabledStatus = 'any';
        $createdOnDate = NULL;
        $createdBeforeDate = NULL;
        $createdAfterDate = NULL;
        $permissions = NULL;

        return self::listUsers(
            $groupName,
            $enabledStatus,
            $createdOnDate,
            $createdBeforeDate,
            $createdAfterDate,
            $permissions,
            $sendbackResultsAsJSON,
            $condensed
        );
    }

    /**
     * List all duplicate user instances from Cognito that share the same email address
     * @param array $users
     * @return array|void
     */
    public static function listCognitoUsersWithDuplicateInstances(array $users = [])
    {
        $users = !empty($users) ? $users : self::listAllUsers(FALSE, TRUE);

        if (empty($users)) {
            return;
        }

        return self::findCognitoUsersWithDuplicateInstances($users);
    }

    /**
     * List all uppercased user instances from Cognito
     * @param array $users
     * @return array|void
     */
    public static function listCognitoUsersWithUppercasedEmails(array $users = [])
    {
        $users = !empty($users) ? $users : self::listAllUsers(FALSE, TRUE);

        if (empty($users)) {
            return;
        }

        return self::findCognitoUsersWithUppercasedEmails($users);
    }

    /**
     * Get all users that have 'public-website' permissions
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public static function listPublicWebsiteUsers()
    {
        return self::listUsers(
            NULL,   // group name
            'enabled',  // enabled status
            NULL,   // created on date
            NULL,   // created before date
            NULL,   // created after date
            ['public-website'], // permissions
            FALSE,  //sendback as JSON
            TRUE    // condensed
        );
    }

    /**
     * Helper function to find all uppercased user instances from Cognito
     * @param $users
     * @return array
     */
    private static function findCognitoUsersWithUppercasedEmails(array $users)
    {
        $emails = [];
        $uppercasedUsers = [];

        foreach ($users as $currentUser) {
            $lowercasedEmail = strtolower($currentUser['email']);

            if (!in_array($lowercasedEmail,$emails) && $lowercasedEmail !== $currentUser['email']) {
                $uppercasedUsers[] = $currentUser;
            }

            $emails[] = $lowercasedEmail;
        }

        return $uppercasedUsers;
    }

    /**
     * Helper function to find all duplicate user instances from Cognito that share the same email address
     * @param $users
     * @return array
     */
    private static function findCognitoUsersWithDuplicateInstances($users)
    {
        $emails = [];
        $duplicateUserInstances = [];

        foreach ($users as $currentUser) {
            // Duplicate Found, add all user instances to duplicates array
            if (in_array(strtolower($currentUser['email']), $emails)) {
                $email = strtolower($currentUser['email']);

                $duplicateUsers = collect($users)
                    ->filter(function($user) use($email) {
                        return strtolower($user['email']) === $email;
                    })
                    ->sortBy('created')
                    ->values()
                    ->all();

                $duplicateUserInstances[] = (object)[
                    'matching_email' => $email,
                    'shopify_ids_match' => collect($duplicateUsers)->every('shopify_id', $currentUser['shopify_id']),
                    'user_instances' => $duplicateUsers
                ];
            }

            // Push this email to the list of possible duplicates
            $emails[] = strtolower($currentUser['email']);
        }

        return $duplicateUserInstances;
    }

}
