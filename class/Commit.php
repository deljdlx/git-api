<?php

namespace JDLX\GithubAPI;

use DateTime;

class Commit extends Entity
{
    /**
     * @var Datetime
     */
    protected $date;

    /**
     * @var User
     */
    protected $author;

    /**
     * @var User
     */
    protected $committer;

    public function __construct($client)
    {
        $this->setApi($client);
    }

    /**
     * @return Datetime
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getMessage()
    {
        return $this->data->commit->message;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return User
     */
    public function getCommiter()
    {
        return $this->committer;
    }

    public function loadData($data)
    {
        parent::loadData($data);

        $this->author = new User($this->api);
        if (isset($data->author->login)) {
            $this->author->loadData($data->author);
        }

        $this->committer = new User($this->api);
        if (isset($data->committer->login)) {
            $this->committer->loadData($data->committer);
        }

        $this->date = new DateTime($data->commit->committer->date);
        return $this;
    }
}
