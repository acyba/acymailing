<?php

use AcyMailing\Libraries\acymPlugin;

class SendinblueClass extends acymPlugin
{
    protected $headers;
    public $plugin;

    public function __construct(&$plugin, $headers = null)
    {
        parent::__construct();
        $this->plugin = &$plugin;
        $this->headers = $headers;
    }

    protected function callApiSendingMethod($url, $data = [], $headers = [], $type = 'GET', $authentication = [], $dataDecoded = false)
    {
        $response = parent::callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.$url, $data, $headers, $type, $authentication, $dataDecoded);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        if (!empty($response['error_curl'])) {
            if (!$backtrace[0]['file'] && !empty($backtrace[1]['function'])) {
                $this->plugin->errors[] = $backtrace[0]['file'].': '.$backtrace[1]['function'];
            }
            acym_logError('Error calling the URL '.$url.': '.$response['error_curl'], 'sendinblue');
            $this->plugin->errors[] = $response['error_curl'];
        } elseif (!empty($response['message'])) {
            acym_logError('Error calling the URL '.$url.': '.$response['message'], 'sendinblue');

            if (strpos($response['message'], 'Contact already in list') === false) {
                if (!$backtrace[0]['file'] && !empty($backtrace[1]['function'])) {
                    $this->plugin->errors[] = $backtrace[0]['file'].': '.$backtrace[1]['function'];
                }
                $this->plugin->errors[] = $response['message'];
            }

            if (strpos($response['message'], 'Your account is under validation.') !== false) {
                $this->config->save(['sendinblue_validation' => 1]);
            }
            /*
             * Any API call
             * code: unauthorized
             * message: Key not found
             *
             * User creation
             * code: invalid_parameter
             * message: Invalid email address
             *
             * User delete
             * code: document_not_found
             * message: Contact does not exist
             *
             * create attribute
             * code: invalid_parameter
             * message: Attribute name must be unique
             *
             * Add user to list
             * code: invalid_parameter
             * message: Contact already in list and/or does not exist
             *
             * Create list
             * code: document_not_found
             * message: Folder ID does not exist
             *
             * Campaign creation
             * message: Your account is under validation. You can not create another campaign.
             */
        }

        return $response;
    }
}
