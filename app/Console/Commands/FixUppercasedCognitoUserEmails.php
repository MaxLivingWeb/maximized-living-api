<?php

namespace App\Console\Commands;

use App\CognitoUser;
use Illuminate\Console\Command;
use App\Helpers\CognitoHelper;
use App\Helpers\CognitoUserHelper;

class FixUppercasedCognitoUserEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fixUppercasedCognitoUserEmails
                                {--sleepTime=1 : How long to sleep between groups of updateUserAttribute queries}
                                {--queryGroupSize=3 : How many updateUserAttribute queries to run before sleeping}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix all Cognito user emails that have uppercase letters, and convert to all lowercase';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cognito = new CognitoHelper();

        $sleepTime = (int)$this->option('sleepTime') ?? 1;
        $queryGroupSize = (int)$this->option('queryGroupSize') ?? 3;

        if (!is_int($sleepTime) || !is_int($queryGroupSize)) {
            return $this->error('--sleepTime and --queryGroupSize parameters must both be integers');
        }

        $users = CognitoUserHelper::listCognitoUsersWithUppercasedEmails();
        
        if (count($users) === 0) {
            return $this->info('No users found with uppercased emails.');
        }

        $numUsers = count($users);
        $response = $this->info('Starting to update users...');
        while ($numUsers--) {
            $user = $users[$numUsers];
            $email = $user['email'];

            // Update user to Cognito
            $cognito->updateUserAttribute('email', strtolower($email), $user['id']);

            // Return response to console
            $number = ($numUsers+1);
            $response .= $this->line($number.'. User to Update ['.$user['id'].'] ... "'.$email.'" changed to "'.strtolower($email).'"');

            // Sleep during queries, so API won't reach limit
            if ($numUsers % $queryGroupSize === 0) {
                sleep($sleepTime);
            }
        }

        $response .= $this->info('Done updating users.');

        return $response;
    }
}
