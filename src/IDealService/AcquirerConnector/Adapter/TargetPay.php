<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Log\Logger,
    Zend\Stdlib\Parameters,
    IDealService\Model\Error,
    IDealService\Model\Issuer,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Transaction,
    IDealService\Model\TransactionStatus;

class TargetPay extends AbstractAdapter
{

    const DIRECTORY_REQUEST_URI   = 'https://www.targetpay.com/ideal/getissuers.php?format=xml';
    const TRANSACTION_REQUEST_URI = 'https://www.targetpay.com/ideal/start';
    const STATUS_REQUEST_URI      = 'https://www.targetpay.com/ideal/check';

    /**
     * Issue a transaction request from the acquirer
     *
     * @return IssuersCollection | FALSE | Error
     */
    public function issueDirectoryRequest()
    {
        $this->getOptions()->setAcquireUrl(self::DIRECTORY_REQUEST_URI);
        $this->getOptions()->setAcquireUrlTest(self::DIRECTORY_REQUEST_URI);

        $response = file_get_contents(self::DIRECTORY_REQUEST_URI);

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
            $transaction->getIssuerId(true),
            $this->options->getMerchantId(),
            $this->options->getSubId(),
            $returnUrl,
            $transaction->getPurchaseId(true),
            $transaction->getAmount(true),
            $transaction->getCurrency(true),
            $transaction->getLanguage(true),
            $transaction->getDescription(true),
            $transaction->getEntranceCode(true)
                ));


        if (!$xml) {
            return false;
        }

        $xml->Merchant->addChild('merchantReturnURL', $returnUrl);

        $Issuer = $xml->addChild('Issuer');
        $Issuer->addChild('issuerID', $transaction->getIssuerId(true));

        $xmlTrx = $xml->addChild('Transaction');
        $xmlTrx->addChild('purchaseID', $transaction->getPurchaseId(true));
        $xmlTrx->addChild('amount', $transaction->getAmount(true));
        $xmlTrx->addChild('currency', $transaction->getCurrency(true));
        $xmlTrx->addChild('expirationPeriod', $expirationPeriod);
        $xmlTrx->addChild('language', $transaction->getLanguage(true));
        $xmlTrx->addChild('description', $transaction->getDescription(true));
        $xmlTrx->addChild('entranceCode', $transaction->getEntranceCode(true));

        $response = $this->postXml($xml);

        return $this->extractResponse($response);
    }

    /**
     * Query the status of an particular transaction with the acquirer
     *
     * @param Transaction $transaction
     * @return TransactionStatus | FALSE | Error
     */
    public function issueStatusRequest($transactionId)
    {
        // Build the status request XML.
        $xmlMsg = $this->constructCommonXml("AcquirerStatusReq", array(
            $this->options->getMerchantId(),
            $this->options->getSubId(),
            $transactionId
                ));

        if (!$xmlMsg) {
            return false;
        }

        $xmlTrx = $xmlMsg->addChild('Transaction');
        $xmlTrx->addChild('transactionID', $transactionId);

        // Post the request to the server.
        $response = $this->postXml($xmlMsg);

        $response = $this->extractResponse($response);

        if (!($response instanceof StatusResponse)) // false || ErrorResponse
            return $response;

        // The verification of the response starts here.
        // The message as per the reference guide instructions.
        $message = $response->getCreateDateTimeStamp() . $response->getTransactionID() . $response->getStatus() . $response->getConsumerAccountNumber();
        $message = str_replace(array(" ", "\t", "\n"), array('', '', ''), $message);

        // The signed hash is base64 encoded and inserted into the XML as such
        $signature = base64_decode($response->getSignatureValue());

        // The merchant should have the public certificate stored locally.
        $certfile = $this->getCertificateFileName($response->getFingerprint());
        if (!$certfile) {
            return false;
        }

        // Verify the message signature
        $valid = $this->verifyMessage($certfile, $message, $signature);
        if (!$valid) {
            return false;
        }

        return $response;
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

        $token = $this->extractFingerprintFromCertificate($this->options->getSslPath() . DIRECTORY_SEPARATOR . $this->options->getLocalPublicKey());

        if (!$token) {
            return false;
        }

        // Calculate the base-64'd hash of the hashId and store it in tokenCode.
        $tokenCode = $this->signMessage(
                $this->options->getSslPath() . DIRECTORY_SEPARATOR . $this->options->getLocalPrivateKey(), $this->options->getLocalPrivateKeyPass(), $message);

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
            $certPath = realpath($this->options->getSslPath() . "/" . $certFilename) ? : false;
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

    protected function extractResponse($response)
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
        switch ($xml->getName()) {
            case 'issuers':

                /**
                 * <?xml version="1.0" encoding="UTF-8"?>
                 * <issuers>
                 * 	<issuer id="0031">ABN AMRO</issuer>
                 * 	<issuer id="0091">Friesland Bank</issuer>
                 * 	<issuer id="0721">ING</issuer>
                 * 	<issuer id="0021">Rabobank</issuer>
                 * 	<issuer id="0751">SNS Bank</issuer>
                 * 	<issuer id="0761">ASN Bank</issuer>
                 * 	<issuer id="0771">RegioBank</issuer>
                 * 	<issuer id="0511">Triodos Bank</issuer>
                 * 	<issuer id="0161">Van Lanschot</issuer>
                 * </issuers>
                 */
                $result = new IssuersCollection();

                foreach ($xml->children() as $Issuer) {
                    if ($Issuer->getName() != 'issuer') {
                        continue;
                    }

                    $issuerEntry = new Issuer();
                    $issuerEntry->setId((string) $Issuer->attributes()->id);
                    $issuerEntry->setName((string) $Issuer);
                    $result->addIssuer($issuerEntry);
                }

                break;
            default:
                $result = false;
        }

        return $result;
    }

}
