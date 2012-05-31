<?php

namespace IDealService\Model;

class Error {
    private $code;
    private $message;
    private $detail;
    private $consumerMessage;

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getDetail()
    {
        return $this->detail;
    }

    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    public function getConsumerMessage()
    {
        return $this->consumerMessage;
    }

    public function setConsumerMessage($consumerMessage)
    {
        $this->consumerMessage = $consumerMessage;
    }
}
