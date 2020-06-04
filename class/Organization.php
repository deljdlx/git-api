<?php

namespace JDLX\GithubAPI;

class Organization extends Entity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var Team[]
     */
    protected $teams;

    /**
     * @var User[]
     */
    protected $members;

    /**
     * @var Repository[]
     */
    protected $repositories;

    public function __construct(Client $api, $name = null)
    {
        $this->setApi($api);
        if ($name) {
            $this->loadByName($name);
        }
    }

    public function loadByName($name)
    {
        $data = $this->api->getOrganization($name, false);
        $this->loadData($data);
    }

    /**

     * @return Team[]
     */
    public function getTeams()
    {
        if ($this->teams === null) {
            $this->teams = $this->api->getTeamsByOrganization($this->getName());
            foreach ($this->teams as $team) {
                $team->setOrganization($this);
            }
        }

        return $this->teams;
    }

    /**
     * @return Repository[]
     */
    public function getUserRepositories($login, $permissions = ['admin', 'pull', 'push'])
    {
        $userRepositories = [];

        $repositories = $this->getRepositories(100);

        foreach ($repositories as $repository) {
            echo "\t" . $repository->getFullQualifiedName() . PHP_EOL;
            $collaborators = $repository->getCollaborators($permissions);
            foreach ($collaborators as $collaborator) {
                if (mb_strtolower($collaborator->getLogin()) == mb_strtolower($login)) {
                    echo "\t\t" . $collaborator->getLogin() . "\t" . $collaborator->isAdmin() . " \n";
                    $userRepositories[] = $repository;
                } else {
                    echo "\t\tSKIP\t" . mb_strtolower($login) . "\t" . $collaborator->getLogin() . "\t" . $collaborator->isAdmin() . " \n";
                }
            }
        }

        return $userRepositories;
    }

    /**
     * @return Repository[]
     */
    public function searchRepositories($name)
    {
        return $this->api->searchRepositoriesInOrganization($this->getName(), $name);
    }

    /**
     * @return Repository[]
     */
    public function getRepositories($limit = null, $filter = null)
    {
        if ($this->repositories === null) {
            $this->repositories = $this->api->getRepositoriesByOrganization($this->getName(), $filter, $limit);
            foreach ($this->repositories as $repository) {
                $repository->setOrganization($this);
            }
        }
        return $this->repositories;
    }

    /**
     * @return Member[]
     */
    public function getMembers()
    {
        if ($this->members === null) {
            $this->members = $this->api->getOrganizationMembers(
                $this->getName()
            );
        }
        return $this->members;
    }

    public function loadData($data)
    {
        parent::loadData($data);

        $this->id = $data->id;
        $this->name = $data->name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }
}
