<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Model\Order;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;


class CcPaymentController extends Controller
{

    protected $parameters = array();
    protected $merchantData = '';
    protected $encRequest = '';
    protected $testMode = false;
    protected $workingKey = 'E62DC9E51412A129987A3E17430C0713';
    protected $accessCode = 'AVEO78KF76BH82OEHB';
    protected $liveEndPoint = 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    protected $testEndPoint = 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    public $response = '';

    function __construct()
    {
       
        $this->parameters['merchant_id'] = '2571703';
        $this->parameters['currency'] = 'INR';
        $this->parameters['redirect_url'] = 'https://india-naa.in/Api/V1/webhooks/payment/';
        $this->parameters['cancel_url'] = 'https://india-naa.in/Api/V1/webhooks/payment/';
        $this->parameters['language'] = 'EN';
    }

    function encrypt($plainText,$key)
    {
        $key = hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    function decrypt($encryptedText,$key)
    {
        $key = hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    function hextobin($hexString) 
    { 
        $length = strlen($hexString); 
        $binString="";   
        $count=0; 
        while($count<$length) 
        {       
            $subString =substr($hexString,$count,2);           
            $packedString = pack("H*",$subString); 
            if ($count==0)
            {
                $binString=$packedString;
            } 
            
            else 
            {
                $binString.=$packedString;
            } 
            
            $count+=2; 
        } 
            return $binString; 
    } 
    public function processPayment(Request $request)
    {

        $order_id = $request->input('order_id');

        $order = Order::where('order_id', $order_id)->first();

        $amount = $order->order_amount;
        
        $paymentData = [
            'order_id'=>$order_id,
            'amount'=>$amount
        ];
        

        $this->parameters = array_merge($this->parameters,$paymentData);

        foreach($this->parameters as $key=>$value) {
            $this->merchantData .= $key.'='.$value.'&';
        }
        $this->encRequest = $this->encrypt($this->merchantData,$this->workingKey);


        return View::make('indipay::ccavenue')->with('encRequest',$this->encRequest)
                             ->with('accessCode',$this->accessCode)
                             ->with('endPoint',$this->liveEndPoint);
        


    }

    
    
    public function handleWebhook(Request $request)
    {

        $encResponse = $request->encResp;

        $rcvdString = $this->decrypt($encResponse,$this->workingKey);
        parse_str($rcvdString, $decResponse);


        
        $orderStatus = $decResponse['order_status'];
        $orderId = $decResponse['order_id'];
        
        if ($orderStatus === 'Success') {
            
            $order = Order::where('order_id', $orderId)->first();
            
            if ($order) {
                $order->update(['payment_status' => 'paid']);
            }
        }
        
        return response('Webhook received', 200);
    }
}



