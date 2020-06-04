<?php

namespace JDLX\GithubAPI;

class Repository extends Entity
{
    private $method = 'git@github.com';
    private $name;
    private $path;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Owner
     */
    private $owner;

    /**
     * @var Client
     **/
    protected $api;

    protected $collaborators;
    protected $contributors;

    protected $commits;

    public function __construct(Client $api, $name = null)
    {
        $this->setApi($api);
        if ($name) {
            $this->name = $name;
        }
    }

    public function getContributor()
    {
        if ($this->contributors === null) {
            $this->contributors = [];
            $this->contributors = $this->api->getRepositoryContributors($this->getFullQualifiedName());
        }
        return $this->contributors;
    }

    public function getCollaborators($permissions = ['admin'])
    {
        if ($this->collaborators === null) {
            $this->collaborators = [];
            $this->collaborators = $this->api->getRepositoryCollaborators($this->getFullQualifiedName());
        }
        return $this->collaborators;
    }

    public function getCommits()
    {
        if ($this->commits === null) {
            $this->commits = [];
            $this->commits = $this->api->getRepositoryCommits($this->getFullQualifiedName());
        }
        return $this->commits;
    }

    public function create()
    {
        //curl -H "Authorization: token $fromToken" --header "Content-Type: application/json" -X POST --data '{"name":"'repository'","private":false,"has_issues":false,"has_projects":false,"has_wiki":false}' https://api.github.com/user/repos
        $this->api->createRepository($this->name);

        return $this;
    }

    public function loadData($data)
    {
        parent::loadData($data);
        $this->name = $data->name;
        $this->owner = new Owner($this->api);
        $this->owner->loadData($data->owner);

        return $this;
    }

    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     *
     *
     * @return Owner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function getFullQualifiedName()
    {
        return $this->owner->getLogin() . '/' . $this->getName();
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSSHUrl()
    {
        return $this->data->ssh_url;
    }
}
