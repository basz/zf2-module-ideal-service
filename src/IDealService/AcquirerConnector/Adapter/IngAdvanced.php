<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Log\Logger,
    IDealService\Model\Error,
    IDealService\Model\Issuer,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Transaction,
    IDealService\Model\TransactionStatus;

class IngAdvanced extends AbstractAdapter
{

    const API_URI      = 'https://ideal.secure-ing.com:443/ideal/iDeal';
    const API_URI_TEST = 'https://idealtest.secure-ing.com:443/ideal/iDeal';

    /**
     * Issue a transaction request from the acquirer
     *
     * @return IssuersCollection | FALSE | Error
     */
    public function issueDirectoryRequest()
    {
        $xml = $this->constructCommonXml('DirectoryReq', array($this->options->getMerchantId(), $this->options->getSubId()));

        if (!$xml) {
            return false;
        }

        $response = $this->postData($xml->asXML(), $this->getOptions()->getUseSandbox() ? self::API_URI_TEST : self::API_URI);

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
        $returnUrl        = $transaction->getReturnUrl() ? : $this->options->getReturnUrl();
        $expirationPeriod = $transaction->getExpirationPeriod() ? : $this->options->getExpirationPeriod();

        $xml = $this->constructCommonXml('AcquirerTrxReq', array(
            $transaction->getIssuer(true),
            $this->options->getMerchantId(),
            $this->options->getSubId(),
            $returnUrl,
            $transaction->getReference(true),
            $transaction->getAmount(true),
            $transaction->getCurrency(true),
            $transaction->getLanguage(true),
            $transaction->getDescription(true),
            $transaction->getReference(true)
                ));


        if (!$xml) {
            return false;
        }

        $xml->Merchant->addChild('merchantReturnURL', $returnUrl);

        $Issuer = $xml->addChild('Issuer');
        $Issuer->addChild('issuerID', $transaction->getIssuer(true));

        $xmlTrx = $xml->addChild('Transaction');
        $xmlTrx->addChild('purchaseID', $transaction->getReference(true));
        $xmlTrx->addChild('amount', $transaction->getAmount(true));
        $xmlTrx->addChild('currency', $transaction->getCurrency(true));
        $xmlTrx->addChild('expirationPeriod', $expirationPeriod);
        $xmlTrx->addChild('language', $transaction->getLanguage(true));
        $xmlTrx->addChild('description', $transaction->getDescription(true));
        $xmlTrx->addChild('entranceCode', $transaction->getReference(true));

        $response = $this->postData($xml->asXML(), $this->getOptions()->getUseSandbox() ? self::API_URI_TEST : self::API_URI);

        return $this->extractResponse($response, $transaction);
    }

    /**
     * Query the status of an particular transaction with the acquirer
     *
     * @param Transaction $transaction
     * @return TransactionStatus | FALSE | Error
     */
    public function issueStatusRequest(TransactionStatus $transactionStatus)
    {
        // Build the status request XML.
        $xml = $this->constructCommonXml("AcquirerStatusReq", array(
            $this->options->getMerchantId(),
            $this->options->getSubId(),
            $transactionStatus->getTransactionId()
                ));

        if (!$xml) {
            return false;
        }

        $xmlTrx = $xml->addChild('Transaction');
        $xmlTrx->addChild('transactionID', $transactionStatus->getTransactionId());

        $response = $this->postData($xml->asXML(), $this->getOptions()->getUseSandbox() ? self::API_URI_TEST : self::API_URI);

        $transactionStatus = $this->extractResponse($response, $transactionStatus);

        if (!($transactionStatus instanceof TransactionStatus)) // false || Error
            return $transactionStatus;

        // The verification of the response starts here.
        // The message as per the reference guide instructions.
        $message = $transactionStatus->getVendorData()->get('createDateTimeStamp') . $transactionStatus->getTransactionId() . $transactionStatus->getStatus() . $transactionStatus->getConsumerAccountNumber();
        $message = str_replace(array(" ", "\t", "\n"), array('', '', ''), $message);

        // The signed hash is base64 encoded and inserted into the XML as such
        $signature = base64_decode($transactionStatus->getVendorData()->get('signatureValue'));

        // The merchant should have the public certificate stored locally.
        $certfile = $this->getCertificateFileName($transactionStatus->getVendorData()->get('fingerprint'));
        if (!$certfile) {
            return false;
        }

        // Verify the message signature
        $valid = $this->verifyMessage($certfile, $message, $signature);
        if (!$valid) {
            return false;
        }

        return $transactionStatus;
    }

    /**
     * Construct an XML object common headers calculated.
     *
     * @param string $requestType				The type of message to construct.
     * @param array $signableFields				The fields used for signature, order is important
     * @return \SimpleXMLElement
     */
    protected function constructCommonXml($requestType, $signableFields = array())
    {
        $timestamp = gmdate('Y-m-d\TH:i:s.000\Z');

        $message = $timestamp;
        foreach ($signableFields as $value) {
            $message .= $value;
        }

        // remove whitespace
        $message = str_replace(array(" ", "\t", "\n"), array('', '', ''), $message);

        // Create the certificate fingerprint used to sign the message. This is passed in to identify
        // the public key of the merchant and is used for authentication and integrity checks.
        $privateCert = $this->options->getLocalPublicKey(); // PRIVATECERT

        $token = $this->extractFingerprintFromCertificate($this->options->getSecurePath() . DIRECTORY_SEPARATOR . $this->options->getLocalPublicKey());

        if (!$token) {
            return false;
        }

        // Calculate the base-64'd hash of the hashId and store it in tokenCode.
        $tokenCode = $this->signMessage(
                $this->options->getSecurePath() . DIRECTORY_SEPARATOR . $this->options->getLocalPrivateKey(), $this->options->getLocalPrivateKeyPass(), $message);

        if (!$tokenCode) {
            return false;
        }

        $tokenCode = base64_encode($tokenCode);

        $xml = new \SimpleXMLElement(sprintf('<?xml version="1.0" encoding="UTF-8"?><%s></%s>', $requestType, $requestType));
        $xml->addAttribute('xmlns', 'http://www.idealdesk.com/Message');
        $xml->addAttribute('version', '1.1.0');

        $xml->addChild('createDateTimeStamp', $timestamp);

        $Merchant = $xml->addChild('Merchant');
        $Merchant->addChild('merchantID', $this->options->getMerchantId());
        $Merchant->addChild('subID', $this->options->getSubId());
        $Merchant->addChild('authentication', 'SHA1_RSA');
        $Merchant->addChild('token', $token);
        $Merchant->addChild('tokenCode', $tokenCode);

        return $xml;
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

            case 'DirectoryRes':
                /*
                 * <?xml version="1.0" encoding="UTF-8"?>
                 * <DirectoryRes xmlns="http://www.idealdesk.com/Message" version="1.1.0">
                 *   <createDateTimeStamp>2012-04-25T15:19:18.731Z</createDateTimeStamp>
                 * <Acquirer>
                 *   <acquirerID>0050</acquirerID>
                 * </Acquirer>
                 * <Directory>
                 * 	<directoryDateTimeStamp>2007-03-20T00:00:00.000Z</directoryDateTimeStamp>
                 * 	<Issuer>
                 * 		<issuerID>0151</issuerID>
                 * 		<issuerName>Issuer Simulator</issuerName>
                 * 		<issuerList>Short</issuerList>
                 * 	</Issuer>
                 * </Directory>
                 * </DirectoryRes>
                 */

                $result = new IssuersCollection();
                foreach ($xml->Directory->children() as $Issuer) {
                    if ($Issuer->getName() != 'Issuer') {
                        continue;
                    }

                    $issuerEntry = new Issuer();
                    $issuerEntry->setId((string) $Issuer->issuerID);
                    $issuerEntry->setName((string) $Issuer->issuerName);
                    $issuerEntry->setType((string) $Issuer->issuerList);
                    $result->addIssuer($issuerEntry);
                }

                break;

            /**
             * <AcquirerTrxRes xmlns="http://www.idealdesk.com/Message" version="1.1.0">
             *     <createDateTimeStamp>2012-04-26T15:54:14.567Z</createDateTimeStamp>
             *   <Acquirer>
             *     <acquirerID>0050</acquirerID>
             *   </Acquirer>
             *   <Issuer>
             *     <issuerAuthenticationURL>https://idealtest.secure-ing.com/ideal/issuerSim.do?trxid=0050000057780787&amp;ideal=prob</issuerAuthenticationURL>
             *   </Issuer>
             *   <Transaction>
             *     <transactionID>0050000057780787</transactionID>
             *     <purchaseID>123</purchaseID>
             *   </Transaction>
             * </AcquirerTrxRes>
             */
            case 'AcquirerTrxRes':

                $instance->setIssuerUrl(html_entity_decode((string) $xml->Issuer->issuerAuthenticationURL));
                $instance->setTransactionId((string) $xml->Transaction->transactionID);

                $result = $instance;

                break;

            case 'AcquirerStatusRes':
                /**
                 *
                 * <AcquirerStatusRes xmlns="http://www.idealdesk.com/Message" version="1.1.0">
                 *   <createDateTimeStamp>2012-04-27T08:22:42.128Z</createDateTimeStamp>
                 * <Acquirer>
                 *   <acquirerID>0050</acquirerID>
                 * </Acquirer>
                 * <Transaction>
                 *   <transactionID>0050000057795329</transactionID>
                 *   <status>Success</status>
                 *   <consumerName>Hr J A T Verfürth en/of Mw T V Chet</consumerName>
                 *   <consumerAccountNumber>P001234567</consumerAccountNumber>
                 *   <consumerCity>Sögel                   </consumerCity>
                 * </Transaction>
                 * <Signature>
                 *   <signatureValue>PPyd/MxHP5LY1XfYv/9AQpilqYFlwsGJG5vce0Z5rcQnB1ixoR/1KeEHvWZFr76ik0mwbn1QUjh66IziNWnGo/1VmCKjVJIU8FxiFP6XptgW0s14p7064OLcyDMuEooGfK/XfZdTYw6tNgP1OrxPGaLGcCn18b8Qi2CsJ19YUGg=</signatureValue>
                 *   <fingerprint>15941925B031BA9CF0F4E5B856EF462413B0AAEC</fingerprint>
                 * </Signature>
                 * </AcquirerStatusRes>
                 */
                $instance->getVendorData()->set('createDateTimeStamp', (string) $xml->createDateTimeStamp);
                $instance->getVendorData()->set('acquirerID', (string) $xml->acquirerID);

                $instance->setStatus((string) $xml->Transaction->status);
                $instance->setConsumerName((string) $xml->Transaction->setConsumerName);
                $instance->setConsumerAccountNumber((string) $xml->Transaction->consumerAccountNumber);
                $instance->setConsumerCity((string) $xml->Transaction->consumerCity);

                $instance->getVendorData()->set('signatureValue', (string) $xml->Signature->signatureValue);
                $instance->getVendorData()->set('fingerprint', (string) $xml->Signature->fingerprint);

                $result = $instance;
                break;
            case 'ErrorRes':

                /*
                 * <ErrorRes xmlns="http://www.idealdesk.com/Message" version="1.1.0">
                 *     <createDateTimeStamp>2012-04-25T14:53:54.275Z</createDateTimeStamp>
                 *     <Error>
                 *         <errorCode>AP1100</errorCode>
                 *         <errorMessage>MerchantID unknown</errorMessage>
                 *         <errorDetail>Field generating error: merchantID. MerchantID unknown</errorDetail>
                 *         <consumerMessage>Betalen met iDEAL is nu niet mogelijk. Probeer het later nogmaals of betaal op een andere manier.</consumerMessage>
                 *     </Error>
                 * </ErrorRes>
                 */

                $result = new Error();
                $result->setCode((string) $xml->Error->errorCode);
                $result->setMessage((string) $xml->Error->errorMessage);
                $result->setDetail((string) $xml->Error->errorDetail);
                $result->setConsumerMessage((string) $xml->Error->consumerMessage);
                break;
        }

        return $result;
    }

    /**
     * Create a certificate fingerprint
     *
     * @param string $filename	File containing the certificate
     * @return string	A hex string of the certificate fingerprint
     */
    protected function extractFingerprintFromCertificate($certificatePath)
    {
        $certificatePath = (realpath($certificatePath)) ? : false;

        if (!$certificatePath)
            return false;

        // Read in the certificate, then convert to X.509-style certificate
        // and export it for later use.
        $cert = file_get_contents($certificatePath);
        if (!$cert) {
            $this->log(Logger::ERR, sprintf('Could not read certificate [%s]. It may be invalid.', $certificatePath));
            return false;
        }

        $data = openssl_x509_read($cert);

        if (!$data) {
            $this->log(Logger::ERR, sprintf('Could not read certificate [%s]. It may be invalid.', $certificatePath));
            return false;
        }

        if (!openssl_x509_export($data, $data)) {
            $this->log(Logger::ERR, sprintf('Could not read certificate [%s]. It may be invalid.', $certificatePath));
            return false;
        }

        // strip ASCII armor
        $data = str_replace(array('-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'), array('', ''), $data);

        // Decode the public key.
        $data = base64_decode($data);

        // Digest the binary public key with SHA-1.
        $fingerprint = sha1($data);

        // Ensure all hexadecimal letters are uppercase.
        $fingerprint = strtoupper($fingerprint);

        return $fingerprint;
    }

    /**
     * Creates an SHA-1 digest of a message and signs the digest with
     * an RSA private key. The result is the signature.
     *
     * @param string 	$priv_keyfile	The file containing the private key
     * @param string 	$key_pass		The password required for the decryption of the key
     * @param string 	$data			The data to digest
     * @return string	The signature produced or "false" in case of error.
     */
    protected function signMessage($privateKeyPath, $key_pass, $data)
    {
        $privateKeyPath = realpath($privateKeyPath) ? : false;

        if (!$privateKeyPath)
            return false;

        // Disregard all whitespace
        $data = preg_replace("/\s/", "", $data);

        $certData = file_get_contents($privateKeyPath);

        $pkeyid = openssl_get_privatekey($certData, $key_pass);

        if (!$pkeyid) {
            $this->log(sprintf('Private key [%s] could not be extracted and may be invalid or the password is incorrect.', $privateKeyPath));

            return false;
        }

        // Signing with OpenSSL first digests the data, then signs the digest
        if (!openssl_sign($data, $signature, $pkeyid)) {
            $this->log(Logger::ERR, sprintf('Could not sign message using private key [%s].', $privateKeyPath));
            return false;
        }

        // free the key from memory
        openssl_free_key($pkeyid);


        return $signature;
    }

    /**
     * Verifies the authenticity and integrity of the message
     *
     * @param string $certfile		The location to the public certificate to verify against.
     * @param string $message		The message to verify. This data should already be binary, not base64.
     * @param string $signature		The signature claimed to be correct
     * @return boolean		true if ok, false if not ok. Reason given in ErrorResponse.
     */
    protected function verifyMessage($certPath, $data, $signature)
    {
        $certPath = realpath($certPath) ? : false;

        if (!$certPath)
            return false;

        $certData = file_get_contents($certPath);

        $pubkeyid = openssl_pkey_get_public($certData);
        if ($pubkeyid === false) {
            $this->log(Logger::ERR, sprintf('Public server signing certificate [%s] is invalid.', $certPath));
            return false;
        }

        // The internal function has two paths of execution:
        // 1. The $data is hashed with SHA-1.
        // 2. The $signature is decrypted with the public key.
        //
		// Both paths are compared against each other and verified if equal.
        $result = openssl_verify($data, $signature, $pubkeyid);

        openssl_free_key($pubkeyid);

        if ($result == 1) {
            // -1 = error, 0 = false, 1 = ok.
            return true;
        }

        $this->log(Logger::ALERT, sprintf('The validity of a message could not be determined.'));

        return false;
    }

    /**
     * Gets a valid certificate file name based on the certificate fingerprint.
     * Uses configuration items in the config file, which are incremented when new
     * security certificates are issued:
     * certificate0=ideal1.crt
     * certificate1=ideal2.crt
     * etc...
     *
     * @param string $fingerprint	A hexadecimal representation of a certificate's fingerprint
     * @return string	The filename containing the certificate corresponding to the fingerprint
     */
    protected function getCertificateFileName($remotePublicKeyFingerprint)
    {
        $count  = 0;
        $result = true;

        foreach ($this->options->getRemotePublicKey() as $certFilename) {
            $certPath = realpath($this->options->getSecurePath() . "/" . $certFilename) ? : false;
            if (!$certPath) {
                continue;
            }

            // Generate a fingerprint from the certificate in the file.
            $fingerprint = $this->extractFingerprintFromCertificate($certPath);
            if ($fingerprint == false) {
                // Could not create fingerprint from configured certificate.
                continue;
            }

            // fingerprint equal to the desired one
            if ($remotePublicKeyFingerprint == $fingerprint) {
                return $certPath;
            }
        }

        $this->log(Logger::ERR, sprintf('Could not find a remote public certificate with fingerprint [%s]', $fingerprint));

        return false;
    }

}
