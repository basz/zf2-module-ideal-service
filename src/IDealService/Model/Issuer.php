<?php

namespace IDealService\Model;

class Issuer
{
    /**
     * @var String
     */
    protected $id;

    /**
     * @var String
     */
    protected $name;

    /**
     * @var String
     */
    protected $type;


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = (string) $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = (string) $type;
    }

    public function toArray() {
        return array('id'=>$this->id, 'name'=>$this->name, 'type'=>$this->type, );
    }

}
