<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH . 'libraries/paypal-php-sdk/paypal/rest-api-sdk-php/sample/bootstrap.php'); // require paypal files

require 'vendor/autoload.php';

use PayPal\Api\ItemList;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Amount;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;

use GuzzleHttp\Client;

class Paypal extends CI_Controller
{
    public $_api_context;

    function  __construct()
    {
        parent::__construct();
        $this->load->model('paypal_model', 'paypal');
        // paypal credentials
        $this->config->load('paypal');

        $this->_api_context = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $this->config->item('client_id'), $this->config->item('secret')
            )
        );
    }

    function index(){
        $this->load->view('content/payment_credit_form');
    }


    function create_payment_with_paypal()
    {

        // setup PayPal api context
        $this->_api_context->setConfig($this->config->item('settings'));


// ### Payer
// A resource representing a Payer that funds a payment
// For direct credit card payments, set payment method
// to 'credit_card' and add an array of funding instruments.

        $payer['payment_method'] = 'paypal';

// ### Itemized information
// (Optional) Lets you specify item wise
// information
        $item1["name"] = $this->input->post('item_name');
        $item1["sku"] = $this->input->post('item_number');  // Similar to `item_number` in Classic API
        $item1["description"] = $this->input->post('item_description');
        $item1["currency"] ="CAD";
        $item1["quantity"] =1;
        $item1["price"] = $this->input->post('item_price');

        $itemList = new ItemList();
        $itemList->setItems(array($item1));

// ### Additional payment details
// Use this optional field to set additional
// payment information such as tax, shipping
// charges etc.
        $details['tax'] = $this->input->post('details_tax');
        $details['subtotal'] = $this->input->post('details_subtotal');
// ### Amount
// Lets you specify a payment amount.
// You can also specify additional details
// such as shipping, tax.
        $amount['currency'] = "CAD";
        $amount['total'] = $details['tax'] + $details['subtotal'];
        $amount['details'] = $details;
// ### Transaction
// A transaction defines the contract of a
// payment - what is the payment for and who
// is fulfilling it.
        $transaction['description'] ='Payment description';
        $transaction['amount'] = $amount;
        $transaction['invoice_number'] = uniqid();
        $transaction['item_list'] = $itemList;

        // ### Redirect urls
// Set the urls that the buyer must be redirected to after
// payment approval/ cancellation.
        $baseUrl = base_url();
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($baseUrl."paypal/getPaymentStatus")
            ->setCancelUrl($baseUrl."paypal/getPaymentStatus");

// ### Payment
// A Payment Resource; create one using
// the above types and intent set to sale 'sale'
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($this->_api_context);
        } catch (Exception $ex) {
            // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
            ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $ex);
            exit(1);
        }
        foreach($payment->getLinks() as $link) {
            if($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        if(isset($redirect_url)) {
            /** redirect to paypal **/
            redirect($redirect_url);
        }

        $this->session->set_flashdata('success_msg','Unknown error occurred');
        redirect('paypal/index');

    }


    public function getPaymentStatus()
    {

        // paypal credentials

        /** Get the payment ID before session clear **/
        $payment_id = $this->input->get("paymentId") ;
        $PayerID = $this->input->get("PayerID") ;
        $token = $this->input->get("token") ;
        /** clear the session payment ID **/

        if (empty($PayerID) || empty($token)) {
            $this->session->set_flashdata('success_msg','Payment failed');
            redirect('paypal/index');
        }

        $payment = Payment::get($payment_id,$this->_api_context);


        /** PaymentExecution object includes information necessary **/
        /** to execute a PayPal account payment. **/
        /** The payer_id is added to the request query parameters **/
        /** when the user is redirected from paypal back to your site **/
        $execution = new PaymentExecution();
        $execution->setPayerId($this->input->get('PayerID'));

        /**Execute the payment **/
        $result = $payment->execute($execution,$this->_api_context);



        //  DEBUG RESULT, remove it later **/
        if ($result->getState() == 'approved') {
            $trans = $result->getTransactions();

            // item info
            $Subtotal = $trans[0]->getAmount()->getDetails()->getSubtotal();
            $Tax = $trans[0]->getAmount()->getDetails()->getTax();

            $payer = $result->getPayer();
            // payer info //
            $PaymentMethod =$payer->getPaymentMethod();
            $PayerStatus =$payer->getStatus();
            $PayerMail =$payer->getPayerInfo()->getEmail();

            $relatedResources = $trans[0]->getRelatedResources();
            $sale = $relatedResources[0]->getSale();
            // sale info //
            $saleId = $sale->getId();
            $CreateTime = $sale->getCreateTime();
            $UpdateTime = $sale->getUpdateTime();
            $State = $sale->getState();
            $Total = $sale->getAmount()->getTotal();
            /** it's all right **/
            /** Here Write your database logic like that insert record or value in database if you want **/
            $this->paypal->create($Total,$Subtotal,$Tax,$PaymentMethod,$PayerStatus,$PayerMail,$saleId,$CreateTime,$UpdateTime,$State);
            $this->session->set_flashdata('success_msg','Payment success');
            redirect('paypal/success');
        }
        $this->session->set_flashdata('success_msg','Payment failed');
        redirect('paypal/cancel');
    }
    function success(){
        $this->load->view("content/success");
    }
    function cancel(){
        $this->load->view("content/cancel");
    }

    function load_refund_form(){
        $this->load->view('content/Refund_payment_form');
    }

    /**
     * Refund payment implemented using the sdk
     */
    function refund_payment(){
        $refund_amount = $this->input->post('refund_amount');
        $saleId = $this->input->post('sale_id');
        $paymentValue =  (string) round($refund_amount,2); ;

// ### Refund amount
// Includes both the refunded amount (to Payer)
// and refunded fee (to Payee). Use the $amt->details
// field to mention fees refund details.
        $amt = new Amount();
        $amt->setCurrency('CAD')
            ->setTotal($paymentValue);

// ### Refund object
        $refundRequest = new RefundRequest();
        $refundRequest->setAmount($amt);

// ###Sale
// A sale transaction.
// Create a Sale object with the
// given sale transaction id.
        $sale = new Sale();
        $sale->setId($saleId);
        try {
            // Refund the sale
            // (See bootstrap.php for more on `ApiContext`)
            $refundedSale = $sale->refundSale($refundRequest, $this->_api_context);
        } catch (Exception $ex) {
            // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
            ResultPrinter::printError("Refund Sale", "Sale", null, $refundRequest, $ex);
            exit(1);
        }

// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        ResultPrinter::printResult("Refund Sale", "Sale", $refundedSale->getId(), $refundRequest, $refundedSale);

        return $refundedSale;
    }

    function load_refund_form_curl(){
        $this->load->view('content/Refund_payment_form_curl');
    }

    /**
     * Refund captured payment implemented using the cURL
     * Don't need to use sdk
     */
    function refund_payment_curl(){
        $refund_amount = $this->input->post('refund_amount');
        $saleId = $this->input->post('sale_id');
        $paymentValue =  (string) round($refund_amount,2); ;

//        $refNumber = '7NX14271YD321594B'; // PayPal transaction ID
//        $FinalTotal = '67.80'; // order total

        // get PayPal access token via cURL
        $ch = curl_init();
        $clientId = "AbqfolVlfz83oAeZmhbztPaZaBZV7uH62w5SYVtLpaeRhD_2IPKrQw2Sc3YRhr8PSGFjYSOoyVUG5tZI";
        $secret = "EKvSawiTVuOiHddWNuW-dTxkR01n5ZqZfvI08w3xohNXJljEVihrmkOGRt1TInQaSmTn7obxQwpxV8Dw";

        curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Accept-Language: en_US'
        ));
        curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);

        if(empty($output)){
            die("Error: No response.");
        }

        $json = json_decode($output);
        $token = $json->access_token; // this is our PayPal access token

        curl_close($ch);

        // refund PayPal sale via cURL
        $header = Array(
            "Content-Type: application/json",
            "Authorization: Bearer $token",
        );
        $ch = curl_init("https://api.sandbox.paypal.com/v2/payments/captures/$saleId/refund");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(empty($output)){
            die("Error: No response. Code:" . $code);
        }

        $res = json_decode($output);
        curl_close($ch);

        // if res has a state, retrieve it
        if(isset($res->status)){
            $status = $res->status;
        }else{
            $status = NULL; // otherwise, set to NULL
        }

        // if we have a state in the response...
        if($status == 'COMPLETED'){
            // the refund was successful
            $this->session->set_flashdata('success_msg','Refund success');
            redirect('paypal/index');
        }else{
            // the refund failed
            $errorName = $res->details[0]->issue; // ex. 'Transaction Refused.'
            $errorReason = $res->details[0]->description; // ex. 'The requested transaction has already been fully refunded.'
            echo 'errorName: ' . $errorName . '<br/>';
            echo 'errorReason: ' . $errorReason . '<br/>';
//            $this->session->set_flashdata('error_msg','Refund failed' . '<br/>' . 'errorName: ' . $errorName . '<br/>' . 'errorReason: ' . $errorReason);
//            redirect('paypal/index');
        }
    }

    function load_refund_form_guzzle(){
        $this->load->view('content/Refund_payment_form_guzzle');
    }

    /**
     * Refund captured payment implemented using the Guzzle
     * Don't need to use sdk
     */
    function refund_payment_guzzle(){
        $refund_amount = $this->input->post('refund_amount');
        $saleId = $this->input->post('sale_id');
        $paymentValue =  (string) round($refund_amount,2);

//        $refNumber = '7NX14271YD321594B'; // PayPal transaction ID
//        $FinalTotal = '67.80'; // order total

        // get PayPal access token via Guzzle
        $uri = 'https://api.sandbox.paypal.com/v1/oauth2/token';
        $clientId = "AbqfolVlfz83oAeZmhbztPaZaBZV7uH62w5SYVtLpaeRhD_2IPKrQw2Sc3YRhr8PSGFjYSOoyVUG5tZI"; //Your client_id which you got on sign up;
        $secret = "EKvSawiTVuOiHddWNuW-dTxkR01n5ZqZfvI08w3xohNXJljEVihrmkOGRt1TInQaSmTn7obxQwpxV8Dw"; //Your secret which you got on sign up;

        $client = new Client();
        $response = $client->request('POST', $uri, [
        'headers' =>
            [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        'body' => 'grant_type=client_credentials',

        'auth' => [$clientId, $secret, 'basic']
    ]);

        $data = json_decode($response->getBody(), true);
        $token = $data['access_token'];

        // refund PayPal sale via Guzzle
        $header = Array(
            "Content-Type" => "application/json",
            "Authorization" => "Bearer $token",
        );
        $uri = "https://api.sandbox.paypal.com/v2/payments/captures/$saleId/refund";

        $bodyParams = new stdClass();
        if($refund_amount){
            $bodyParams->amount = new stdClass();
            $bodyParams->amount->value = $refund_amount;
            $bodyParams->amount->currency_code = 'CAD';
        }
        $note = 'Defective product';
        if($note){
            $bodyParams->note_to_payer = $note;
        }
        $jsonBodyParams = json_encode($bodyParams);
//        echo 'jsonBodyParams=' . $jsonBodyParams . '<br/>';

        $client = new Client();
        $response = $client->request('POST', $uri, [
            'headers' => $header,
            'body' => $jsonBodyParams
        ]);

        $output = $response->getBody();

        if(empty($output)){
            die("Error: No response.");
        }

        $res = json_decode($output);

        // if res has a status, retrieve it
        if(isset($res->status)){
            $status = $res->status;
        }else{
            $status = NULL; // otherwise, set to NULL
        }

        // if we have a status in the response...
        if($status == 'COMPLETED'){
            // the refund was successful
//            echo 'The refund was successful' . '<br>';
//            echo 'res=';
//            echo '<pre>';
//            var_dump($res);
//            echo '</pre>';

            // begin refund detail
//            echo 'refundId=' . $res->id . '<br>';
            $refundId = $res->id;

            // refund PayPal sale via Guzzle
            $header = Array(
                "Content-Type" => "application/json",
                "Authorization" => "Bearer $token",
            );
//            $uri = "https://api.sandbox.paypal.com/v2/payments/refunds/$refundId";
//            echo 'res->links[0]->href=' . $res->links[0]->href . '<br>';
            $uri = $res->links[0]->href;

            $client = new Client();
            $response = $client->request('GET', $uri, [
                'headers' => $header
            ]);

            $output = $response->getBody();

            if(empty($output)){
                die("Error: No response.");
            }

            $res1 = json_decode($output);

            // if res has a status, retrieve it
            if(isset($res1->status)){
                $status1 = $res1->status;
            }else{
                $status1 = NULL; // otherwise, set to NULL
            }

            // if we have a status in the response...
            if($status1 == 'COMPLETED'){
                // the refund was successful
//                echo 'the refund was successful' . '<br>';
//                echo 'res1=';
//                echo '<pre>';
//                var_dump($res1);
//                echo '</pre>';
                echo 'REFUNDTRANSACTIONID=' . $res1->id . '<br>';
                echo 'FEEREFUNDAMT=' . $res1->seller_payable_breakdown->paypal_fee->value . '<br>';
                echo 'GROSSREFUNDAMT=' . $res1->seller_payable_breakdown->gross_amount->value . '<br>';
                echo 'NETREFUNDAMT=' . $res1->seller_payable_breakdown->net_amount->value . '<br>';
                echo 'CURRENCYCODE=' . $res1->amount->currency_code . '<br>';
                echo 'TOTALREFUNDEDAMOUNT=' . $res1->seller_payable_breakdown->total_refunded_amount->value . '<br>';
                echo 'ACK=' . 'Success' . '<br>';
//                echo 'REFUNDSTATUS=' . 'Instant' . '<br>';
//                echo 'PENDINGREASON=' . 'None' . '<br>';

                //            $this->session->set_flashdata('success_msg','Refund success');
//            redirect('paypal/index');
            }else{
                // the refund failed
                $errorName1 = $res1->details[0]->issue; // ex. 'Transaction Refused.'
                $errorReason1 = $res1->details[0]->description; // ex. 'The requested transaction has already been fully refunded.'
                echo 'errorName1: ' . $errorName1 . '<br/>';
                echo 'errorReason1: ' . $errorReason1 . '<br/>';
//            $this->session->set_flashdata('error_msg','Refund failed' . '<br/>' . 'errorName: ' . $errorName . '<br/>' . 'errorReason: ' . $errorReason);
//            redirect('paypal/index');
            }
            // end refund detail
//            $this->session->set_flashdata('success_msg','Refund success');
//            redirect('paypal/index');
        }else{
            // the refund failed
            $errorName = $res->details[0]->issue; // ex. 'Transaction Refused.'
            $errorReason = $res->details[0]->description; // ex. 'The requested transaction has already been fully refunded.'
            echo 'errorName: ' . $errorName . '<br/>';
            echo 'errorReason: ' . $errorReason . '<br/>';
//            $this->session->set_flashdata('error_msg','Refund failed' . '<br/>' . 'errorName: ' . $errorName . '<br/>' . 'errorReason: ' . $errorReason);
//            redirect('paypal/index');
        }
    }

}