<?php

namespace AdilByrm\ToVBT;
use GuzzleHttp\Client;

class VBT
{
    protected $client;
    public $token = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function doRequest($url, $data, $method = 'post')
    {
        $headers = ['Content-type' => 'application/json'];
        if ($this->token) {
            $headers["vbtAuthorization"] = $this->token;
        }

        $res = $this->client->$method($url, [
            "http_errors" => false,
            "headers" => $headers,
            "body" => json_encode($data)
        ]);

        return (string)$res->getBody();
    }

    public function setToken()
    {
        $data = array("Email" => Config::$apiUsername, "Password" => Config::$apiPassword);
        $res = $this->doRequest(Config::$baseUrl . '/Account/Token', $data);
        $this->token = json_decode($res)->Token;
    }

    public function toVBT($data)
    {
        $res = $this->doRequest(Config::$baseUrl . '/VbtApi/AddOutgoingInvoice', $data, $this->token);
        $data = json_decode($res);
        if (isset($data->ErrorCode)) { // not 200 ok
            return ['error' => true, 'message' => $data->Message];
        }
        if (!$data->Data->HasError) {
            return ['error' => false, 'ettn' => $data->Data->Ettn, 'invoice_number' => $data->Data->InvoiceNumber];
        }
        return ['error' => true, 'message' => $data->Data->Errors[0]->ErrorMessage];
    }

    public function getPdfWithHtml($ettn)
    {
        $res = $this->doRequest(Config::$baseUrl . '/VbtApi/GetOutgoingInvoiceView', ["Ettn" => $ettn], $this->token);
        $data = json_decode($res);
        return $data->Data->InvoiceHtmlView;
        // header('content-type: text/html; charset=utf-8');
        // echo $data->Data->InvoiceHtmlView . "<script>window.print();setTimeout('window.close()', 1000);</script>";
    }

    /**
     * null donerse EARSIVFATURA; deger donerse TEMELFATURA.
     */
    public function mukellef($number)
    {
        $res = $this->doRequest(Config::$baseUrl . '/VbtApi/GetGibInvoiceUser', ["Identifier" => $number]);
        $data = json_decode($res);
        return $data->Data;
    }

    public function incomingInvoiceList()
    {
        $criteria = [
            'Query' => [
                'IssueDate' => [
                    'StartDate' => date('Y-m-d H:i:s', strtotime('-1 months')),
                    'EndDate' => date('Y-m-d H:i:s')
                ]
            ],
            'Skip' => 0,
            'Take' => 100,
            'OrderByName' => '',
            'OrderByType' => 'asc'
        ];
        //GetIncomingInvoiceList
        $res = $this->doRequest(Config::$baseUrl . '/VbtApi/GetOutgoingInvoiceList', $criteria);
        $data = json_decode($res);
        return $data;
    }

    public function incomingInvoiceDetail($id)
    {
        //GetIncomingInvoiceList
        $res = $this->doRequest(Config::$baseUrl . "/VbtApi/GetOutgoingInvoice?id={$id}", [], 'get');
        $data = json_decode($res);
        return $data;
    }
}