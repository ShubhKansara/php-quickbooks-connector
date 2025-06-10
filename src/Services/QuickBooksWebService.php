<?php

namespace ShubhKansara\PhpQuickbooksConnector\Services;

use Illuminate\Support\Facades\Log;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncSession;
use SoapVar;
use ShubhKansara\PhpQuickbooksConnector\Services\QuickBooksDesktopConnector;
use ShubhKansara\PhpQuickbooksConnector\Services\SyncManager;
use XSD_ANYXML;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;

class QuickBooksWebService
{
    // 1 ) serverVersion

    public function serverVersion(\StdClass $req): array
    {
        // WSDL: <serverVersionResponse><serverVersionResult>…</serverVersionResult>
        return ['serverVersionResult' => '1.0.0'];
    }

    // 2 ) clientVersion

    public function clientVersion(\StdClass $req): array
    {
        // WSDL: <clientVersionResponse><clientVersionResult>…</clientVersionResult>
        // $req->strVersion holds the incoming Web Connector version
        return ['clientVersionResult' => ''];
    }

    // 3 ) authenticate

    public function authenticate(\StdClass $req): array
    {
        // Check if QuickBooks Desktop mode is enabled
        if (env('QB_MODE') !== 'desktop') {
            event(new QuickBooksLogEvent('warning', 'QBWC Desktop integration is disabled'));
            return [
                'authenticateResult' => [
                    'string' => ['QuickBooks Desktop integration is disabled']
                ]
            ];
        }

        $user = $req->strUserName;
        $pass = $req->strPassword;
        $configUsername = config('php-quickbooks.username');
        $configPassword = config('php-quickbooks.password');

        if ($user === $configUsername && $pass === $configPassword) {
            $ticket = session()->getId();
            event(new QuickBooksLogEvent('info', 'QBWC Authentication success', ['user' => $user]));
            return [
                'authenticateResult' => [
                    'string' => [$ticket, '']
                ]
            ];
        }

        event(new QuickBooksLogEvent('warning', 'QBWC Authentication failed', ['username' => $user]));
        return [
            'authenticateResult' => [
                'string' => ['nvu']
            ]
        ];
    }

    // …and similarly for sendRequestXML, receiveResponseXML, connectionError, getLastError, closeConnection:

    public function sendRequestXML(\StdClass $req): \SoapVar
    {
        // Only run in desktop mode
        if (env('QB_MODE') !== 'desktop') {
            event(new QuickBooksLogEvent('warning', 'sendRequestXML: Desktop integration is disabled'));
            $disabled = <<<'EOX'
            <sendRequestXMLResponse xmlns = 'http://developer.intuit.com/'>
            <sendRequestXMLResult>QuickBooks Desktop integration is disabled</sendRequestXMLResult>
            </sendRequestXMLResponse>
            EOX;
            return new \SoapVar($disabled, XSD_ANYXML);
        }

        $sync = app(SyncManager::class);

        // 1 ) If there's a pending push job, emit it
        if ($job = $sync->nextJob()) {
            $sync->markStarted($job);

            $connector = app(QuickBooksDesktopConnector::class);

            switch ($job->entity_type) {
                case 'Customer':
                    $qbxml = $connector->generateCustomerQueryXML();
                    break;
                case 'Item':
                    $qbxml = $connector->generateItemQueryXML();
                    break;
                case 'Invoice':
                    $qbxml = $connector->generateInvoiceAddXML($job->payload);
                    break;
                default:
                    $qbxml = '';
            }
            event(new QuickBooksLogEvent('info', 'sendRequestXML: Sending QBXML', [
                'entity' => $job->entity_type,
                'action' => $job->action,
                'job_id' => $job->id,
                'xml' => $qbxml,
            ]));

            $wrapper = <<<XML
                    <sendRequestXMLResponse xmlns="http://developer.intuit.com/">
                    <sendRequestXMLResult><![CDATA[$qbxml]]></sendRequestXMLResult>
                    </sendRequestXMLResponse>
                    XML;

            return new \SoapVar($wrapper, XSD_ANYXML);
        }

        // 2) Otherwise fallback to your pull logic (ItemQueryRq / CustomerQueryRq, etc.)
        event(new QuickBooksLogEvent('info', 'sendRequestXML: No pending jobs, sending empty response'));
        $done = <<<EOX
        <sendRequestXMLResponse xmlns="http://developer.intuit.com/">
          <sendRequestXMLResult></sendRequestXMLResult>
        </sendRequestXMLResponse>
        EOX;
        return new \SoapVar($done, XSD_ANYXML);
    }

    public function receiveResponseXML(\StdClass $req): int
    {
        // Only handle desktop mode
        if (config('php-quickbooks.mode') !== 'desktop') {
            event(new QuickBooksLogEvent('warning', 'receiveResponseXML: Desktop integration is disabled'));
            return 100;
        }

        $ticket   = $req->ticket   ?? null;
        $response = trim($req->response ?? '');
        $hresult  = $req->hresult  ?? null;
        $message  = $req->message  ?? null;

        $sync     = app(SyncManager::class);
        $connector = app(QuickBooksDesktopConnector::class);

        // 1) Handle transport-level errors
        if ($hresult) {
            event(new QuickBooksLogEvent('error', "QBWC SOAP ERROR: {$hresult} – {$message}", compact('ticket', 'hresult', 'message')));
            $job = \ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue::where('status', 'processing')->first();
            if ($job) {
                $sync->markProcessed($job, false, $message);
            }
            return 100;
        }

        if (empty($response)) {
            event(new QuickBooksLogEvent('warning', 'receiveResponseXML: Empty response', compact('ticket')));
            return 100;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        if (! $xml) {
            $errs = array_map(fn($e) => $e->message, libxml_get_errors());
            event(new QuickBooksLogEvent('error', 'QBWC XML PARSE ERROR', ['errors' => $errs, 'xml' => $response]));
            return 100;
        }

        event(new QuickBooksLogEvent('debug', 'receiveResponseXML: Parsed XML', ['xml' => $response]));

        foreach ($xml->QBXMLMsgsRs->children() as $rsNode) {
            $tag = $rsNode->getName();

            // ——— Push responses (AddRs) ———
            if (str_ends_with($tag, 'AddRs')) {
                $job = \ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue
                    ::where('status', 'processing')
                    ->oldest('processed_at')
                    ->first();

                $entity = substr($tag, 0, -5);
                $method = "process{$entity}AddResponse";

                try {
                    if (method_exists($connector, $method)) {
                        $result = $connector->$method($response);
                        event(new QuickBooksLogEvent('info', "Processed {$entity}AddResponse", ['result' => $result, 'job_id' => $job->id ?? null]));
                    } else {
                        event(new QuickBooksLogEvent('warning', "No AddResponse method for {$entity}"));
                        $result = null;
                    }
                    if ($job) {
                        $sync->markProcessed($job, true, json_encode($result));
                    }
                } catch (\Exception $e) {
                    event(new QuickBooksLogEvent('error', "Error in {$method}: " . $e->getMessage(), ['exception' => $e]));
                    if ($job) {
                        $sync->markProcessed($job, false, $e->getMessage());
                    }
                }
            }

            // ——— Pull responses (QueryRs) ———
            if (str_ends_with($tag, 'QueryRs')) {
                $entity = substr($tag, 0, -7);
                $retTag = "{$entity}Ret";

                $repoInt = "App\\Interfaces\\QuickBooks\\{$entity}RepositoryInterface";
                if (! interface_exists($repoInt)) {
                    event(new QuickBooksLogEvent('warning', "No repository for QuickBooks entity {$entity}"));
                    continue;
                }
                $repo = app($repoInt);

                foreach ($rsNode->$retTag ?? [] as $ret) {
                    $data = [];
                    foreach ($ret->children() as $child) {
                        $name = $child->getName();
                        if (in_array($name, ['TimeCreated', 'TimeModified'])) continue;
                        $data[$name] = (string)$child;
                    }
                    $data['MetaData'] = (object)[
                        'CreateTime'      => (string)($ret->TimeCreated  ?? ''),
                        'LastUpdatedTime' => (string)($ret->TimeModified ?? ''),
                    ];
                    $repo->createOrUpdateFromQuickBooks($data);
                }

                // --- Mark the job as processed for Query jobs ---
                $job = QbSyncQueue
                    ::where('status', 'processing')
                    ->oldest('processed_at')
                    ->first();
                if ($job) {
                    $sync->markProcessed($job, true, 'Query processed');
                }

                $session = QbSyncSession::first();
                if ($session) {
                    $session->update(['last_pull_at' => now()]);
                } else {
                    QbSyncSession::create(['last_pull_at' => now()]);
                }
                event(new QuickBooksLogEvent('info', "Processed {$entity}QueryRs", ['entity' => $entity]));
            }
        }

        $remaining = $sync->remaining();
        event(new QuickBooksLogEvent('debug', 'receiveResponseXML: Remaining jobs', ['remaining' => $remaining]));
        return $remaining > 0 ? $remaining : 100;
    }

    /**
     * Handle connection errors from QuickBooks Web Connector
     * @param \StdClass $req Request object containing error details
     * @return array Response indicating error was logged
     */

    public function connectionError(\StdClass $req): array
    {
        $ticket = $req->ticket ?? null;
        $hresult = $req->hresult ?? null;
        $message = $req->message ?? null;

        event(new QuickBooksLogEvent('error', 'QBWC Connection Error', compact('ticket', 'hresult', 'message')));
        return ['connectionErrorResult' => 'Error logged'];
    }

    public function getLastError(\StdClass $req): array
    {
        $ticket = $req->ticket ?? null;
        event(new QuickBooksLogEvent('info', 'QBWC getLastError called', compact('ticket')));
        return ['getLastErrorResult' => 'No error'];
    }

    public function closeConnection(\StdClass $req): array
    {
        $ticket = $req->ticket ?? null;
        event(new QuickBooksLogEvent('info', 'QBWC Connection closed', compact('ticket')));
        return ['closeConnectionResult' => 'Session completed successfully!'];
    }
}
