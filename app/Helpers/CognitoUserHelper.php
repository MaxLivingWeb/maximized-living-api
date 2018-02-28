<?php

namespace App\Helpers;

use App\Helpers\CognitoHelper;
use GuzzleHttp\Exception\ClientException;
use Aws\Exception\AwsException;

class CognitoUserHelper
{
    /**
     * List Users from Cognito
     * @param null|string $groupName (Get Cognito users by a specific UserGroup. To get ALL Cognito users, enter "ALL_COGNITO_USERS")
     * @param bool $sendbackResultAsJSON (Sendback result as JSON format)
     * @param bool $condensed (Sendback condensed user data)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public static function listUsers(
        $groupName = NULL,
        $sendbackResultAsJSON = TRUE,
        $condensed = FALSE
    ){
        $cognito = new CognitoHelper();
        try {
            $result = $cognito->listUsers($groupName, $condensed);

            if(is_null($result)) {
                return response()->json('no users', 404);
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
     * List all duplicate user instances from Cognito that share the same email address
     * @param array $users
     * @return array|void
     */
    public function listDuplicateCognitoUserInstances(array $users)
    {
        if (empty($users)) {
            return;
        }

        return $this->findDuplicateCognitoUserInstances($users);
    }

    /**
     * List all uppercased user instances from Cognito
     * @param array $users
     * @return array|void
     */
    public function listUppercasedCognitoUserInstances(array $users)
    {
        if (empty($users)) {
            return;
        }

        return $this->findUppercasedCognitoUserInstances($users);
    }

    /**
     * Helper function to find all uppercased user instances from Cognito
     * @param $users
     * @return array
     */
    private function findUppercasedCognitoUserInstances(array $users)
    {
        $uppercasedUserInstances = [];

        foreach ($users as $currentUser) {
            $lowercasedEmail = strtolower($currentUser['email']);
            if ($lowercasedEmail !== $currentUser['email']) {
                $uppercasedUserInstances[] = $currentUser['email'];
            }
        }

        return $uppercasedUserInstances;
    }

    /**
     * Helper function to find all duplicate user instances from Cognito that share the same email address
     * @param $users
     * @return array
     */
    private function findDuplicateCognitoUserInstances($users)
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

                $duplicateUserInstances[$email] = (object)[
                    'user_instances' => $duplicateUsers,
                    'shopify_ids_match' => collect($duplicateUsers)->every('shopify_id', $currentUser['shopify_id'])
                ];
            }

            // Push this email to the list of possible duplicates
            $emails[] = strtolower($currentUser['email']);
        }

        return $duplicateUserInstances;
    }

}
