<?php

namespace IDealService\AcquirerConnector\Adapter;

use IDealService\Model\Error,
    IDealService\Model\IssuersCollection,
    IDealService\Model\Transaction,
    IDealService\Model\TransactionStatus;

interface AdapterInterface
{

    /**
     * Issue a transaction request from the acquirer
     *
     * @return IssuersCollection | FALSE | Error
     */
    public function issueDirectoryRequest();

    /**
     * Issue a transaction request from the acquirer
     *
     * @param Transaction $transaction
     * @return IssuersCollection | FALSE | Error
     */
    public function issueTransactionRequest(Transaction $transaction);

    /**
     * Query the status of an particular transaction with the acquirer
     *
     * @param Transaction $transaction
     * @return TransactionStatus | FALSE | Error
     */
    public function issueStatusRequest(TransactionStatus $transactionStatus);
}
