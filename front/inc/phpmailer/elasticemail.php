<?php

acym_cmsLoaded();


/**
 * @copyright      Copyright (C) 2009-{__YEAR__} ACYBA SAS - All rights reserved.
 * @license        GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 */
class acyElasticemail
{
    /**
     * Ressources : Connection to the elasticemail server
     */
    var $conn;

    /**
     * String : Last error...
     */
    var $error;
    var $Username = '';
    var $Password = '';

    /* Upload Function which uploads the file selected and return a part of the response.
     * The return value is the file's ID on ElasticEmail server.
     */
    private function uploadAttachment($filepath, $filename)
    {
        if (!empty ($this->attachment[$filepath])) {
            return $this->attachment[$filepath];
        }

        $data = file_get_contents($filepath);
        $header = "PUT /attachments/upload?username=".urlencode($this->Username)."&api_key=".urlencode($this->Password)."&file=".urlencode($filename)." HTTP/1.0\r\n";
        $header .= "Host: api.elasticemail.com\r\n";
        $header .= "Connection: Keep-alive\r\n";
        $header .= "Content-Length: ".strlen($data)."\r\n\r\n";
        $info = $header.$data;
        $result = $this->sendinfo($info);
        //We take the last value of the server's response which correspond of the file's ID.
        $explodedResult = explode("\r\n", $result);
        $res = end($explodedResult);
        //If the ID is correct and we have no Errors
        if (preg_match('#[^a-z0-9\-]#i', $res) || strpos($result, '200 OK') === false) {
            $this->error = "Error while uploading file : ".$res;

            return false;
        } else {
            $this->attachment[$filepath] = $res;

            return $res;
        }
    }

    /* Function which permit to send an email based on the object's values.
     * First, we do the test if we have enough credit to send emails.
     */
    function sendMail(&$object)
    {
        if (!$this->connect()) {
            return false;
        }

        $data = "username=".urlencode($this->Username);
        $data .= "&api_key=".urlencode($this->Password);
        $data .= "&referral=".urlencode('2f0447bb-173a-459d-ab1a-ab8cbebb9aab');
        if (!empty($object->From)) {
            $data .= "&from=".urlencode($object->From);
        }
        if (!empty($object->FromName)) {
            $data .= "&from_name=".urlencode($object->FromName);
        }

        $to = array_merge($object->to, $object->cc, $object->bcc);
        $data .= "&to=";
        foreach ($to as $oneRecipient) {
            $data .= urlencode($object->addrFormat($oneRecipient).";");
        }
        $data = trim($data, ';');

        if (!empty($object->Subject)) {
            $data .= "&subject=".urlencode($object->Subject);
        }

        if (!empty($object->ReplyTo)) {
            $replyToTmp = reset($object->ReplyTo);
            $data .= "&reply_to=".urlencode($replyToTmp[0]);
            if (!empty($replyToTmp[1])) {
                $data .= "&reply_to_name=".urlencode($replyToTmp[1]);
            }
        }

        if (!empty($object->Sender)) {
            $data .= "&sender=".urlencode($object->Sender);
        }


        //Do we have special headers?
        if (!empty($object->CustomHeader)) {
            $i = 1;
            foreach ($object->CustomHeader as $oneHeader) {
                $data .= "&header".$i."=".urlencode($oneHeader[0]).': '.urlencode($oneHeader[1]);
                $i++;
            }
        }

        //We set only quoted printable as others may not work with DKIM
        if ($object->Encoding == 'quoted-printable') {
            $data .= "&encodingtype=3";
        }

        $data .= "&body_html=".urlencode($object->Body);
        if (!empty($object->AltBody)) {
            $data .= "&body_text=".urlencode($object->AltBody);
        }

        if ($object->attachment) {
            $ArrayID = [];
            foreach ($object->attachment as $oneAttachment) {
                $oneID = $this->uploadAttachment($oneAttachment[0], $oneAttachment[2]);
                if (!$oneID) {
                    return false;
                }
                $ArrayID[] = $oneID;
            }
            $data .= "&attachments=".urlencode(implode(";", $ArrayID));
        }

        if (!empty($object->id)) {
            $data .= "&channel=".urlencode($object->id);
        }
        if (!empty($object->type) && strpos($object->type, 'notification') !== false) {
            $data .= '&isTransactional=true';
        }

        $header = "POST /mailer/send HTTP/1.0\r\n";
        $header .= "Host: api.elasticemail.com\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Connection: Keep-Alive\r\n";
        $header .= "Content-Length: ".strlen($data)."\r\n\r\n";
        $info = $header.$data;
        $result = $this->sendinfo($info);

        //We take the last value of the server's response which correspond of the file's ID.
        $explodedVar = explode("\r\n", $result);
        $res = end($explodedVar);

        //If the ID is correct and we have no Errors
        if (strpos($result, '200 OK') === false || preg_match('#[^a-z0-9\-]#i', $res)) {
            $this->error = $res;

            return false;
        } else {
            return true;
        }
    }

    function getCredits($object)
    {
        $header = "GET /mailer/account-details?username=".urlencode($this->Username)."&api_key=".urlencode($this->Password)." HTTP/1.0\r\n";
        $header .= "Host: api.elasticemail.com\r\n";
        $header .= "Connection: Close\r\n\r\n";
        $result = $this->sendinfo($header);
        if (!$result) {
            return false;
        }

        if (preg_match('#<credit>(.*)</credit>#Ui', $result, $explodedResults)) {
            return $explodedResults[1];
        } else {
            $this->error = $result;

            return false;
        }
    }

    private function connect()
    {
        if (is_resource($this->conn)) {
            return true;
        }

        $this->conn = fsockopen('ssl://api.elasticemail.com', 443, $errno, $errstr, 20);
        if (!$this->conn) {
            $this->error = "Could not open connection ".$errstr;

            return false;
        }

        return true;
    }

    private function sendinfo(&$info)
    {
        //Check if the connection is Ok... and if not we return false.
        if (!$this->connect()) {
            return false;
        }

        $res = '';
        $length = 0;
        ob_start();
        $result = fwrite($this->conn, $info);
        $errorContent = ob_get_clean();
        if ($result === false) {
            return $errorContent;
        }

        while (!feof($this->conn)) {
            $res .= fread($this->conn, 1024);
            if (substr($res, 0, 4) == "HTTP") {
                $length = 0;
            }
            if ($length == 0) {
                $pos = strpos(strtolower($res), 'content-length:');
                if ($pos !== false) {
                    $lng = substr($res, $pos + 16, 6);
                    if (strpos($lng, "\r") !== false) {
                        $length = (int)$lng;
                        $length += $pos;
                    }
                }
            }
            if ($length > 0 && strlen($res) >= $length) {
                break;
            }
        }

        return $res;
    }

    function __destruct()
    {
        if (is_resource($this->conn)) {
            fclose($this->conn);
        }
    }
}
