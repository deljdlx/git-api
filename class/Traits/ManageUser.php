<?php

namespace JDLX\GithubAPI\Traits;

use JDLX\GithubAPI\Commit;
use JDLX\GithubAPI\Repository;
use JDLX\GithubAPI\User;

trait ManageUser
{
    /**
    * @param string $userName
    * @return Repository[]
    */
    public function getUserContributedRepositories($userName)
    {
        $query = '
           query Test {
               user(login:"' . $userName . '") {
                   login,
                   id,
                   repositoriesContributedTo (first:100, includeUserRepositories: true, contributionTypes :[COMMIT, ISSUE, PULL_REQUEST, REPOSITORY]) {
                       nodes {
                           id,
                           name,
                           nameWithOwner,
                           description,
                           descriptionHTML,
                           sshUrl,
                           homepageUrl,
                           owner {
                               id,
                               login
                           }
                       }
                   }
               }
           }
       ';

        $response = $this->graphQLRequest($query);

        $repositories = [];
        foreach ($response->data->user->repositoriesContributedTo->nodes as $value) {
            $repository = new Repository($this);
            $repository->loadData($value);
            $repositories[] = $repository;
            //$repos
        }
        return $repositories;
    }

    /**
     * @param string $userName
     * @param string $organization
     * @return Repository[]
     */
    public function getUserRepositories($userName)
    {
        $query = '
            query Test {
                user(login:"' . $userName . '") {
                    login,
                    id,
                    repositories(first:100, affiliations: [OWNER,ORGANIZATION_MEMBER,COLLABORATOR]) {
                        nodes {
                            id,
                            name,
                            nameWithOwner,
                            description,
                            descriptionHTML,
                            sshUrl,
                            homepageUrl,
                            owner {
                                id,
                                login
                            }
                        }
                    }
                }
            }
        ';

        $response = $this->graphQLRequest($query);
        $repositories = [];

        foreach ($response->data->user->repositories->nodes as $value) {
            $repository = new Repository($this);
            $repository->loadData($value);
            $repositories[] = $repository;
            //$repos
        }
        return $repositories;
    }

    /**
     * @param string $userName
     * @param string $organization
     * @return Repository[]
     */
    public function searchUserRepositoriesByName($userName, $search)
    {
        $repositories = $this->getUserRepositories($userName);

        $filteredRepositories = [];
        foreach ($repositories as $index => $repository) {
            if (strpos($repository->getName(), $search) !== false) {
                $filteredRepositories[] = $repository;
            }
        }
        return $filteredRepositories;
    }

    /**
     * @param string $userName
     * @return Repository[]
     */
    public function getUserPublicRepositories($userName)
    {
        $buffer = $this->request('GET', '/users/' . $userName . '/repos?per_page=300&type=all');
        $data = json_decode($buffer);

        $repositories = [];
        foreach ($data as $index => $repositoryData) {
            $repository = new Repository($this);
            $repository->loadData($repositoryData);

            $repositories[] = $repository;
        }
        return $repositories;
    }

    /**
     * @param string $userName
     * @return User
     */
    public function getUser($userName)
    {
        $buffer = $this->request('GET', '/users/' . $userName);
        $data = json_decode($buffer);
        $user = new User($this);
        $user->loadData($data);
        return $user;
    }
}
