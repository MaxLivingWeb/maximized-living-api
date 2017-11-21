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
                'password'  => 'required|size:8',
                'firstName' => 'required',
                'lastName'  => 'required',
                'phone'     => 'required',
                'legacyId'  => 'nullable|integer',
                'commission_id'         => 'nullable|integer',
                'wholesale'             => 'nullable',
                'wholesale.address1'    => 'nullable',
                'wholesale.address2'    => 'nullable',
                'wholesale.city'        => 'nullable',
                'wholesale.province'    => 'nullable',
                'wholesale.phone'       => 'nullable',
                'wholesale.zip'         => 'nullable',
                'wholesale.country'     => 'nullable',
                'discountCode'          => 'nullable|integer',
                'groupName'             => 'nullable',
                'permissions'           => 'nullable|array|min:1',
                'permissions.*'         => 'nullable|string|distinct|exists:user_permissions,key'
            ]);

            //Add user to Cognito
            $cognito = new CognitoHelper();
            $shopify = new ShopifyHelper();

            $cognitoUser = $cognito->createUser($validatedData['email'], $validatedData['password']);

            if(isset($validatedData['groupName'])) {
                $cognito->addUserToGroup($cognitoUser->get('User')['Username'], $validatedData['groupName']);
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

                if(isset($validatedData['commission_id'])) {
                    $params['commission_id'] = $validatedData['commission_id'];
                }

                if(isset($validatedData['discountCode'])) {
                    $params['discount_id'] = $validatedData['discountCode'];
                }

                UserGroup::create($params);

                $cognito->addUserToGroup($cognitoUser->get('User')['Username'], $tempGroup['GroupName']);
            }

            $customer = [
                'email'     => $validatedData['email'],
                'first_name' => $validatedData['firstName'],
                'last_name'  => $validatedData['lastName'],
                'phone'     => $validatedData['phone'],
                'addresses' => [
                    [
                        'address1'  => $validatedData['wholesale']['address1'],
                        'address2'  => $validatedData['wholesale']['address2'],
                        'city'      => $validatedData['wholesale']['city'],
                        'province'  => $validatedData['wholesale']['province'],
                        'phone'     => $validatedData['wholesale']['phone'],
                        'zip'       => $validatedData['wholesale']['zip'],
                        'country'   => $validatedData['wholesale']['country'],
                    ]
                ]
            ];

            //Add user to Shopify
            $shopifyCustomer = $shopify->getOrCreateCustomer($customer);

            //Save Shopify ID to Cognito user attribute
            $cognito->updateUserAttribute(env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'), strval($shopifyCustomer->id), $validatedData['email']);

            //attach permissions to user
            $cognito->updateUserAttribute('custom:permissions', implode(',', $validatedData['permissions']), $validatedData['email']);

            return response()->json();
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getUser($id)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $cognitoUser = $cognito->getUser($id);

            $res = (object) [
                'id'    => $cognitoUser->get('Username'),
                'email' => collect($cognitoUser['UserAttributes'])->where('Name', 'email')->first()['Value'],
                'user_status' => $cognitoUser->get('UserStatus')
            ];

            $shopifyId = collect($cognitoUser['UserAttributes'])->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))->first()['Value'];

            $shopifyCustomer = $shopify->getCustomer($shopifyId);

            $res->shopify_id = $shopifyCustomer->id;
            $res->first_name = $shopifyCustomer->first_name;
            $res->last_name = $shopifyCustomer->last_name;
            $res->phone = $shopifyCustomer->phone;
            $res->addresses = $shopifyCustomer->addresses;

            $userGroups = $cognito->getGroupsForUser($id);

            if($userGroups->isNotEmpty()) {
                $res->affiliate = UserGroup::with('commission')->where('group_name', $userGroups->first()['GroupName'])->first();
            }

            $permissions = collect($cognitoUser['UserAttributes'])->where('Name', 'custom:permissions')->first();
            if(!is_null($permissions)) {
                $res->permissions = explode(',', $permissions['Value']);
            }

            return response()->json($res);
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'shopify_id' => 'required',
                'first_name' => 'required',
                'last_name'  => 'required',
                'phone'      => 'required',
                'group'      => 'nullable',
                'permissions' => 'nullable|array|min:1',
                'permissions.*' => 'nullable|string|distinct|exists:user_permissions,key'
            ]);

            //update user in Shopify
            $shopify = new ShopifyHelper();

            $customer = [
                'id'         => $validatedData['shopify_id'],
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name'],
                'phone'      => $validatedData['phone']
            ];

            $shopify->updateCustomer($customer);

            $cognito = new CognitoHelper();

            if(isset($validatedData['group'])) {
                //Remove user from existing groups
                $userGroups = $cognito->getGroupsForUser($request->id);
                foreach ($userGroups as $group) {
                    $cognito->removeUserFromGroup($request->id, $group['GroupName']);
                }

                //add user to new group
                $cognito->addUserToGroup($request->id, $validatedData['group']);
            }

            //update permissions
            if(isset($validatedData['permissions'])) {
                $cognito->updateUserAttribute('custom:permissions', implode(',', $validatedData['permissions']), $request->id);
            }
            else {
                //no permissions, remove them from user
                $cognito->removeUserAttribute(['custom:permissions'], $request->id);
            }

            return response()->json();
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
