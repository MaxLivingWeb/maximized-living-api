<?php

namespace App\Http\Controllers;

use App\TransactionalEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp;

class TransactionalEmailController extends Controller
{
    protected $emailRecordID;
    protected $arcaneLeadsStatus;
    protected $sendgridStatus;

    /**
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        // Capture request data
        $requestData = $request->json()->all();

        // Validate request has content
        if(empty($requestData)) {
            return response('JSON Format Error', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has TO
        if(empty($requestData['to_email'])) {
            return response('Missing Required Field: To Email Address', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has FROM
        if(empty($requestData['from_email'])) {
            return response('Missing Required Field: From Email Address', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has SUBJECT
        if(empty($requestData['email_subject'])) {
            return response('Missing Required Field: Subject', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has FORM_NAME
        if(empty($requestData['form_name'])) {
            return response('Missing Required Field: Form Name', 400)
                ->header('Content-type', 'text/plain');
        }

        // Save request to the DB and returns ID
        $this->emailRecordID = $this->saveTransactionalEmail($requestData);

        // Send to Arcane Leads API
        $this->arcaneLeadsStatus = $this->leadsAPISubmission($requestData);

        // Save Arcane Leads API Status
        $this->updateTransactionalEmails(['leads_api_submission_status' => $this->arcaneLeadsStatus]);

        // Send via Sendgrid
        $this->sendgridStatus = $this->sendgridSubmission($requestData);

        // Save Sendgrid Response status
        $this->updateTransactionalEmails(['sendgrid_submission_status' => $this->sendgridStatus]);

        // If Sendgrid error return 202
        if($this->sendgridStatus !== 202){
            return response('Submission Processing', 202)
                ->header('Content-type', 'text/plain');
        }

        // Return Accepted Status Code After submission and all checks valid
        return response('All Good', 200)
            ->header('Content-type', 'text/plain');
    }


    /**
     * Sends submission to Sendgrid for distribution
     *
     * @param $data
     * @return int
     */
    public function sendgridSubmission($data)
    {

        return (int) $statusCode;
    }


    /**
     * Sends submission to Arcane Leads API
     *
     * @param $data
     * @return int
     */
    public function leadsAPISubmission($data)
    {

        $client = new GuzzleHttp\Client([
            'base_uri' => 'https://api.arcane.ws/api/'
        ]);

        $response = $client->request('POST', 'save', [
            'headers' => [
                'Content-Type' => 'application/json',
                'FormName' => $data['form_name'],
                'Authorization' => 'Bearer ' . getenv('ARCANE_LEADS_API_KEY')
            ],
            'body' => json_encode($data)
        ]);

        return (int) $response->getStatusCode();
    }

    /**
     * Saves the form data in its initial state
     *
     * @param $data
     * @return int
     */
    public function saveTransactionalEmail($data)
    {
        $emailRecord = new TransactionalEmail();

        foreach ($data as $key => $value):
            $emailRecord->$key = $value;
        endforeach;

        $emailRecord->request_data = json_encode($data);
        $emailRecord->created_at = Carbon::now();
        $emailRecord->updated_at = Carbon::now();

        $emailRecord->save();

        return (int) $emailRecord->id;
    }

    public function updateTransactionalEmails($data)
    {
        $emailRecord = TransactionalEmail::find($this->emailRecordID);

        foreach ($data as $key => $value):
            $emailRecord->$key = $value;
        endforeach;

        $emailRecord->updated_at = Carbon::now();

        $emailRecord->save();
    }
}
