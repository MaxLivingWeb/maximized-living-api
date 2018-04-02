<?php

namespace App\Console\Commands;

use App\Helpers\CognitoUserHelper;
use Illuminate\Console\Command;

class FindPublicWebsiteUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:findPublicWebsiteUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate all Users with the "public-website" permission value, to ensure they are also Affiliates';

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
        $this->line('Starting to get User data...');

        $users = CognitoUserHelper::listPublicWebsiteUsers();

        if (count($users) === 0) {
            $this->info('No users found with "public-website" permissions.');
        }

        $this->info('Total Users Found: ' . count($users));

        if (count($users) > 0) {
            $this->table(
                ['Cognito ID', 'Email', 'Affiliate ID', 'Location ID', 'Clinic Website ID', 'Clinic Website URL', 'Created'],
                collect($users)->transform(function($user){
                    return [
                        $user['id'],
                        $user['email'],
                        $user['affiliate']['id'] ?? null,
                        $user['affiliate']['location']['id'] ?? null,
                        $user['affiliate']['location']['vanity_website_id'] ?? null,
                        $user['affiliate']['location']['vanity_website_url'] ?? null,
                        $user['created']
                    ];
                })
            );
        }

        $this->info('Done Finding Public Website Users');
    }
}
