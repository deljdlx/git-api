<?php

namespace JDLX\GithubAPI\Traits;

use JDLX\GithubAPI\Commit;
use JDLX\GithubAPI\Repository;
use JDLX\GithubAPI\User;

trait ManageRepository
{
    /**
     * @return Repository[]
     */
    public function searchRepositories($query)
    {
        $repositories = [];
        $page = 1;
        do {
            $buffer = $this->request('GET', '/search/repositories?q=' . urlencode($query) . '&page=' . $page);

            $list = json_decode($buffer);

            foreach ($list->items as $data) {
                $repository = new Repository($this, $data->name);
                $repository->loadData($data);
                $repositories[] = $repository;
                $page++;
            }
        } while (count($list->items));

        return $repositories;
    }

    /**
     * @param string $name
     * @return Repository
     */
    public function getRepository($name)
    {
        $buffer = $this->request('GET', '/repos/' . $name);
        if ($buffer) {
            $data = json_decode($buffer);
            $repository = new Repository($this, $name);
            $repository->loadData($data);

            return $repository;
        } else {
            return false;
        }
    }

    /**
     * @param string $repository
     * @return Commit[]
     */
    public function getRepositoryCommits($repository)
    {
        $buffer = $this->request(
            'GET',
            '/repos/' . $repository . '/commits'
        );
        $data = json_decode($buffer);

        $commits = [];

        foreach ($data as $values) {
            $commit = new Commit($this);
            $commit->loadData($values);
            $commits[] = $commit;
        }
        return $commits;
    }

    public function getRepositoryContributors($repositoryName)
    {
        $users = [];
        $buffer = $this->request(
            'GET',
            '/repos/' . $repositoryName . '/contributors'
        );

        $data = json_decode($buffer);
        foreach ($data as $descriptor) {
            $user = new User($this);
            $user->loadData($descriptor);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param string $repositoryName
     * @return User[]
     */
    public function getRepositoryCollaborators($repositoryName)
    {
        $users = [];
        $buffer = $this->request(
            'GET',
            '/repos/' . $repositoryName . '/collaborators'
        );

        $data = json_decode($buffer);
        foreach ($data as $descriptor) {
            $user = new User($this);
            $user->loadData($descriptor);
            $users[] = $user;
        }

        return $users;
    }

    public function createRepository($repository)
    {
        //curl -H "Authorization: token $fromToken" --header "Content-Type: application/json" -X POST --data '{"name":"'repository'","private":false,"has_issues":false,"has_projects":false,"has_wiki":false}' https://api.github.com/user/repos

        $this->request(
            'POST',
            '/user/repos',
            '{"name":"' . $repository . '","private":false,"has_issues":false,"has_projects":false,"has_wiki":false}',
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    public function deleteRepository($fullQualifiedRepositoryName)
    {
        $endPoint = '/repos/' . $fullQualifiedRepositoryName;
        $buffer = $this->request('DELETE', $endPoint);
        return $buffer;
    }

    public function repositoryExists($fullQualifiedRepositoryName)
    {
        $level = error_reporting(E_PARSE);
        $buffer = $this->request('GET', '/repos/' . $fullQualifiedRepositoryName);
        error_reporting($level);

        if ($buffer) {
            return true;
        } else {
            return false;
        }
    }
}
