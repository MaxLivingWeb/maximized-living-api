<?php

namespace App\Http\Controllers;

use App\TransactionalEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp;
use SendGrid;
use Illuminate\Support\Facades\View;

class TransactionalEmailController extends Controller
{
    private $emailRecordID;

    /**
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        // Capture request data
        $requestData = $request->json()->all();

        return $this->processing($requestData);

    }

    public function apiSave($request)
    {
        return $this->processing($request);
    }

    private function processing($data)
    {

        // Validate request has content
        if(empty($data)) {
            return response('JSON Format Error', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has TO
        if(empty($data['to_email'])) {
            return response('Missing Required Field: To Email Address', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has REPLY_TO
        if(empty($data['reply_to'])) {
            return response('Missing Required Field: Reply To Address', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has SUBJECT
        if(empty($data['email_subject'])) {
            return response('Missing Required Field: Subject', 400)
                ->header('Content-type', 'text/plain');
        }

        // Validate request has FORM_NAME
        if(empty($data['form_name'])) {
            return response('Missing Required Field: Form Name', 400)
                ->header('Content-type', 'text/plain');
        }

        // Format the data to assign defaults if no data exists
        $formattedData = $this->formatArrayData($data);

        // Save request to the DB and returns ID
//        $this->emailRecordID = $this->saveTransactionalEmail($formattedData);

        // Send to Arcane Leads API
//        $arcaneLeadsStatus = $this->leadsAPISubmission($formattedData);

        // Save Arcane Leads API Status
//        $this->updateTransactionalEmails(['leads_api_submission_status' => $arcaneLeadsStatus]);

        // Send via Sendgrid
        $sendgridStatus = $this->sendgridSubmission($formattedData);

        // Save Sendgrid Response status
//        $this->updateTransactionalEmails(['sendgrid_submission_status' => $sendgridStatus]);

        // If Sendgrid error return 202
        if($sendgridStatus !== 202){
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
    private function sendgridSubmission($data)
    {
        $from = new SendGrid\Email($data['from_name'], $data['from_email']);
        $to = new SendGrid\Email($data['to_name'], $data['to_email']);
        $subject = $data['email_subject'];
        $content = new SendGrid\Content($data['content_type'], $data['content']);

        $mail = new SendGrid\Mail($from, $subject, $to, $content);

        foreach($data['cc_records'] as $value) {
            $email = new SendGrid\Email($value['name'], $value['email']);
            $mail->personalization[0]->addCc($email);
        }

        foreach($data['bcc_records'] as $value) {
            $email = new SendGrid\Email($value['name'], $value['email']);
            $mail->personalization[0]->addBcc($email);
        }

        $reply_to = new SendGrid\ReplyTo($data['reply_to'], $data['from_name']);
        $mail->setReplyTo($reply_to);

        $mail->setTemplateId($data['template_id']);
        foreach ($data['substitutions'] as $key => $value) {
            $mail->personalization[0]->addSubstitution($key, $value);
        }

        $apiKey = env('SENDGRID_API_KEY');
        $sg = new SendGrid($apiKey);

        $response = $sg->client->mail()->send()->post($mail);

        return (int) $response->statusCode();
    }


    /**
     * Sends submission to Arcane Leads API
     *
     * @param $data
     * @return int
     */
    private function leadsAPISubmission($data)
    {
        $client = new GuzzleHttp\Client([
            'base_uri' => 'https://api.arcane.ws/api/'
        ]);

        $response = $client->request('POST', 'save', [
            'headers' => [
                'Content-Type' => 'application/json',
                'FormName' => $data['form_name'],
                'Authorization' => 'Bearer ' . env('ARCANE_LEADS_API_KEY')
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
    private function saveTransactionalEmail($data)
    {
        $emailRecord = new TransactionalEmail();

        foreach ($emailRecord->getTableColumnsWithoutId() as $value) :
            if (array_key_exists($value, $data)){
                $emailRecord->$value = $data[$value];
            }
        endforeach;

        $emailRecord->request_data = json_encode($data);
        $emailRecord->created_at = Carbon::now();
        $emailRecord->updated_at = Carbon::now();

        $emailRecord->save();

        return (int) $emailRecord->id;
    }

    /**
     * Saves update updated status code and update time stamp
     *
     * @param $data
     */
    private function updateTransactionalEmails($data)
    {
        $emailRecord = TransactionalEmail::find($this->emailRecordID);

        foreach ($data as $key => $value):
            $emailRecord->$key = $value;
        endforeach;

        $emailRecord->updated_at = Carbon::now();

        $emailRecord->save();
    }

    /**
     * Merges email defaults with received data before sending to services
     *
     * @param $data
     * @return array
     */
    private function formatArrayData($data)
    {
        $defaults = [
            'to_name' => null,
            'to_email' => null,
            'from_name' => null,
            'from_email' => 'no-reply@maxliving.com',
            'cc_records' => array(),
            'bcc_records' => array(),
            'reply_to' => null,
            'email_subject' => 'Max Living Contact Form',
            'form_name' => null,
            'content_type' => 'text/html',
            'content' => '<span></span>',
            'template_id' => null,
            'substitutions' => [],
            'vanity_website_id' => null,
            'affiliate_id' => null
        ];

        $formattedArray = array_replace_recursive(
            $defaults,
            array_intersect_key($data, $defaults)
        );

        return (array) $formattedArray;
    }

    /**
     * @param $email
     */
    public function LocationEmail($email) {

        $emailTitle = 'MaxLiving Location created: '.$email[2]->name;
        $formName = 'MaxLiving Location Location Created';
        $contentHeader = '<br><h3><a href="'.$email[2]->vanity_website_url.'" target="_blank">'.$email[2]->name.'</a> has been created!</h3>';
        if ($email[4]==='update') {
            $emailTitle = 'Update for MaxLiving Location: '.$email[2]->name;
            $formName = 'Update for MaxLiving Location';
            $contentHeader = '<br><h3><a href="'.$email[2]->vanity_website_url.'" target="_blank">'.$email[2]->name.'</a> has been updated!</h3>';
        }

        $content = View::make(
            'locationEmailNoticeNew',
            [
                'contentHeader'               => $contentHeader,
                'location'                    => $email[2],
                'addresses'                   => $email[3],
                'type'                        => $email[4]
            ]
        )->render();

        if($email[4]==='update') {//render update version of email
            $content = View::make(
                'locationEmailNotice',
                [
                    'contentHeader'               => $contentHeader,
                    'location'                    => $email[2],
                    'addresses'                   => $email[3],
                    'locationBeforeUpdate'        => $email[0],
                    'locationBeforeUpdateAddress' => $email[1],
                    'type'                        => $email[4]
                ]
            )->render();
        }

        $email = array(
            'to_email' => env('ARCANE_NOTIFICATION_EMAIL'),
            'reply_to' => 'noreply@maxliving.com',
            'email_subject' => $emailTitle,
            'form_name' => $formName,
            'content' => $content
        );

        $this->apiSave($email);

        return;
    }
}
