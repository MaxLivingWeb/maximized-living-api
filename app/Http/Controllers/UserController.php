<?php

namespace App\Http\Controllers;

use App\Helpers\ShopifyHelper;
use App\UserGroup;
use Illuminate\Http\Request;
use App\Helpers\CognitoHelper;
use Aws\Exception\AwsException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function addUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'email'     => 'required|email',
                'password'  => 'required|min:8',
                'firstName' => 'required',
                'lastName'  => 'required',
                'phone'     => 'required',
                'legacyId'  => 'nullable|integer'
            ]);

            //Add user to Cognito
            $cognito = new CognitoHelper();
            $shopify = new ShopifyHelper();

            $cognitoUser = $cognito->createUser($validatedData['email'], $validatedData['password']);

            $selectedGroup = request()->input('group');
            if(!is_null($selectedGroup)) {
                $cognito->addUserToGroup($cognitoUser->get('User')['Username'], $selectedGroup);
            }
            else {
                //no group selected. Create and add to a temporary group
                $tempGroup = $cognito->createGroup('user.' . $validatedData['email'], 'group for ' . $validatedData['email']);

                $params = [
                    'group_name' => $tempGroup['GroupName']
                ];

                if(isset($validatedData['legacyId'])) {
                    $params['legacy_affiliate_id'] = $validatedData['legacyId'];
                }

                UserGroup::create($params);

                $cognito->addUserToGroup($cognitoUser->get('User')['Username'], $tempGroup['GroupName']);
            }

            $customer = [
                'email'     => $validatedData['email'],
                'first_name' => $validatedData['firstName'],
                'last_name'  => $validatedData['lastName'],
                'phone'     => $validatedData['phone']
            ];

            //Add user to Shopify
            $shopifyCustomer = $shopify->getOrCreateCustomer($customer);

            //Save Shopify ID to Cognito user attribute
            $cognito->updateUserAttribute(
                env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'),
                strval($shopifyCustomer->id),
                $validatedData['email']
            );

            return response()->json();
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()]);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors());
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
