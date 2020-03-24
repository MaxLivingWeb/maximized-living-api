<?php
namespace App\Console\Commands;

use App\Helpers\CognitoUserHelper;
use Illuminate\Console\Command;

class FindDuplicateCognitoUserInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:listDuplicateUserInstances
                                {--sleepTime=1 : How long to sleep between groups of queries}
                                {--queryGroupSize=3 : How many updateUserAttribute queries to run before sleeping}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Return all duplicate Cognito User Instances';

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
        $sleepTime = (int)$this->option('sleepTime') ?? 1;
        $queryGroupSize = (int)$this->option('queryGroupSize') ?? 3;

        if (!is_int($sleepTime) || !is_int($queryGroupSize)) {
            return $this->error('--sleepTime and --queryGroupSize parameters must both be integers');
        }

        $response = $this->line('Starting to get User data...');
        $duplicateUserInstances = CognitoUserHelper::listCognitoUsersWithDuplicateInstances();

        if (count($duplicateUserInstances) === 0) {
            return $this->info('No users found with matching emails.');
        }

        $response .= $this->info('Total Duplicate User Instances Found: ' . count($duplicateUserInstances));

        if (count($duplicateUserInstances) > 0) {
            $numUsers = count($duplicateUserInstances);
            while ($numUsers--) {
                $results = $duplicateUserInstances[$numUsers];
                $matchingEmail = $results->matching_email;

                $response .= $this->line('----');
                $response .= $this->line(count($results->user_instances).' Users with the Email Address ['.$matchingEmail.']');
                $response .= $this->line('Shopify IDs Match: ' . ($results->shopify_ids_match ? 'Yes' : 'No'));
                $response .= $this->table(
                    ['Cognito ID', 'Email', 'User Status', 'Created', 'Shopify ID'],
                    collect($results->user_instances)->transform(function($user){
                        return [
                            $user['id'],
                            $user['email'],
                            $user['user_status'],
                            $user['created'],
                            $user['shopify_id']
                        ];
                    })
                );

                // Sleep during queries, so API won't reach limit
                if ($numUsers % $queryGroupSize === 0) {
                    sleep($sleepTime);
                }
            }
        }

        $response .= $this->line('----');
        $response .= $this->info('Done Finding Duplicate Cognito Users');
        return $response;
    }
}
