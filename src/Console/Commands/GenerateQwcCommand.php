<?php

namespace ShubhKansara\PhpQuickbooksConnector\Console\Commands;

use Illuminate\Console\Command;

class GenerateQwcCommand extends Command
{
    protected $signature = 'quickbooks:generate-qwc 
                            {--output= : (optional) path to save the .qwc file }';

    protected $description = 'Generate a QuickBooks Web Connector .qwc file using your php-quickbooks config';

    public function handle()
    {
        $config = config('php-quickbooks');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><QBWCXML/>');
        $xml->addChild('AppName', 'Laravel QBWC Connector');
        $xml->addChild('AppID'); // empty
        $xml->addChild('AppURL', rtrim($config['url'], '/'));
        $xml->addChild('AppDescription', 'Laravel ↔ QuickBooks via Web Connector');
        $xml->addChild('AppSupport', url('/'));
        $xml->addChild('UserName', $config['username']);
        $xml->addChild('OwnerID', $config['owner_id']);
        $xml->addChild('FileID', $config['file_id']);
        $xml->addChild('QBType', 'QBFS');
        $sched = $xml->addChild('Scheduler');
        $sched->addChild('RunEveryNMinutes', '5');

        $qwcContents = $xml->asXML();

        if ($path = $this->option('output')) {
            file_put_contents($path, $qwcContents);
            $this->info("Wrote .qwc to {$path}");
        } else {
            // dump to console
            $this->line($qwcContents);
        }
    }
}
