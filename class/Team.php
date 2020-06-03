<?php

namespace JDLX\GithubAPI;

class Team extends Entity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $slug;

    /**
     * @var Client
     **/
    protected $api;

    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @var User[]
     */
    protected $members = null;

    public function __construct(Client $api, $name = null)
    {
        $this->setApi($api);
        if ($name) {
            $this->name = $name;
        }
    }

    /**
     * @param Organization $organization
     *
     * @return static
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return User[]
     */
    public function getMembers()
    {
        if ($this->members === null) {
            $this->members = $this->api->getTeamMembers(
                $this->organization->getName(),
                $this->getSlug()
            );
        }

        return $this->members;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function loadData($data)
    {
        parent::loadData($data);
        $this->id = $data->id;
        $this->slug = $data->slug;
        $this->name = $data->name;

        return $this;
    }
}
