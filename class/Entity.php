<?php

namespace JDLX\GithubAPI;

class Entity
{
    /**
     * @var Client
     **/
    protected $api;

    protected $data;

    private $json;

    public function setApi(Client $api)
    {
        $this->api = $api;

        return $this;
    }

    public function loadJSON($json)
    {
        $this->json = $json;
        $this->loadData(json_decode($json));

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function loadData($data)
    {
        $this->data = $data;

        return $this;
    }
}
