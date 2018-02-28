<?php

namespace App\Helpers;

class CognitoUserReportingHelper
{
    public function listDuplicateUserInstances(array $users)
    {
        if (empty($users)) {
            return;
        }

        $duplicateUsers = $this->findDuplicateCognitoUserInstances($users);

        return $duplicateUsers;
    }

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
