<?php

namespace ShubhKansara\PhpQuickbooksConnector\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksEntitySynced;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;
use ShubhKansara\PhpQuickbooksConnector\Models\QbEntityAction;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncSession;
use ShubhKansara\PhpQuickbooksConnector\Models\QwcFile;
use SoapVar;

class QuickBooksWebService
{
    public function serverVersion(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Checking server version...'));

            return ['serverVersionResult' => '1.0.0'];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] Server version check failed', ['exception' => $e->getMessage()]));

            return ['serverVersionResult' => 'Error'];
        }
    }

    public function clientVersion(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Checking client (Web Connector) version...'));

            return ['clientVersionResult' => ''];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] Client version check failed', ['exception' => $e->getMessage()]));

            return ['clientVersionResult' => 'Error'];
        }
    }

    public function authenticate(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Authenticating QuickBooks Web Connector...'));
            if (env('QB_MODE') !== 'desktop') {
                event(new QuickBooksLogEvent('warning', '[Warning] QuickBooks Desktop integration is disabled'));

                return [
                    'authenticateResult' => [
                        'string' => ['QuickBooks Desktop integration is disabled'],
                    ],
                ];
            }

            $user = $req->strUserName;
            $pass = $req->strPassword;

            // Fetch the enabled QWC file
            $qwc = QwcFile::where('enabled', true)->first();

            if ($qwc && $user === $qwc->username && $pass === $qwc->password) {
                $ticket = session()->getId();
                event(new QuickBooksLogEvent('info', '[Success] Authentication successful', ['user' => $user]));

                return [
                    'authenticateResult' => [
                        'string' => [$ticket, ''],
                    ],
                ];
            }

            event(new QuickBooksLogEvent('warning', '[Warning] Authentication failed', ['username' => $user]));

            return [
                'authenticateResult' => [
                    'string' => ['nvu'],
                ],
            ];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] Authentication step failed', ['exception' => $e->getMessage()]));

            return [
                'authenticateResult' => [
                    'string' => ['error'],
                ],
            ];
        }
    }

    public function sendRequestXML(\StdClass $req): \SoapVar
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Preparing to send request XML to QuickBooks...'));

            if (env('QB_MODE') !== 'desktop') {
                event(new QuickBooksLogEvent('warning', '[Warning] Desktop integration is disabled'));
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
                event(new QuickBooksLogEvent('info', '[Step] Found a pending sync job. Preparing QBXML...'));

                $entityConfig = QbEntityAction::whereHas('entity', function ($q) use ($job) {
                    $q->where('name', $job->entity_type)->where('active', true);
                })
                    ->where('action', $job->action ?? 'sync')
                    ->where('active', true)
                    ->firstOrFail();

                // Extract payload keys as variables for Blade
                $vars = $job->payload ?? [];
                $qbxml = Blade::render($entityConfig->request_template, $vars);

                event(new QuickBooksLogEvent('info', 'Generated QBXML for job', [
                    'entity' => $job->entity_type,
                    'action' => $job->action,
                    'job_id' => $job->id,
                    'qbxml' => $qbxml,
                ]));

                // Optionally call a custom handler:
                if ($entityConfig->handler_class) {
                    event(new QuickBooksLogEvent('info', '[Step] Using custom handler for QBXML.'));
                    $handler = app($entityConfig->handler_class);
                    $qbxml = $handler->beforeSend($qbxml, $job);
                }

                event(new QuickBooksLogEvent('info', '[Step] Sending QBXML to QuickBooks.', [
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

                return new SoapVar($wrapper, XSD_ANYXML);
            }

            // 2) Otherwise fallback to your pull logic (ItemQueryRq / CustomerQueryRq, etc.)
            event(new QuickBooksLogEvent('info', '[Step] No pending jobs. Sending empty response to QuickBooks.'));
            $done = <<<'EOX'
            <sendRequestXMLResponse xmlns="http://developer.intuit.com/">
              <sendRequestXMLResult></sendRequestXMLResult>
            </sendRequestXMLResponse>
            EOX;

            return new SoapVar($done, XSD_ANYXML);
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] sendRequestXML failed', ['exception' => $e->getMessage()]));
            $errorXml = <<<EOX
            <sendRequestXMLResponse xmlns="http://developer.intuit.com/">
              <sendRequestXMLResult>Error: {$e->getMessage()}</sendRequestXMLResult>
            </sendRequestXMLResponse>
            EOX;

            return new SoapVar($errorXml, XSD_ANYXML);
        }
    }

    public function receiveResponseXML(\StdClass $req): int
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Receiving response XML from QuickBooks...'));

            if (config('php-quickbooks.mode') !== 'desktop') {
                event(new QuickBooksLogEvent('warning', '[Warning] Desktop integration is disabled'));

                return 100;
            }

            $ticket = $req->ticket ?? null;
            $response = trim($req->response ?? '');
            $hresult = $req->hresult ?? null;
            $message = $req->message ?? null;

            $sync = app(SyncManager::class);
            $connector = app(QuickBooksDesktopConnector::class);

            // 1) Handle transport-level errors
            if ($hresult) {
                event(new QuickBooksLogEvent('error', '[Error] QuickBooks Web Connector transport error', compact('ticket', 'hresult', 'message')));
                $job = QbSyncQueue::where('status', 'processing')->first();
                if ($job) {
                    $sync->markProcessed($job, false, $message);
                }

                return 100;
            }

            if (empty($response)) {
                event(new QuickBooksLogEvent('warning', '[Warning] Received empty response from QuickBooks.', compact('ticket')));

                return 100;
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            if (! $xml) {
                $errs = array_map(fn ($e) => $e->message, libxml_get_errors());
                event(new QuickBooksLogEvent('error', '[Error] Failed to parse XML from QuickBooks.', ['errors' => $errs, 'xml' => $response]));

                return 100;
            }

            event(new QuickBooksLogEvent('info', '[Step] Successfully parsed XML from QuickBooks.', [
                'raw_xml' => $response, // Log the raw XML string for debugging
            ]));

            foreach ($xml->QBXMLMsgsRs->children() as $rsNode) {
                $tag = $rsNode->getName();

                // Get status attributes
                $statusCode = (string) ($rsNode['statusCode'] ?? '');
                $statusSeverity = (string) ($rsNode['statusSeverity'] ?? '');
                $statusMessage = (string) ($rsNode['statusMessage'] ?? '');

                // ——— Push responses (AddRs/ModRs) ———
                if (str_ends_with($tag, 'AddRs') || str_ends_with($tag, 'ModRs')) {
                    event(new QuickBooksLogEvent('info', "[Step] Processing {$tag} (push) response..."));
                    $job = QbSyncQueue::where('status', 'processing')->oldest('processed_at')->first();

                    $entity = substr($tag, 0, -5);

                    $records = [];
                    foreach ($rsNode->children() as $ret) {
                        if (! str_ends_with($ret->getName(), 'Ret')) {
                            continue;
                        }
                        $data = [];
                        foreach ($ret->children() as $child) {
                            $name = $child->getName();
                            if (in_array($name, ['TimeCreated', 'TimeModified'])) {
                                continue;
                            }
                            if ($child->count() > 0) {
                                $data[$name] = $this->parseXmlNode($child);
                            } else {
                                $data[$name] = (string) $child;
                            }
                        }
                        $data['MetaData'] = [
                            'CreateTime' => (string) ($ret->TimeCreated ?? ''),
                            'LastUpdatedTime' => (string) ($ret->TimeModified ?? ''),
                        ];
                        $data['QBType'] = $ret->getName();
                        $records[] = $data;
                    }
                    event(new QuickBooksEntitySynced($entity, $records));
                    event(new QuickBooksLogEvent('info', '[Step] Parsed records from QuickBooks.', [
                        'entity' => $entity,
                        'count' => count($records),
                        'sample' => $records[0] ?? null,
                    ]));

                    // --- Mark the job as processed or failed based on status code ---
                    $job = QbSyncQueue::where('status', 'processing')->oldest('processed_at')->first();
                    if ($job) {
                        if ($statusSeverity === 'Error' || (int) $statusCode >= 3000) {
                            // Log the error event
                            event(new QuickBooksLogEvent(
                                'error',
                                "[QuickBooks Error] {$statusMessage}",
                                [
                                    'entity' => $entity,
                                    'job_id' => $job->id,
                                    'statusCode' => $statusCode,
                                    'statusSeverity' => $statusSeverity,
                                    'statusMessage' => $statusMessage,
                                    'response' => $rsNode->asXML(),
                                ]
                            ));

                            // --- Handle EditSequence out-of-date error ---
                            if (stripos($statusMessage, 'edit sequence') !== false && stripos($statusMessage, 'out-of-date') !== false) {
                                // Get the latest EditSequence from the response
                                foreach ($rsNode->children() as $ret) {
                                    if (! str_ends_with($ret->getName(), 'Ret')) {
                                        continue;
                                    }
                                    $latestEditSequence = (string) ($ret->EditSequence ?? null);
                                    if ($latestEditSequence) {
                                        // Update the job's payload with the new EditSequence
                                        $payload = $job->payload;
                                        $payload['EditSequence'] = $latestEditSequence;
                                        // Optionally update other fields from $ret as needed

                                        $job->payload = $payload;
                                        $job->status = 'pending'; // Mark as pending for retry
                                        $job->save();

                                        event(new QuickBooksLogEvent(
                                            'info',
                                            '[QuickBooks] Updated job payload with latest EditSequence and set to pending.',
                                            [
                                                'job_id' => $job->id,
                                                'new_EditSequence' => $latestEditSequence,
                                            ]
                                        ));
                                    }
                                }
                            } else {
                                // For other errors, mark as failed
                                $sync->markProcessed($job, false, $statusMessage ?: 'QuickBooks error');
                            }
                        } else {
                            $sync->markProcessed($job, true, json_encode($records));
                        }
                    }

                    $session = QbSyncSession::first();
                    if ($session) {
                        $session->update(['last_pull_at' => now()]);
                    } else {
                        QbSyncSession::create(['last_pull_at' => now()]);
                    }
                    event(new QuickBooksLogEvent('info', "[Success] Processed {$entity}QueryRs.", ['entity' => $entity]));

                    $entityConfig = QbEntityAction::whereHas('entity', function ($q) use ($entity) {
                        $q->where('name', $entity)->where('active', true);
                    })
                        ->where('action', $job->action ?? 'sync')
                        ->where('active', true)
                        ->first();

                    if ($entityConfig && $entityConfig->handler_class) {
                        $handler = app($entityConfig->handler_class);
                        if (method_exists($handler, 'afterReceive')) {
                            $payload = $job ? $job->payload : [];
                            $handler->afterReceive([
                                'payload' => $payload,
                                'response' => $records ?? [],
                            ]);
                        }
                    }
                }

                // ——— Pull responses (QueryRs) ———
                if (str_ends_with($tag, 'QueryRs')) {
                    event(new QuickBooksLogEvent('info', "[Step] Processing QueryRs (pull) response for $tag..."));
                    $entity = substr($tag, 0, -7);
                    $retTag = "{$entity}Ret";

                    $records = [];
                    foreach ($rsNode->children() as $ret) {
                        if (! str_ends_with($ret->getName(), 'Ret')) {
                            continue;
                        } // Only process *Ret nodes
                        $data = [];
                        foreach ($ret->children() as $child) {
                            $name = $child->getName();
                            if (in_array($name, ['TimeCreated', 'TimeModified'])) {
                                continue;
                            }
                            // OLD: $data[$name] = (string)$child;
                            // NEW:
                            if ($child->count() > 0) {
                                $data[$name] = $this->parseXmlNode($child);
                            } else {
                                $data[$name] = (string) $child;
                            }
                        }
                        $data['MetaData'] = [
                            'CreateTime' => (string) ($ret->TimeCreated ?? ''),
                            'LastUpdatedTime' => (string) ($ret->TimeModified ?? ''),
                        ];
                        $data['QBType'] = $ret->getName(); // Optionally add the type
                        $records[] = $data;
                    }
                    event(new QuickBooksEntitySynced($entity, $records));
                    event(new QuickBooksLogEvent('info', '[Step] Parsed records from QuickBooks.', [
                        'entity' => $entity,
                        'count' => count($records),
                        'sample' => $records[0] ?? null,
                    ]));

                    // --- Mark the job as processed for Query jobs ---
                    $job = QbSyncQueue::where('status', 'processing')->oldest('processed_at')->first();
                    if ($job) {
                        $sync->markProcessed($job, true, json_encode($records));
                    }

                    $session = QbSyncSession::first();
                    if ($session) {
                        $session->update(['last_pull_at' => now()]);
                    } else {
                        QbSyncSession::create(['last_pull_at' => now()]);
                    }
                    event(new QuickBooksLogEvent('info', "[Success] Processed {$entity}QueryRs.", ['entity' => $entity]));

                    $entityConfig = QbEntityAction::whereHas('entity', function ($q) use ($entity) {
                        $q->where('name', $entity)->where('active', true);
                    })
                        ->where('action', $job->action ?? 'sync')
                        ->where('active', true)
                        ->first();

                    if ($entityConfig && $entityConfig->handler_class) {
                        $handler = app($entityConfig->handler_class);
                        if (method_exists($handler, 'afterReceive')) {
                            $handler->afterReceive($records);
                        }
                    }
                }
            }

            $remaining = $sync->remaining();
            event(new QuickBooksLogEvent('info', '[Step] Remaining jobs in queue: '.$remaining));

            return $remaining > 0 ? $remaining : 100;
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] receiveResponseXML failed', ['exception' => $e->getMessage()]));

            return 100;
        }
    }

    public function connectionError(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Handling connection error from QuickBooks Web Connector...'));
            $ticket = $req->ticket ?? null;
            $hresult = $req->hresult ?? null;
            $message = $req->message ?? null;

            event(new QuickBooksLogEvent('error', '[Error] QBWC Connection Error', compact('ticket', 'hresult', 'message')));

            return ['connectionErrorResult' => 'Error logged'];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] connectionError step failed', ['exception' => $e->getMessage()]));

            return ['connectionErrorResult' => 'Error'];
        }
    }

    public function getLastError(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] getLastError called by QuickBooks Web Connector.'));
            $ticket = $req->ticket ?? null;

            return ['getLastErrorResult' => 'No error'];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] getLastError step failed', ['exception' => $e->getMessage()]));

            return ['getLastErrorResult' => 'Error'];
        }
    }

    public function closeConnection(\StdClass $req): array
    {
        try {
            event(new QuickBooksLogEvent('info', '[Step] Closing connection with QuickBooks Web Connector.'));
            $ticket = $req->ticket ?? null;

            return ['closeConnectionResult' => 'Session completed successfully!'];
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', '[Error] closeConnection step failed', ['exception' => $e->getMessage()]));

            return ['closeConnectionResult' => 'Error'];
        }
    }

    // Add this helper function inside your class (or as a private static function)
    private function parseXmlNode($node)
    {
        $result = [];
        foreach ($node->children() as $child) {
            $name = $child->getName();
            if ($child->count() > 0) {
                // Recursively parse child nodes
                $result[$name] = $this->parseXmlNode($child);
            } else {
                $result[$name] = (string) $child;
            }
        }

        return $result;
    }
}
