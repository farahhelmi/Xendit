<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Illuminate\Support\Str;
use App\Models\Payment;

class PaymentController extends Controller
{
    var $apiInstance = null;
    //melakukan check API xendit
    public function __construct() {
        Configuration::setXenditKey("xnd_development_3zMQQPUSyPG4ijjncBXjjc5RE89dPiAI71l82CaIHHIWmq5Z3C0hiXemiWZw5QL");
        $this->apiInstance = new InvoiceApi();
    }

    // create data payment
    public function store(Request $request){
        $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => (string) Str::uuid(),
            'description' => $request->description,
            'amount' => $request->amount,
            'payer_email' => $request->payer_email,
            
          ]); 

        $result = $this->apiInstance->createInvoice($create_invoice_request);
    
        //Save to database
        $payment = new Payment();
        $payment -> status = 'pending';
        $payment -> checkout_link = $result['invoice_url'];
        $payment -> external_id = $create_invoice_request['external_id'];
        $payment -> save();

        return response() -> json($payment);
    }
    
    public function notification(Request $request) {
        $result = $this->apiInstance->getInvoices(null, $request->external_id);

        // Get data
        $payment = Payment::where('external_id', $request->external_id)->firstOrFail();

        if($payment->status == 'settled') {
            return response()->json('Payment anda telah di proses');
        }

        // Update status
        $payment->status = strtolower($result[0]['status']);
        $payment->save();

        return response()->json('Success');
    }
    
}
