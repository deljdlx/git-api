<?php

namespace JDLX\GithubAPI;

use Organisation;

class Client
{
    use Traits\ManageOrganization;
    use Traits\ManageRepository;
    use Traits\ManageUser;

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

    public function getOwner()
    {
        return $this->owner;
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
