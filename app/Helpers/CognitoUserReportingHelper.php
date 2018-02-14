<?php

namespace App\Helpers;

use App\Helpers\CognitoHelper;

class CognitoUserReportingHelper
{
    public function listDuplicateUserInstances()
    {
        $users = $this->listUsers('ALL_COGNITO_USERS');

        $duplicateUsers = $this->findDuplicateCognitoUserInstances($users);

        return $duplicateUsers;
    }

    private function listUsers($groupName = NULL)
    {
        $cognito = new CognitoHelper();
        try {
            $result = $cognito->listUsers($groupName);

            if(is_null($result)) {
                return response()->json('no users', 404);
            }

            return $result;
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    private function findDuplicateCognitoUserInstances($users)
    {
        $emails = [];
        $duplicateUsers = [];

        foreach ($users as $currentUser) {
            // Duplicate Found, add all user instances to duplicates array
            if (in_array(strtolower($currentUser['email']), $emails)) {
                $email = strtolower($currentUser['email']);

                $duplicateUsersForEmail = collect($users)
                    ->filter(function($user) use($email) {
                        return strtolower($user['email']) === $email;
                    })
                    ->sortBy('created')
                    ->values()
                    ->all();

                $duplicateUsers[$email] = (object)[
                    'user_instances' => $duplicateUsersForEmail,
                    'shopify_ids_match' => collect($duplicateUsersForEmail)->every('shopify_id', $currentUser['shopify_id'])
                ];
            }

            // Push this email to the list of possible duplicates
            $emails[] = strtolower($currentUser['email']);
        }

        return $duplicateUsers;
    }

}
