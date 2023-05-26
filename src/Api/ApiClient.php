<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 * @author    Afternet <info@afternet.gr>
 * @copyright Afternet
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace CleverPoint\Api;

class ApiClient
{
    private $api_url = null;
    private $api_key = null;
    private $sandbox = false;
    private $errors = [];
    public $additional_headers = [];

    /**
     * Default contructor
     *
     * @param $api_key
     * @param $sandbox
     * @return void
     */
    public function __construct($api_key, $sandbox = false)
    {
        $this->set('sandbox', $sandbox);

        if ($this->sandbox) {
            $this->set('api_url', 'https://test.cleverpoint.gr/');
        } else {
            $this->set('api_url', 'https://platform.cleverpoint.gr/');
        }
        $this->set('api_key', $api_key);
    }

    /**
     * @param $variable
     * @param $value
     * @return void
     */
    public function set($variable, $value)
    {
        $this->{$variable} = $value;
    }

    /**
     * @param $variable
     * @return mixed
     */
    public function get($variable)
    {
        return $this->{$variable};
    }

    /**
     * Get API Url
     * @param $service
     * @return string
     */
    public function getApiUrl($service = null)
    {
        return sprintf("%s/api/v1/%s", $this->get('api_url'), $service);
    }

    /**
     * Call API
     * @param $service
     * @param $fields
     * @param  $method
     * @return mixed
     */
    public function apiCall($service, $fields = [], $method = 'POST')
    {
        $headers = [
            'Content-Type: application/json',
        ];

        if (!empty($this->get('api_key'))) {
            $headers[] = sprintf("Authorization: ApiKey %s", $this->get('api_key'));
        }

        if (!empty($this->additional_headers)) {
            $headers = array_merge($headers, $this->additional_headers);
        }

        try {
            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => $this->getApiUrl($service),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => $headers,
            ));

            if ($method == 'POST' || $method == 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            }
            $result = curl_exec($ch);

            if ($result === false) {
                $this->errors[] = curl_error($ch);
                return false;
            }
            curl_close($ch);

            // Decode result
            $result_decode = json_decode($result, true, 512, JSON_UNESCAPED_UNICODE);
            // Check for errors
            if (isset($result_decode['status']) && $result_decode['status'] != 200) {
                $this->errors[] = $this->getErrorMessage($result_decode['code']);
            }

            return $result_decode;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Check if response has errors
     *
     * @param $response
     * @return boolean
     */
    public function hasError($response)
    {
        // Check for errors
        if (isset($response['ResultType']) && $response['ResultType'] != 'Success') {
            return true;
        }

        return false;
    }

    /**
     * POST /api/v1/Shipping/GetPrices
     *
     *
     * @return mixed
     */
    public function getPrices()
    {
        $result = $this->apiCall('Shipping/GetPrices', null, 'GET');
        if (!$this->hasError($result)) {
            return isset($result['Content'][0]['Price']['Value']) ? (float)$result['Content'][0]['Price']['Value'] : 0;
        }

        return false;
    }

    /**
     * POST /api/v1/Shipping/GetCarriers
     *
     *
     * @return mixed
     */
    public function getCarriers()
    {
        $result = $this->apiCall('Shipping/GetCarriers', null, 'GET');
        $carriers = [];
        if (!$this->hasError($result)) {
            if (isset($result['Content']) && is_array($result['Content'])) {
                foreach ($result['Content'] as $carrier) {
                    $carriers[] = $carrier;
                }
            }
        }

        return $carriers;
    }

    /**
     * Get error message
     *
     * @param $error
     * @return string
     */
    public function getErrorMessage($error)
    {
        $errors_arr = array(
            "400" => "Invalid request data Make sure you are sending the request according to the documentation.",
            "401" => "Invalid request origin. Make sure you are sending the rcorrect credentials.",
            "403" => "You are not allowed to copmlete the action.",
            "404" => "The request object does not exist.",
            "405" => "Method dont allowed",
            "429" => "Too many requests",
        );

        return sprintf("Error: %s - %s", $error, (isset($errors_arr[$error]) ? $errors_arr[$error] : "Unkown error"));
    }

}