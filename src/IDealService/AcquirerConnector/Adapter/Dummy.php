<?php

namespace IDealService\AcquirerConnector\Adapter;

use Zend\Log\Logger,
    Zend\Stdlib\Parameters,
    IDealService\Model\Error,
    IDealService\Model\Issuer,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Transaction,
    IDealService\Model\TransactionStatus;

class Dummy extends AbstractAdapter
{

    /**
     * Issue a transaction request from the acquirer
     *
     * @return IssuersCollection | FALSE | Error
     */
    public function issueDirectoryRequest()
    {
        $issuersCollection = new IssuersCollection();

        $issuer = new Issuer();
        $issuer->setId('001');
        $issuer->setName('Dummy Issuer');

        $issuersCollection->addIssuer($issuer);

        return $issuersCollection;
    }

    /**
     * Issue a transaction request from the acquirer
     *
     * @param Transaction $transaction
     * @return IssuersCollection | FALSE | Error
     */
    public function issueTransactionRequest(Transaction $transaction)
    {
        $transaction->setIssuerUrl('http://somedomain.com/issuerurl');
        $transaction->setTransactionId('666');

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
        $transactionStatus->setStatus('SOME_STATUS');

        $transactionStatus->setConsumerName('Mr. Consumer');
        $transactionStatus->setConsumerAccountNumber('987');
        $transactionStatus->setConsumerCity('Consumer City');

        return $transactionStatus;
    }
}
