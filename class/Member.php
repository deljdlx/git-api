<?php

namespace JDLX\GithubAPI;

use Organisation;

class Member extends Entity
{
    /**
     * @var string
     */
    private $role;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Organization
     */
    private $organization;

    public function __construct($client)
    {
        $this->setApi($client);
    }

    /**
     * @param stdclass $data
     * @return static
     */
    public function loadData($data)
    {
        parent::loadData($data);
        $organization = new Organization($this->api);
        $organization->loadData($data->organization);
        $this->setOrganization($organization);

        $user = new User($this->api);
        $user->loadData($data->user);
        $this->setUser($user);
        return $this;
    }

    public function isMember()
    {
        if ($this->role == 'MEMBER') {
            return true;
        }
        return false;
    }

    public function isAdmin()
    {
        if ($this->role == 'ADMIN') {
            return true;
        }
        return false;
    }

    /**
     * @param Organization $organization
     * @return static
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @param User $user
     * @return static
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get the value of organization
     *
     * @return  Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Get the value of user
     *
     * @return  User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get the value of role
     *
     * @return  string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set the value of role
     *
     * @param  string  $role
     *
     * @return  self
     */
    public function setRole(string $role)
    {
        $this->role = $role;

        return $this;
    }
}
