<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CognitoHelper;
use Aws\Exception\AwsException;

class AddCognitoAffiliatesToGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:transferAffiliates {--log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets all users from Cognito that are affiliates (or wholesalers, etc.) and adds them to the affiliates user group on Cognito.';

    /**
     * The name of the user group on Cognito.
     *
     * @var string
     */
    protected $userGroupName = '';

    /**
     * The Cognito helper object.
     *
     * @var /App/Helpers/CognitoHelper
     */
    protected $cognito;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userGroupName = env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME');
        if(empty($this->userGroupName)) {
            $this->error('Please set a AWS_COGNITO_AFFILIATE_USER_GROUP_NAME env variable before running this script.');
            exit;
        }

        $this->cognito = new CognitoHelper();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * Loop through all user IDs from the usergroup_users table in the database and add them to a group for affiliates on Cognito.
     *
     * @return mixed
     */
    public function handle()
    {
        $user_ids = \DB::table('usergroup_users')
            ->select('user_id')
            ->get()
            ->pluck('user_id')
            ->unique();

        try {
            $cognitoGroup = $this->_getOrCreateGroup();

            foreach($user_ids as $user_id) {
                try {
                    $this->cognito->addUserToGroup($user_id, $this->userGroupName);
                    if($this->option('log')) {
                        $this->info('Added user ' . $user_id . ' to ' . $this->userGroupName);
                    }
                }
                catch(AwsException $e) {
                    // do nothing, we don't want the whole process to fail if one of the users doesn't actually exist in Cognito
                    // echo it out though
                    if($this->option('log')) {
                        $this->info('Skipping user ' . $user_id . ' because they don\'t exist on Cognito.');
                    }
                }
                //sleep(1); // just for safety, sleep a little (don't want to hit any AWS limits
            }
        } catch(AwsException $e) {
            $this->error($e->getAwsErrorMessage());
            exit;
        }

        if($this->option('log')) {
            $this->info('All done!');
        }
    }

    /**
     * Tries to retrieve the given user group from Cognito. If it doesn't exist, it creates it first.
     *
     * @return stdClass
     */
    private function _getOrCreateGroup() {
        try {
            $cognitoGroup = $this->cognito->getGroup($this->userGroupName);
        }
        catch(AwsException $e) {
            if($e->getStatusCode() !== 400) { // group not found
                $this->error($e->getAwsErrorMessage());
                exit;
            }

            $cognitoGroup = $this->cognito->createGroup(
                $this->userGroupName,
                'All affiliate (incl. wholesalers etc.) users.'
            );
        }

        return $cognitoGroup;
    }
}
