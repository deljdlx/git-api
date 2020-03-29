<?php

namespace JDLX\GithubAPI;


class Client
{
    private $userAgent = 'JDLX-Github-client';
    private $apiRootURL = 'https://api.github.com';
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
            array(
                'Content-Type' => 'application/json'
            )
        );
    }


    public function duplicate(Client $client, $workingPath = null, $force = false)
    {
        if($workingPath === null) {
            $workingPath = getcwd();
        }
        $repositories = $this->getRepositories();
        foreach($repositories as $repository)
        {
            echo "===================================================" . PHP_EOL;
            $destination = $workingPath . '/' . $repository->getName();
            

            if($force || !is_dir($destination)) {
                echo "Duplicating " . $repository->getName() . PHP_EOL;
                $repository->clone($destination);
            }
            else {
                echo "Skiping cloning " . $repository->getName() . PHP_EOL;
                $repository->setPath($destination);
            }

            if(!$client->repositoryExists($repository->getName())) {
                echo "Creating repository " . $repository->getName() . PHP_EOL;
                $client->createRepository($repository->getName());
            }

            $repository->setOrigin($repository->getMethod() . ':' . $client->getOwner() . '/' . $repository->getName());
            echo "Pushing repository " . $repository->getName() . PHP_EOL;
            $repository->push();

            

        }
    }


    public function deleteRepository($repository)
    {
        $endPoint = '/repos/'.$this->owner.'/'.$repository;
        $buffer =$this->request('DELETE', $endPoint);
        return $buffer;
    }


    public function getRepository($name)
    {
        $buffer = $this->request('GET', '/repos/'.$this->getOwner().'/'.$name);
        if($buffer) {
            $data = json_decode($buffer);
            $repository = new Repository($this, $name);
            $repository->loadFromObject($data);
            return $data;
        }
        else {
            return false;
        }
    }


    public function repositoryExists($name)
    {
        
        $level = error_reporting(E_PARSE);
        $buffer = $this->request('GET', '/repos/'.$this->getOwner().'/'.$name);
        error_reporting($level);

        if($buffer) {
            return true;
        }
        else {
            return false;
        }

    }


    /**
     * @return Repository[]
     */
    public function getRepositories()
    {
        $page = 1;
        $repositories = [];
        do {
        $buffer =$this->request('GET', '/user/repos?per_page=' . $this->repositoryLimit . '&page=' . $page . '&type=owner');
            $list = json_decode($buffer);
            foreach($list as $index => $data) {
                $repository = new Repository($this, $data->name);
                $repository->loadFromObject($data);
                $repositories[] = $repository;
            }
            $page++;
        } while(count($list) && $page < 3);

        return $repositories;

    }

    public function flushRepositories($onlyOwner = true, $force = false)
    {
        echo "COMMENTED FOR SECURITY PURPOSES" . PHP_EOL;
        echo __FILE__.':'.__LINE__; exit();

        $repositories = $this->getRepositories();
        foreach($repositories as $repository)
        {
            if(!$onlyOwner || $repository->getProperties()->owner->login === $repository->getOwner()) {
                echo PHP_EOL . "===============================".PHP_EOL;
                echo "Deleting " . $repository->getName() . PHP_EOL;
                $this->deleteRepository($repository->getName());
            }
        }
    }

    public function cloneRepository($repository, $path)
    {
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        exec('git clone git@github.com:' . $this->owner . '/' . $repository .' '. $path);
    }


    public function request($method, $endPoint, $data = null, $headers = [])
    {
        $options = array(
            'http' => array( 
                'header' =>
                    "Authorization: token " . $this->token . "\r\n".
                    "User-Agent: ".$this->userAgent."\r\n"
                ,
                'method' => $method,
            )
        );

        foreach($headers as $name => $value) {
            $options['http']['header'] .= $name.': ' . $value ."\r\n";
        }

        if($data !== null) {
            $options['http']['content'] = $data;
            $options['http']['header'] .= 'Content-Length: ' . strlen($data) ."\r\n";

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
        $buffer = file_get_contents($url,false, $context); 
        return $buffer;

    }
}



