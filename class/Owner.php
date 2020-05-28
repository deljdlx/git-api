<?php

namespace JDLX\GithubAPI;

class Owner
{
    private $properties;



    public function loadFromObject($data)
    {
        $this->properties = $data;
        return $this;
    }


    public function getName()
    {
        return $this->properties->login;
    }

}
