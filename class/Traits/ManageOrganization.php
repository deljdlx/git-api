<?php

namespace JDLX\GithubAPI\Traits;

use JDLX\GithubAPI\Member;
use JDLX\GithubAPI\Organization;
use JDLX\GithubAPI\Repository;
use JDLX\GithubAPI\Team;
use JDLX\GithubAPI\User;

trait ManageOrganization
{
    /**
    * @param string $userName
    * @param string $organization
    * @return Repository[]
    */
    public function getUserRepositoriesInOrganization($userName, $organization)
    {
        $repositories = $this->getUserRepositories($userName);

        $filteredRepositories = [];
        foreach ($repositories as $index => $repository) {
            if ($repository->getOwner()->getLogin() == $organization) {
                $filteredRepositories[] = $repository;
            }
        }
        return $filteredRepositories;
    }

    /**
     * @param string $name
     * @param string $cast
     * @return Organization
     */
    public function getOrganization($name, $cast = Organization::class)
    {
        $buffer = $this->request(
            'GET',
            '/orgs/' . $name
        );

        if ($cast) {
            $organization = new $cast($this, $name);
            $organization->loadJSON($buffer);
            return $organization;
        } else {
            return json_decode($buffer);
        }
    }

    /**
     * @return Organization
     */
    public function getOrganizationMembers($organization)
    {
        $query = '
            query Test {
                organization(login:"' . $organization . '") {
                    id,
                    name,
                    login,
                    avatarUrl,
                    description,
                    descriptionHTML,
                    url,
                    membersWithRole (first:100) {
                        edges {
                            role,
                            node {
                                id,
                                name,
                                login,
                                avatarUrl,
                                email,
                                url
                            }
                        }
                    }
                }
            }
        ';

        $response = $this->graphQLRequest($query);

        $members = [];

        $data = $response->data->organization;
        $organization = new Organization($this);
        $organization->loadData($data);

        foreach ($response->data->organization->membersWithRole->edges as $value) {
            $member = new Member($this);
            $member->setRole($value->role);

            $member->setOrganization($organization);

            $user = new User($this);
            $user->loadData($value->node);
            $member->setUser($user);

            $members[] = $member;
            //$repos
        }
        return $members;
    }

    /**
     * @param string $name
     * @return Team[]
     */
    public function getTeamsByOrganization(string $name)
    {
        $teams = [];
        $buffer = $this->request(
            'GET',
            '/orgs/' . $name . '/teams'
        );

        $data = \json_decode($buffer);
        foreach ($data as $descriptor) {
            $team = new Team($this);
            $team->loadData($descriptor);
            $teams[] = $team;
        }

        return $teams;
    }

    /**
     * @param string $organization
     * @param string $filter
     * @param int $limit
     * @return Repository[]
     */
    public function getRepositoriesByOrganization($organization, $filter = null, $limit = null)
    {
        $page = 1;
        $repositories = [];
        $i = 0;
        do {
            $buffer = $this->request('GET', '/orgs/' . $organization . '/repos?per_page=' . $this->repositoryLimit . '&page=' . $page);

            $list = json_decode($buffer);
            foreach ($list as $data) {
                $validated = true;
                if (is_string($filter)) {
                    $validated = preg_match($filter, $data->name);
                } elseif (is_callable($filter)) {
                    $validated = call_user_func_array($filter, $data);
                }

                if ($validated) {
                    $repository = new Repository($this, $data->name);
                    $repository->loadData($data);
                    $repositories[] = $repository;
                    $i++;
                } else {
                }

                if ($limit) {
                    if ($i > $limit) {
                        break 2;
                    }
                }
            }
            ++$page;
        } while (count($list));

        return $repositories;
    }

    /**
     * @param string $organization
     * @param string $search
     * @return Repository[]
     */
    public function searchRepositoriesInOrganization($organization, $search)
    {
        return $this->searchRepositories('org:' . $organization . ' ' . $search . ' in:name');
    }
}
