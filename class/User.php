<?php

namespace JDLX\GithubAPI;

class User extends Entity
{
    /**
     * @var Repository[]
     */
    protected $repositories;

    /**
     * @var Repository[]
     */
    protected $contributedTo;

    /**
     * @var string
     */
    protected $login;

    public function __construct($client)
    {
        $this->setApi($client);
    }

    /**
     * @return Repository[]
     */
    public function getRepositories()
    {
        if ($this->repositories === null) {
            $this->repositories = $this->api->getUserRepositories($this->getLogin());
        }

        return $this->repositories;
    }

    /**
     * @return Repository[]
     */
    public function loadData($data)
    {
        parent::loadData($data);
        $this->login = $data->login;
    }

    /**
     * @return Repository[]
     */
    public function searchRepository($repositoryName)
    {
        return $this->api->getUserRepositoriesByName($this->getLogin(), $repositoryName);
    }

    /**
     * @return Repository[]
     */
    public function repositoriesContributedTo()
    {
        if ($this->contributedTo === null) {
            $this->contributedTo = $this->api->getUserContributedRepositories($this->getLogin());
        }

        return $this->contributedTo;
    }

    public function hasPermissions(array $permissions)
    {
        if (isset($this->data->permissions)) {
            foreach ($permissions as $permission) {
                foreach ($this->data->permissions as $index => $value) {
                    if ($permission == $index) {
                        if ($value) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function isAdmin()
    {
        if (isset($this->data->permissions)) {
            return (bool) $this->data->permissions->admin;
        }
    }

    public function getLogin()
    {
        return $this->login;
    }
}
