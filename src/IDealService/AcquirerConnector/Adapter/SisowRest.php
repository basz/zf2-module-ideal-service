<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Log\Logger,
    Zend\Stdlib\Parameters,
    IDealService\Model\Error,
    IDealService\Model\Issuer,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Transaction,
    IDealService\Model\TransactionStatus,
    IDealService\AcquirerConnector\Adapter\Options\SisowRest as Options;

class SisowRest extends AbstractAdapter
{

    const DIRECTORY_REQUEST_URI   = 'https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/DirectoryRequest';
    const TRANSACTION_REQUEST_URI = 'https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/TransactionRequest';
    const STATUS_REQUEST_URI      = 'https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/StatusRequest';

    /**
     *
     * @return Options
     */
    public function getVendorOptions() {
        return parent::getVendorOptions();
    }

    /**
     * Issue a transaction request from the acquirer
     *
     * @return IssuersCollection | FALSE | Error
     */
    public function issueDirectoryRequest()
    {
        $data = new Parameters();

        if ($this->getServiceOptions()->getUseSandbox())
            $data->set('test', 'true');

//// Set the configuration parameters
//$config = array(
//    'adapter'      => '\Zend\Http\Client\Adapter\Curl',
//);
//
//// Instantiate a client object
//$client = new \Zend\Http\Client('https://www.sisow.nl/Sisow/iDeal/RestHandler.ashx/DirectoryRequest', $config);
//
//// The following request will be sent over a TLS secure connection.
//$response = $client->send();
//   if($response->isOk()) {
//
//       print_r($response->getBody());
//
//   }
//
//   die();
//
//        return false;

        $response = $this->postData($data->toArray(), self::DIRECTORY_REQUEST_URI);

        return $this->extractResponse($response);
    }

    /**
     * Issue a transaction request from the acquirer
     *
     * @param Transaction $transaction
     * @return IssuersCollection | FALSE | Error
     */
    public function issueTransactionRequest(Transaction $transaction)
    {
        $returnUrl        = $transaction->getReturnUrl() ? : $this->getVendorOptions()->getReturnUrl();

        $data = new Parameters();

        if ($this->getServiceOptions()->getUseSandbox())
            $data->set('test', 'true');

        $data->set('purchaseid', $transaction->getReference());
        $data->set('entrancecode', $transaction->getReference());
        $data->set('merchantid', $this->getVendorOptions()->getMerchantId());
        $data->set('issuerid', $transaction->getIssuer());
        $data->set('amount', $transaction->getAmount(true));
        $data->set('description', $transaction->getDescription(true));
        $data->set('returnurl', $returnUrl);

        $hash = sha1(// purchaseid/entrancecode/amount/shopid/merchantid/merchantkey
                $transaction->getReference() .
                $transaction->getReference() .
                $transaction->getAmount(true) .
                $this->getVendorOptions()->getMerchantId() .
                $this->getVendorOptions()->getMerchantKey()
        );

        $data->set('sha1', $hash);

        $response = $this->postData($data->toArray(), self::TRANSACTION_REQUEST_URI);

        $transaction = $this->extractResponse($response, $transaction);

        if (!($transaction instanceof Transaction)) // false || Error
            return $transaction;

        $signature = $transaction->getVendorSpecific()->get('signature');

        $hash = sha1(// 'trxid + issuerurl + merchantid + merchantkey'
            $transaction->getTransactionId() .
            $transaction->getVendorSpecific()->get('issuerurl') .
            $this->getVendorOptions()->getMerchantId() .
            $this->getVendorOptions()->getMerchantKey()
        );


        if ($signature != $hash) {
            $this->log(Logger::ALERT, sprintf(self::LOG_MESSAGE_VERIFICATION_FAILED));
            return false;
        }

        return $transaction;
    }

    /**
     * Query the status of an particular transaction with the acquirer
     *
     * @param Transaction $transaction
     * @return TransactionStatus | FALSE | Error
     */
    public function issueStatusRequest(TransactionStatus $transactionStatus)
    {

        $data = new Parameters();

        $data->set('trxid', $transactionStatus->getTransactionId());

        if ($this->getVendorOptions()->getShopid())
            $data->set('shopid', $this->getVendorOptions()->getShopid());

        $data->set('merchantid', $this->getVendorOptions()->getMerchantId());

        // trxid/shopid/merchantid/merchantkey
        $hash = sha1(
                $transactionStatus->getTransactionId() .
                $this->getVendorOptions()->getShopid() .
                $this->getVendorOptions()->getMerchantId() .
                $this->getVendorOptions()->getMerchantKey()
        );

        $data->set('sha1', $hash);

        $response = $this->postData($data->toArray(), self::STATUS_REQUEST_URI);

        $transactionStatus = $this->extractResponse($response, $transactionStatus);

        if (!($transactionStatus instanceof TransactionStatus)) // false || Error
            return $transactionStatus;

        $signature = $transactionStatus->getVendorSpecific()->get('signature');


        // 'trxid + status + amount + purchaseid + entrancecode + consumeraccount + merchantid + merchantkey'
        $hash = sha1(
                $transactionStatus->getTransactionId() .
                $transactionStatus->getStatus() .
                $transactionStatus->getVendorSpecific()->get('amount') .
                $transactionStatus->getVendorSpecific()->get('purchaseid') .
                $transactionStatus->getVendorSpecific()->get('entrancecode') .
                $transactionStatus->getConsumerAccountNumber() .
                $this->getVendorOptions()->getMerchantId() .
                $this->getVendorOptions()->getMerchantKey()
                );

        if ($signature != $hash) {
            $this->log(Logger::ALERT, sprintf(self::LOG_MESSAGE_VERIFICATION_FAILED));
            return false;
        }

        return $transactionStatus;
    }

    protected function extractResponse($response, $instance = null)
    {
        // parse the response text into an xml object
        $prev = libxml_use_internal_errors(true);
        $xml  = simplexml_load_string($response);
        if ($xml === FALSE) {
            $errors = "";
            foreach (libxml_get_errors() as $error) {
                $errors .= sprintf("%s\n", $error->message);
            }
            throw new \Exception(sprintf("The response packet could not be succesfully parsed: %s", $errors));
        }
        libxml_use_internal_errors($prev);

        // extract an object from the xml object
        $result = false;
        switch ($xml->getName()) {

            case 'directoryresponse':
                /*
                 *
                 * <?xml version="1.0" encoding="UTF-8"?>
                 * <directoryresponse xmlns="https://www.sisow.nl/Sisow/REST" version="1.0.0">
                 *   <directory>
                 *     <issuer>
                 *        <issuerid>01</issuerid>
                 *        <issuername>ABN Amro Bank</issuername>
                 *     </issuer>
                 *   </directory>
                 * </directoryresponse>
                 */

                $result = new IssuersCollection();
                foreach ($xml->directory->children() as $Issuer) {
                    if ($Issuer->getName() != 'issuer') {
                        continue;
                    }

                    $issuerEntry = new Issuer();
                    $issuerEntry->setId((string) $Issuer->issuerid);
                    $issuerEntry->setName((string) $Issuer->issuername);
                    $result->addIssuer($issuerEntry);
                }

                break;

            /**
             * <transactionrequest xmlns="https://www.sisow.nl/Sisow/REST" version="1.0.0">
             *   <transaction>
             *     <issuerurl>IssuerURL</issuerurl>
             *     <trxid>TransactionID</trxid>
             *   </transaction>
             *   <signature>
             *     <sha1>SHA1 trxid + issuerurl + merchantid + merchantkey</sha1>
             *   </signature>
             * </transactionrequest>
             */
            case 'transactionrequest':
                $instance->setIssuerUrl(urldecode((string) $xml->transaction->issuerurl));
                $instance->getVendorSpecific()->set('issuerurl', (string) $xml->transaction->issuerurl);
                $instance->setTransactionId((string) $xml->transaction->trxid);

                $instance->getVendorSpecific()->set('signature', (string) $xml->signature->sha1);
                $result = $instance;
                break;

            case 'statusresponse':
                /**
                 * <statusresponse xmlns="https://www.sisow.nl/Sisow/REST" version="1.0.0">
                 *   <transaction>
                 *     <trxid>TransactionID</trxid>
                 *     <status>Status</status>
                 *     <amount>Bedrag in centen</amount>
                 *     <purchaseid>Kenmerk</purchaseid>
                 *     <description>Omschrijving</description>
                 *     <entrancecode>EntranceCode</entrancecode>
                 *     <timestamp>Tijdstip</timestamp>
                 *     <consumername>Naam rekeninghouder</consumername>
                 *     <consumeraccount>Bankrekening</consumeraccount>
                 *     <consumercity>Plaats rekening</consumercity>
                 *   </transaction>
                 *   <signature>
                 *     <sha1>SHA1 trxid + status + amount + purchaseid + entrancecode + consumeraccount + merchantid + merchantkey</sha1>
                 *   </signature>
                 * </statusresponse>
                 */

                $instance->setStatus((string) $xml->transaction->status);
                $instance->setConsumerName((string) $xml->transaction->consumername);
                $instance->setConsumerAccountNumber((string) $xml->transaction->consumeraccount);
                $instance->setConsumerCity((string) $xml->transaction->consumercity);

                $instance->getVendorSpecific()->set('amount', (string) $xml->transaction->amount);
                $instance->getVendorSpecific()->set('purchaseid', (string) $xml->transaction->purchaseid);
                $instance->getVendorSpecific()->set('description', (string) $xml->transaction->description);
                $instance->getVendorSpecific()->set('entrancecode', (string) $xml->transaction->entrancecode);
                $instance->getVendorSpecific()->set('timestamp', (string) $xml->transaction->timestamp);
                $instance->getVendorSpecific()->set('acquirerID', (string) $xml->transaction->acquirerID);


                $instance->getVendorSpecific()->set('signature', (string) $xml->signature->sha1);

                $result = $instance;
                break;
            case 'errorresponse':

                /*
                 * <?xml version="1.0" encoding="UTF-8"?>
                 * <errorresponse xmlns="https://www.sisow.nl/Sisow/REST" version="1.0.0">
                 *   <error>
                 *     <errorcode>TA3330</errorcode>
                 *     <errormessage>No SHA1</errormessage>
                 *   </error>
                 * </errorresponse>
                 */

                $result = new Error();
                $result->setCode((string) $xml->error->errorcode);
                $result->setMessage((string) $xml->error->errormessage);
                break;
        }

        return $result;
    }
}
