<?php

namespace App\Http\Controllers;

use App\TransactionalEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionalEmailController extends Controller
{
    protected $emailID;
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
        $this->emailID = $this->saveTransactionalEmail($requestData);

        // Send to Arcane Leads API

        // Save Arcane Leads API Status

        // Send via Sendgrid

        // Save Sendgrid Response status

        // If Sendgrid error return 202 and send error log to qa@arcane.ws

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

        return (int) $statusCode;
    }

    /**
     * Saves the form data in its initial state
     *
     * @param $data
     * @return int
     */
    public function saveTransactionalEmail($data)
    {
        $email = new TransactionalEmail();

        foreach ($data as $key => $value):
            $email->$key = $value;
        endforeach;

        $email->request_data = json_encode($data);
        $email->created_at = Carbon::now();
        $email->updated_at = Carbon::now();

        $email->save();

        return (int) $email->id;
    }

    public function updateTransactionalEmails($data)
    {

    }
}
