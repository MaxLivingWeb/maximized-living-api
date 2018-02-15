<?php

namespace App\Console\Commands;

use App\Helpers\ExportHelper;
use App\Helpers\CognitoUserReportingHelper;
use Illuminate\Console\Command;

class FindDuplicateCognitoUserInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:listDuplicateUserInstances';

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
        $cognitoUserReportingHelper = new CognitoUserReportingHelper();
        $duplicateUserInstances = $cognitoUserReportingHelper->listDuplicateUserInstances();

        $response = $this->info('Total Duplicate User Instances Found: ' . count($duplicateUserInstances));
        if (count($duplicateUserInstances) > 0) {
            foreach ($duplicateUserInstances as $email => $results) {
                $response .= $this->line('----');
                $response .= $this->line(count($results->user_instances).' Users with the Email Address ['.$email.']');
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
            }
        }

        $response .= $this->line('----');
        $response .= $this->info('Done Finding Duplicate Cognito Users');

        return $response;
    }
}
