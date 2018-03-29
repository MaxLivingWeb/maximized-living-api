<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CognitoUserHelper;

class FindUppercasedCognitoUserEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:findUppercasedCognitoUserEmails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fnd all Cognito user emails that have uppercase letters';

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
        $response = $this->line('Starting to get User data...');
        $users = CognitoUserHelper::listCognitoUsersWithUppercasedEmails();

        if (count($users) === 0) {
            return $this->info('No users found with uppercased emails.');
        }

        foreach ($users as $num => $user) {
            $email = $user['email'];

            // Return response to console
            $number = ($num+1);
            $response .= $this->line($number.'. User ['.$user['id'].'] ... "'.$email.'"');
        }

        $response .= $this->info('Done finding users.');

        return $response;
    }
}
