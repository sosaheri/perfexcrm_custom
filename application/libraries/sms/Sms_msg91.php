<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sms_msg91 extends App_sms
{
    private $auth_key;

    private $sender_id;

    private $apiRequestUrl = 'http://api.msg91.com/api/v2/sendsms';

    private $worldRequestUrl = 'http://world.msg91.com/api/sendhttp.php';

    public function __construct()
    {
        parent::__construct();

        $this->sender_id = $this->get_option('msg91', 'sender_id');
        $this->auth_key  = $this->get_option('msg91', 'auth_key');
        $this->api_type  = $this->get_option('msg91', 'api_type');

        $this->add_gateway('msg91', [
                'info'    => "<p>MSG91 SMS integration is one way messaging, means that your customers won't be able to reply to the SMS.</p><hr class='hr-10'>",
                'name'    => 'MSG91',
                'options' => [
                    [
                        'name'  => 'sender_id',
                        'label' => 'Sender ID',
                        'info'  => '<p><a href="https://help.msg91.com/article/40-what-is-a-sender-id-how-to-select-a-sender-id" target="_blank">https://help.msg91.com/article/40-what-is-a-sender-id-how-to-select-a-sender-id</a></p>',
                    ],
                    [
                        'name'          => 'api_type',
                        'field_type'    => 'radio',
                        'default_value' => 'api',
                        'label'         => 'Api Type',
                        'options'       => [
                            ['label' => 'World', 'value' => 'world'],
                            ['label' => 'Api', 'value' => 'api'],
                        ],
                    ],
                    [
                        'name'  => 'auth_key',
                        'label' => 'Auth Key',
                    ],
                ],
            ]);
    }

    /**
     * Send sms
     *
     * @param  string $number
     * @param  string $message
     *
     * @return boolean
     */
    public function send($number, $message)
    {
        if ($this->api_type == 'world') {
            return $this->sendViaWorldRoute($number, $message);
        }

        return $this->sendViaApiRoute($number, $message);
    }

    /**
     * Send SMS via World Route
     *
     * @param  string $number
     * @param  string $message
     *
     * @return boolean
     */
    public function sendViaWorldRoute($number, $message)
    {
        try {
            $queryString = array_merge($this->getCommonQueryString(), [
                'mobiles'  => $number,
                'message'  => $message,
                'authkey'  => $this->auth_key,
                'response' => 'json',
            ]);

            $response = $this->client->request(
                'GET',
                $this->worldRequestUrl . '?' . http_build_query($queryString),
                $this->getCommonGuzzleOptions()
            );

            $result = json_decode($response->getBody());

            if ($result->type == 'success') {
                $this->logSuccess($number, $message);

                return true;
            }

            $this->set_error($result->message);
        } catch (\Exception $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);

            $this->set_error($response['message']);
        }

        return false;
    }

    /**
     * Send SMS via the regular API route
     *
     * @param  string $number
     * @param  string $message
     *
     * @return boolean
     */
    public function sendViaApiRoute($number, $message)
    {
        try {
            $response = $this->client->request(
                'POST',
                $this->apiRequestUrl,
                array_merge($this->getCommonGuzzleOptions(), [
                    'body' => json_encode(array_merge($this->getCommonQueryString(), [
                        'sms' => [
                            ['message' => urlencode($message), 'to' => [$number]],
                        ],
                    ])),
                    'headers' => [
                        'authkey' => $this->auth_key,
                    ],
                ])
            );

            $result = json_decode($response->getBody());

            if ($result->type == 'success') {
                $this->logSuccess($number, $message);

                return true;
            }

            $this->set_error($result->message);
        } catch (\Exception $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);

            $this->set_error($response['message']);
        }

        return false;
    }

    /**
     * Get the sender name
     *
     * @return string
     */
    protected function getSender()
    {
        return empty($this->sender_id) ? get_option('companyname') : $this->sender_id;
    }

    /**
     * Get the API common query string options
     *
     * @return array
     */
    protected function getCommonQueryString()
    {
        return hooks()->apply_filters('msg91_common_options', [
                'route'   => 4,
                'country' => 0,
                'unicode' => 1,
                'sender'  => $this->getSender(),
            ]);
    }

    /**
     * Get the API common query string options
     *
     * @return array
     */
    protected function getCommonGuzzleOptions()
    {
        return [
            'allow_redirects' => [
                'max' => 10,
            ],
            'version'        => CURL_HTTP_VERSION_1_1,
            'decode_content' => [CURLOPT_ENCODING => ''],
            ];
    }
}
