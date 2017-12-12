<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransactionalEmailController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $requestData = $request->json()->all();

        if(empty($requestData)) {
            return response('JSON Format Error', 400)
                ->header('Content-type', 'text/plain');
        }

        dd($request->json()->all());

        return response('Accepted', 202)
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
}
