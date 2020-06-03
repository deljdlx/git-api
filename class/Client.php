<?php

namespace JDLX\GithubAPI;

class Client
{
    private $userAgent = 'JDLX-Github-client';
    private $apiRootURL = 'https://api.github.com';
    private $graphQLRootURL = 'https://api.github.com/graphql';

    private $repositoryLimit = 100;

    private $token;
    private $owner;

    private $certPath = __DIR__ . '/cacert.pem';

    public function __construct($owner, $token)
    {
        $this->owner = $owner;
        $this->token = $token;

        //$this->certPath = __DIR__ . '/cacert.pem';
    }

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
     *
     *
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

    public function getOrganization($name)
    {
        $buffer = $this->request(
            'GET',
            '/orgs/' . $name
        );

        $organization = new Organization($this, $name);
        $organization->loadJSON($buffer);

        return $organization;
    }

    public function getOrganizationMembers($organization, $perPage = 200)
    {
        $users = [];

        $buffer = $this->request(
            'GET',
            '/orgs/' . $organization . '/members?per_page=' . $perPage
        );

        $data = json_decode($buffer);

        foreach ($data as $index => $userData) {
            $user = new User($this);
            $user->loadData($userData);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param string $organization
     * @param string $teamSlug
     *
     * @return void
     */
    public function getTeamMembers($organization, $teamSlug, $perPage = 200)
    {
        $buffer = $this->request(
            'GET',
            '/orgs/' . $organization . '/teams/' . $teamSlug . '/members?per_page=' . $perPage
        );

        $users = [];
        $data = json_decode($buffer);
        foreach ($data as $index => $userData) {
            $user = new User($this);
            $user->loadData($userData);
            $users[] = $user;
        }

        return $users;
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

    /**
     * Get teams.
     *
     * @param string $name
     *
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

    public function getOwner()
    {
        return $this->owner;
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

    public function duplicate(Client $client, $workingPath = null, $force = false)
    {
        if ($workingPath === null) {
            $workingPath = getcwd();
        }
        $repositories = $this->getRepositories();
        foreach ($repositories as $repository) {
            echo '===================================================' . PHP_EOL;
            $destination = $workingPath . '/' . $repository->getName();

            if ($force || !is_dir($destination)) {
                echo 'Duplicating ' . $repository->getName() . PHP_EOL;
                $repository->clone($destination);
            } else {
                echo 'Skiping cloning ' . $repository->getName() . PHP_EOL;
                $repository->setPath($destination);
            }

            if (!$client->repositoryExists($repository->getName())) {
                echo 'Creating repository ' . $repository->getName() . PHP_EOL;
                $client->createRepository($repository->getName());
            }

            $repository->setOrigin($repository->getMethod() . ':' . $client->getOwner() . '/' . $repository->getName());
            echo 'Pushing repository ' . $repository->getName() . PHP_EOL;
            $repository->push();
        }
    }

    public function deleteRepository($repository)
    {
        $endPoint = '/repos/' . $this->owner . '/' . $repository;
        $buffer = $this->request('DELETE', $endPoint);

        return $buffer;
    }

    public function getRepository($name)
    {
        $buffer = $this->request('GET', '/repos/' . $this->getOwner() . '/' . $name);
        if ($buffer) {
            $data = json_decode($buffer);
            $repository = new Repository($this, $name);
            $repository->loadData($data);

            return $data;
        } else {
            return false;
        }
    }

    public function repositoryExists($name)
    {
        $level = error_reporting(E_PARSE);
        $buffer = $this->request('GET', '/repos/' . $this->getOwner() . '/' . $name);
        error_reporting($level);

        if ($buffer) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return Repository[]
     */
    public function getRepositories($type = 'owner')
    {
        $repositories = [];
        do {
            $buffer = $this->request('GET', '/user/repos?per_page=' . $this->repositoryLimit . '&page=' . $page . '&type=' . $type);
            $list = json_decode($buffer);
            foreach ($list as $index => $data) {
                $repository = new Repository($this, $data->name);
                $repository->loadData($data);
                $repositories[] = $repository;
            }
        } while (count($list));

        return $repositories;
    }

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

    public function searchRepositoriesInOrganization($organization, $search)
    {
        return $this->searchRepositories('org:' . $organization . ' ' . $search . ' in:name');
    }

    /**
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
                    echo $i . "\t" . $data->name . PHP_EOL;
                } else {
                    echo "SKIP\t" . $data->name . PHP_EOL;
                }

                if ($limit) {
                    if ($i > $limit) {
                        break 2;
                    }
                }
            }
            ++$page;
            echo "======================================\n\n\n";
        } while (count($list));

        return $repositories;
    }

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

    public function flushRepositories($onlyOwner = true, $force = false)
    {
        echo 'COMMENTED FOR SECURITY PURPOSES' . PHP_EOL;
        echo __FILE__ . ':' . __LINE__;
        exit();

        $repositories = $this->getRepositories();
        foreach ($repositories as $repository) {
            if (!$onlyOwner || $repository->getProperties()->owner->login === $repository->getOwner()) {
                echo PHP_EOL . '===============================' . PHP_EOL;
                echo 'Deleting ' . $repository->getName() . PHP_EOL;
                $this->deleteRepository($repository->getName());
            }
        }
    }

    public function cloneRepository($repository, $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        exec('git clone git@github.com:' . $this->owner . '/' . $repository . ' ' . $path);
    }

    public function request($method, $endPoint, $data = null, $headers = [])
    {
        $options = [
            'http' => [
                'header' => 'Authorization: token ' . $this->token . "\r\n" .
                'User-Agent: ' . $this->userAgent . "\r\n",
                'method' => $method,
            ],
        ];

        foreach ($headers as $name => $value) {
            $options['http']['header'] .= $name . ': ' . $value . "\r\n";
        }

        if ($data !== null) {
            $options['http']['content'] = $data;
            $options['http']['header'] .= 'Content-Length: ' . strlen($data) . "\r\n";

            /*
            $options['ssl'] = array(
            'verify_peer' => true,
            'cafile' => $this->certPath,
            'ciphers' => 'HIGH:TLSv1.2:TLSv1.1:TLSv1.0:!SSLv3:!SSLv2',
            'CN_match' => 'api.github.com',
            'disable_compression' => true,
            );
            */
        }

        $context = stream_context_create($options);

        $url = $this->apiRootURL . $endPoint;
        $buffer = file_get_contents($url, false, $context);

        return $buffer;
    }

    public function graphQLRequest($query)
    {
        $variables = '';

        $json = json_encode(['query' => $query, 'variables' => $variables]);

        $chObj = curl_init();
        curl_setopt($chObj, CURLOPT_URL, $this->graphQLRootURL);
        curl_setopt($chObj, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chObj, CURLOPT_CUSTOMREQUEST, 'POST');
        //curl_setopt($chObj, CURLOPT_HEADER, true);
        //curl_setopt($chObj, CURLOPT_VERBOSE, true);
        curl_setopt($chObj, CURLOPT_POSTFIELDS, $json);

        curl_setopt(
            $chObj,
            CURLOPT_HTTPHEADER,
            [
                'User-Agent: ' . $this->userAgent,
                'Content-Type: application/json;charset=utf-8',
                'Authorization: bearer ' . $this->token
            ]
            );

        $response = curl_exec($chObj);
        return json_decode($response);
    }
}
