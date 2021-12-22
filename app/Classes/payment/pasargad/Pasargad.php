<?php
namespace App\Classes\payment\pasargad;
use \App\Classes\payment\pasargad\RSA;
use \App\Classes\payment\pasargad\RSAProcessor;
class Pasargad {

    private $TERMINAL_CODE = 1664157;
    private $MERCHANT_CODE = 4483845;
    private $ACTION = 1003;

    public static function getToken($data, $price){
        return 'The payment token will get returned';
        //necessary information:
        //1. url
        //2. price
        //3. data(information)

    }

    public function createPaymentToken($parameters){
        $parameters['TerminalCode'] = $this->TERMINAL_CODE;
        $parameters['MerchantCode'] = $this->MERCHANT_CODE;
        $parameters['Action'] = $this->ACTION;
        
        $jsonData = json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $processor = new RSAProcessor();
        $data = sha1($jsonData, true);
        $data = $processor->sign($data);
        $sign = base64_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/GetToken');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Sign: ' . $sign,
        ));
        $response = curl_exec ($ch);
        curl_close ($ch);
        if($response == NULL){
            return array('status' => 'failed', 'source' => 'c', 'message' => 'could not connect to bank', 'umessage' => 'خطا در اتصال به درگاه بانک');
        }else{
            $response = json_decode($response);
            if($response->IsSuccess === false){
                return array('status' => 'failed', 'source' => 'c', 'message' => 'wrong information was sent', 'umessage' => 'عدم تایید بانک');
            }else{
                return array('status' => 'done', 'stage' => 'payment', 'message' => 'bank response was successful', 'bank' => 'pasargad', 'token' => $response->Token, 'bankPaymentLink' => 'https://pep.shaparak.ir/payment.aspx?n=' . $response->Token);
            }
        }
    }

    public function verifyPayment($invoiceNumber, $invoiceDate, $amount){
        //"bank-response":{"TraceNumber":980924,"ReferenceNumber":141126271381,"TransactionDate":"2021\/12\/22 10:16:08","Action":"1003","TransactionReferenceID":"637757649387308119","InvoiceNumber":"9816401555382857","InvoiceDate":"2021\/12\/22 10:15:38","MerchantCode":4483845,"TerminalCode":1664157,"Amount":1000,"TrxHashedCardNumber":"6B47A319A0FAB70DCF3806F4ADD965FB8714E5EDB2364DF4EEFC15A1D3A926FB","TrxMaskedCardNumber":"6037-69**-****-6563","IsSuccess":true,"Message":"\u0639\u0645\u0644\u064a\u0627\u062a \u0628\u0647 \u0627\u062a\u0645\u0627\u0645 \u0631\u0633\u064a\u062f"}
        $parameters = [
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate' => $invoiceDate,
            'TerminalCode' => $this->TERMINAL_CODE,
            'MerchantCode' => $this->MERCHANT_CODE,
            'Amount' => $amount,
            'Timestamp' => date('Y/m/d H:i:s'),
        ];

        $jsonData = json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $processor = new RSAProcessor();
        $data = sha1($jsonData, true);
        $data = $processor->sign($data);
        $sign = base64_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Sign: ' . $sign,
        ));
        $response = curl_exec ($ch);
        curl_close ($ch);
        if($response == NULL){
            return array('status' => 'failed', 'source' => 'bank', 'message' => 'could not connect to bank', 'umessage' => 'خطا در اتصال به درگاه بانک');
        }else{
            $response = json_decode($response);
            if($response->IsSuccess === false){
                return array('status' => 'failed', 'source' => 'bank', 'message' => 'wrong information was sent', 'umessage' => 'عدم تایید بانک');
            }else{
                return array('status' => 'done');
            }
        }
    }

    public function getTermialCode (){
        return $this->MERCHANT_CODE;
    }

    public function getMerchantcode(){
        return $this->MERCHANT_CODE;
    }

}