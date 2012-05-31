<?php

namespace IDealService\Model;

class IssuersCollection implements \Iterator
{

    protected $issuers = array();

    public function getIssuers()
    {
        return $this->issuers;
    }

    /**
     * Adds Issuer to the collection
     */
    public function addIssuer($entry)
    {
        if ($entry instanceof Issuer) {
            $this->issuers[] = $entry;
        }
    }

    public function rewind()
    {
        reset($this->issuers);
    }

    public function current()
    {
        return current($this->issuers);
    }

    public function key()
    {
        return key($this->issuers);
    }

    public function next()
    {
        return next($this->issuers);
    }

    public function valid()
    {
        $key = key($this->issuers);

        return ($key !== NULL && $key !== FALSE);
    }

    public function toArray() {
        $issuers = array();
        foreach ($this as $key=>$issuer) {
            $issuers[$key] = $issuer->toArray();
        }
        return $issuers;
    }

}
