<?php

namespace App\Helpers;

class SlackHelper
{
    /**
     * Sends an error notification to the ecomm team
     *
     * @param string $msg
     * @param string $channel
     */
    public static function slackNotification(string $msg, string $channel = 'C5S9LV83S')
    {
        $curl = curl_init();
        curl_setopt_array($curl,
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://slack.com/api/chat.postMessage',
                CURLOPT_POSTFIELDS => [
                    'token' => 'xoxb-101555870051-rw3ZFZk34E8fzDaon5jhfjVc',
                    'channel' => $channel,
                    'text' => $msg,
                    'parse' => 'full',
                    'username' => 'maxliving-bot'
                ]
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }
}
