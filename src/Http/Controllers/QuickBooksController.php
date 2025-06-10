<?php

namespace ShubhKansara\PhpQuickbooksConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;

class QuickBooksController extends Controller
{
    public function handle(Request $request)
    {
        // 1) grab the incoming XML
        $xmlIn = file_get_contents('php://input');
        event(new QuickBooksLogEvent(
            'debug',
            'QBWC REQUEST received',
            ['xml' => $xmlIn, 'ip' => $request->ip()]
        ));

        // 2) dispatch to your SOAP service
        $wsdl = __DIR__ . '/../../Wsdl/QuickBooksConnector.wsdl';

        $server = new \SoapServer($wsdl, [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'exceptions' => true,
        ]);
        $server->setClass(\ShubhKansara\PhpQuickbooksConnector\Services\QuickBooksWebService::class);

        ob_start();
        $server->handle();
        $xmlOut = ob_get_clean();

        // 3) log what you’re about to return
        event(new QuickBooksLogEvent(
            'debug',
            'QBWC RESPONSE sent',
            ['xml' => $xmlOut]
        ));

        return response($xmlOut, 200)
            ->header('Content-Type', 'text/xml');
    }
}
