<?php

class Mqs {

    public $AccessKey = '';
    public $AccessSecret = '';
    public $CONTENT_TYPE = 'text/xml;charset=utf-8';
    public $MQSHeaders = '2015-06-06';//版本号(更改后当前最新版)
    public $queueownerid = '';
    public $mqsurl = '';


    function __construct($key, $secret, $queueownerid, $mqsurl) {
        $this->AccessKey    = $key;
        $this->AccessSecret = $secret;
        $this->queueownerid = $queueownerid;
        $this->mqsurl       = $mqsurl;
    }


    protected function requestCore($request_uri, $request_method, $request_header, $request_body = "") {
        $_start_time = microtime(true);
        if ($request_body != "") {
            $request_header['Content-Length'] = strlen($request_body);
        }
        $_headers = array();
        foreach ($request_header as $name => $value)
            $_headers[] = $name . ": " . $value;

        $_headers[]     = "Expect:";
        $request_header = $_headers;
        $ch             = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $res = curl_exec($ch);
        curl_close($ch);
        $ret = $data = explode("\r\n\r\n", $res);
        $_stat = library('fstat');
        $_stat->set(1, 'MQS连接效率', $_stat->formatTime(number_format(microtime(true) - $_start_time, 6)), 'aliyun_mqs');

        return $ret;
    }


    protected function errorHandle($headers) {
        preg_match('/HTTP\/[\d]\.[\d] ([\d]+) /', $headers, $code);
        if ($code[1]) {
            if ($code[1] / 100 > 1 && $code[1] / 100 < 4)
                return false;
            else return $code[1];
        }
    }

    protected function getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders = array(), $CanonicalizedResource = "/") {
        $order_keys = array_keys($CanonicalizedMQSHeaders);
        sort($order_keys);
        $x_mqs_headers_string = "";
        foreach ($order_keys as $k) {
            $x_mqs_headers_string .= join(":", array(strtolower($k), $CanonicalizedMQSHeaders[$k] . "\n"));
        }

        $string2sign = sprintf(
            "%s\n%s\n%s\n%s\n%s%s",
            $VERB,
            $CONTENT_MD5,
            $CONTENT_TYPE,
            $GMT_DATE,
            $x_mqs_headers_string,
            $CanonicalizedResource
        );

        $sig = base64_encode(hash_hmac('sha1', $string2sign, $this->AccessSecret, true));
        return "MNS " . $this->AccessKey . ":" . $sig;
    }

    protected function getGMTDate() {
        return gmdate('D, d M Y H:i:s T');
    }


    protected function getXmlData($strXml) {
        $pos = strpos($strXml, 'xml');
        if ($pos) {
            $xmlCode   = simplexml_load_string($strXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $arrayCode = $this->get_object_vars_final($xmlCode);
            isset($arrayCode['MessageBody']) && $arrayCode['MessageBody'] = base64_decode($arrayCode['MessageBody']);
            return $arrayCode;
        } else {
            return '';
        }
    }


    protected function get_object_vars_final($obj) {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }
        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = $this->get_object_vars_final($value);
            }
        }
        return $obj;
    }

}


Class Queue extends Mqs {

    public function Createqueue($queueName, $parameter = array()) {

        $queue = array('DelaySeconds' => 0, 'MaximumMessageSize' => 65536, 'MessageRetentionPeriod' => 345600, 'VisibilityTimeout' => 30, 'PollingWaitSeconds' => 30);
        foreach ($queue as $k => $v) {
            foreach ($parameter as $x => $y) {
                if ($k == $x) {
                    $queue[$k] = $y;
                }
            }
        }
        $VERB                    = "PUT";
        $CONTENT_BODY            = $this->generatequeuexml($queue);
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/" . $queueName;

        $sign = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);

        $headers = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
        }
        return $msg;
    }


    public function Setqueueattributes($queueName, $parameter = array()) {

        $queue = array('DelaySeconds' => 0, 'MaximumMessageSize' => 65536, 'MessageRetentionPeriod' => 345600, 'VisibilityTimeout' => 30, 'PollingWaitSeconds' => 30);
        foreach ($queue as $k => $v) {
            foreach ($parameter as $x => $y) {
                if ($k == $x) {
                    $queue[$k] = $y;
                }
            }
        }
        $VERB                    = "PUT";
        $CONTENT_BODY            = $this->generatequeuexml($queue);
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/" . $queueName . "?metaoverride=true";

        $sign = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);

        $headers = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    public function Getqueueattributes($queueName) {
        $VERB                    = "GET";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/" . $queueName;

        $sign = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);

        $headers = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    public function Deletequeue($queueName) {
        $VERB                    = "DELETE";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/" . $queueName;

        $sign = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);

        $headers = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
        }
        return $msg;
    }


    public function ListQueue($prefix = '', $number = '', $marker = '') {
        $VERB                    = "GET";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders,
        );

        if ($prefix != '') {
            $CanonicalizedMQSHeaders['x-mqs-prefix'] = $prefix;
        }
        if ($number != '') {
            $CanonicalizedMQSHeaders['x-mqs-ret-number'] = $number;
        }
        if ($marker != '') {
            $CanonicalizedMQSHeaders['x-mqs-marker'] = $marker;
        }

        $RequestResource = "/";
        $sign            = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);
        $headers         = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    private function generatequeuexml($queue = array()) {
        header('Content-Type: text/xml;');
        $dom               = new DOMDocument("1.0", "utf-8");
        $dom->formatOutput = TRUE;
        $root              = $dom->createElement("Queue");
        $dom->appendchild($root);
        $price = $dom->createAttribute("xmlns");
        $root->appendChild($price);
        $priceValue = $dom->createTextNode('http://mns.aliyuncs.com/doc/v1/');
        $price->appendChild($priceValue);

        foreach ($queue as $k => $v) {
            $queue = $dom->createElement($k);
            $root->appendChild($queue);
            $titleText = $dom->createTextNode($v);
            $queue->appendChild($titleText);
        }
        return $dom->saveXML();
    }

}

class Message extends Mqs {

    public function SendMessage($queueName, $msgbody, $DelaySeconds = 0, $Priority = 8) {
        $VERB         = "POST";
        $CONTENT_BODY = $this->generatexml($msgbody, $DelaySeconds, $Priority);

        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/queues/" . $queueName . "/messages";
        $sign                    = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);
        $headers                 = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }

        $headers['Authorization'] = $sign;

        $request_uri = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data        = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);
        $msg         = array();
        $error       = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    public function ReceiveMessage($queue, $Second) {
        $VERB                    = "GET";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/queues/" . $queue . "/messages?waitseconds=" . $Second;
        $sign                    = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);
        $headers                 = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $msg   = array();
        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    public function DeleteMessage($queueName, $ReceiptHandle) {
        $VERB                    = "DELETE";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/queues/" . $queueName . "/messages?ReceiptHandle=" . $ReceiptHandle;
        $sign                    = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);
        $headers                 = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
        } else {
            $msg['state'] = "ok";
        }
        return $msg;
    }


    public function PeekMessage($queuename) {
        $VERB                    = "GET";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/queues/" . $queuename . "/messages?peekonly=true";
        $sign                    = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);
        $headers                 = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $msg   = array();
        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    public function ChangeMessageVisibility($queueName, $ReceiptHandle, $visibilitytimeout) {

        $VERB                    = "PUT";
        $CONTENT_BODY            = "";
        $CONTENT_MD5             = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE            = $this->CONTENT_TYPE;
        $GMT_DATE                = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mns-version' => $this->MQSHeaders
        );
        $RequestResource         = "/queues/" . $queueName . "/messages?ReceiptHandle=" . $ReceiptHandle . "&VisibilityTimeout=" . $visibilitytimeout;

        $sign = $this->getSignature($VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource);

        $headers = array(
            'Host'         => $this->queueownerid . "." . $this->mqsurl,
            'Date'         => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5'  => $CONTENT_MD5
        );
        foreach ($CanonicalizedMQSHeaders as $k => $v) {
            $headers[$k] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri              = 'http://' . $this->queueownerid . '.' . $this->mqsurl . $RequestResource;
        $data                     = $this->requestCore($request_uri, $VERB, $headers, $CONTENT_BODY);

        $error = $this->errorHandle($data[0]);
        if ($error) {
            $msg['state'] = $error;
            $msg['msg']   = $this->getXmlData($data[1]);
        } else {
            $msg['state'] = "ok";
            $msg['msg']   = $this->getXmlData($data[1]);
        }
        return $msg;
    }


    private function generatexml($msgbody, $DelaySeconds = 0, $Priority = 8) {
        header('Content-Type: text/xml;');
        $dom               = new DOMDocument("1.0", "utf-8");
        $dom->formatOutput = TRUE;
        $root              = $dom->createElement("Message");
        $dom->appendchild($root);
        $price = $dom->createAttribute("xmlns");
        $root->appendChild($price);
        $priceValue = $dom->createTextNode('http://mns.aliyuncs.com/doc/v1/');
        $price->appendChild($priceValue);

        $msg = array('MessageBody' => $msgbody, 'DelaySeconds' => $DelaySeconds, 'Priority' => $Priority);
        foreach ($msg as $k => $v) {
            $msg = $dom->createElement($k);
            $root->appendChild($msg);
            if ($k == 'MessageBody') {
                $titleText = $dom->createTextNode(base64_encode($v));
            } else {
                $titleText = $dom->createTextNode($v);
            }
            $msg->appendChild($titleText);
        }
        return $dom->saveXML();
    }
}
