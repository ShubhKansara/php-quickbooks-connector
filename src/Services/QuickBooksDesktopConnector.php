<?php

namespace ShubhKansara\PhpQuickbooksConnector\Services;

use Exception;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;

class QuickBooksDesktopConnector
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the QuickBooks Web Connector configuration
     */
    public function getConfig(): array
    {
        return [
            'username' => config('php-quickbooks.username'),
            'password' => config('php-quickbooks.password'),
            'url' => config('php-quickbooks.url'),
        ];
    }

    /**
     * Generate a customer query request XML
     */
    public function generateCustomerQueryXML(): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>
            <?qbxml version="13.0"?>
            <QBXML>
                <QBXMLMsgsRq onError="stopOnError">
                    <CustomerQueryRq requestID="'.uniqid().'">
                    </CustomerQueryRq>
                </QBXMLMsgsRq>
            </QBXML>';
    }

    /**
     * Generate an item query request XML
     */
    public function generateItemQueryXML(): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>
            <?qbxml version="13.0"?>
            <QBXML>
                <QBXMLMsgsRq onError="stopOnError">
                    <ItemQueryRq requestID="'.uniqid().'">
                    </ItemQueryRq>
                </QBXMLMsgsRq>
            </QBXML>';
    }

    /**
     * Generate an invoice add request XML
     */
    public function generateInvoiceAddXML(array $invoiceData): string
    {
        // Build the invoice XML based on the provided data
        $xml = '<?xml version="1.0" encoding="utf-8"?>
            <?qbxml version="13.0"?>
            <QBXML>
                <QBXMLMsgsRq onError="stopOnError">
                    <InvoiceAddRq requestID="'.uniqid().'">
                        <InvoiceAdd>
                            <CustomerRef>
                                <FullName>'.htmlspecialchars($invoiceData['customer_name']).'</FullName>
                            </CustomerRef>
                            <TxnDate>'.$invoiceData['date'].'</TxnDate>
                            <RefNumber>'.htmlspecialchars($invoiceData['ref_number']).'</RefNumber>
                            <BillAddress>
                                <Addr1>'.htmlspecialchars($invoiceData['bill_address']['addr1'] ?? '').'</Addr1>
                                <Addr2>'.htmlspecialchars($invoiceData['bill_address']['addr2'] ?? '').'</Addr2>
                                <City>'.htmlspecialchars($invoiceData['bill_address']['city'] ?? '').'</City>
                                <State>'.htmlspecialchars($invoiceData['bill_address']['state'] ?? '').'</State>
                                <PostalCode>'.htmlspecialchars($invoiceData['bill_address']['postal_code'] ?? '').'</PostalCode>
                                <Country>'.htmlspecialchars($invoiceData['bill_address']['country'] ?? '').'</Country>
                            </BillAddress>';

        // Add line items
        foreach ($invoiceData['line_items'] as $item) {
            $xml .= '
                            <InvoiceLineAdd>
                                <ItemRef>
                                    <FullName>'.htmlspecialchars($item['name']).'</FullName>
                                </ItemRef>
                                <Desc>'.htmlspecialchars($item['description']).'</Desc>
                                <Quantity>'.$item['quantity'].'</Quantity>
                                <Rate>'.$item['rate'].'</Rate>
                            </InvoiceLineAdd>';
        }

        $xml .= '
                        </InvoiceAdd>
                    </InvoiceAddRq>
                </QBXMLMsgsRq>
            </QBXML>';

        return $xml;
    }

    /**
     * Process the customer query response XML
     */
    public function processCustomerQueryResponse(string $responseXML): array
    {
        $customers = [];

        try {
            $xml = new \SimpleXMLElement($responseXML);
            $customerRets = $xml->xpath('//CustomerRet');

            foreach ($customerRets as $customerRet) {
                $customers[] = [
                    'list_id' => (string) $customerRet->ListID,
                    'name' => (string) $customerRet->Name,
                    'full_name' => (string) $customerRet->FullName,
                    'company_name' => (string) $customerRet->CompanyName,
                    'first_name' => (string) $customerRet->FirstName,
                    'last_name' => (string) $customerRet->LastName,
                    'email' => (string) $customerRet->Email,
                    'phone' => (string) $customerRet->Phone,
                ];
            }
        } catch (Exception $e) {
            event(new QuickBooksLogEvent('error', 'Error processing QuickBooks Desktop customer response', [
                'exception' => $e->getMessage(),
                'xml' => $responseXML,
            ]));
        }

        return $customers;
    }

    /**
     * Process the item query response XML
     */
    public function processItemQueryResponse(string $responseXML): array
    {
        $items = [];

        try {
            $xml = new \SimpleXMLElement($responseXML);
            $itemRets = $xml->xpath('//ItemRet');

            foreach ($itemRets as $itemRet) {
                $items[] = [
                    'list_id' => (string) $itemRet->ListID,
                    'name' => (string) $itemRet->Name,
                    'full_name' => (string) $itemRet->FullName,
                    'description' => (string) $itemRet->SalesDesc,
                    'price' => (string) $itemRet->SalesPrice,
                    'type' => $itemRet->getName(),
                ];
            }
        } catch (Exception $e) {
            event(new QuickBooksLogEvent('error', 'Error processing QuickBooks Desktop item response', [
                'exception' => $e->getMessage(),
                'xml' => $responseXML,
            ]));
        }

        return $items;
    }

    /**
     * Process the invoice add response XML
     */
    public function processInvoiceAddResponse(string $responseXML): ?array
    {
        try {
            $xml = new \SimpleXMLElement($responseXML);
            $invoiceRet = $xml->xpath('//InvoiceRet');

            if (! empty($invoiceRet)) {
                $invoiceRet = $invoiceRet[0];

                return [
                    'txn_id' => (string) $invoiceRet->TxnID,
                    'txn_number' => (string) $invoiceRet->TxnNumber,
                    'ref_number' => (string) $invoiceRet->RefNumber,
                    'customer_name' => (string) $invoiceRet->CustomerRef->FullName,
                    'date' => (string) $invoiceRet->TxnDate,
                    'subtotal' => (string) $invoiceRet->Subtotal,
                    'total' => (string) $invoiceRet->TotalAmount,
                ];
            }
        } catch (Exception $e) {
            event(new QuickBooksLogEvent('error', 'Error processing QuickBooks Desktop invoice response', [
                'exception' => $e->getMessage(),
                'xml' => $responseXML,
            ]));
        }

        return null;
    }
}
