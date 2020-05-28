<?php

namespace JDLX\GithubAPI;


class Repository
{
    const GIT_NOTHING_TO_COMMIT = 'nothing to commit, working tree clean';
    private $method = 'git@github.com';
    private $name;
    private $path;


    private $properties = null;


    /**
     * @var Owner
     */
    private $owner;


    /**
    * @var Client
    **/
    protected $api;

    public function __construct(Client $api, $name)
    {
        $this->setApi($api);
        $this->name = $name;
    }



    public function create()
    {
        //curl -H "Authorization: token $fromToken" --header "Content-Type: application/json" -X POST --data '{"name":"'repository'","private":false,"has_issues":false,"has_projects":false,"has_wiki":false}' https://api.github.com/user/repos
        $this->api->createRepository($this->name);
        return $this;

    }


    public function getRepository()
    {
        return $this->method . ':' . $this->getOwner()->getName() . '/' . $this->getName();
    }

    
    public function getOrigin()
    {
        $current = getcwd();
        chdir($this->getPath());
        exec('git remote -v ', $lines);
        chdir($current);

        $urls = [];
        foreach($lines as $index => $line) {

            if(preg_match('`^origin`', $line)) {
                $line = trim($line);
                $type = preg_replace('`.*?\((.*?)\)$`', '$1', $line);
                $url =  preg_replace('`.*?(' . $this->method . ':.*?)\s.*`', '$1', $line);
                $urls[$type] = $url;
            }
        }
        return $urls;
    }

    public function setOrigin($origin, &$buffer = null)
    {
        $current = getcwd();
        chdir($this->getPath());
        exec('git remote set-url origin '.$origin, $lines);
        $buffer = implode('', $lines);
        chdir($current);
        return $this;
    }

    public function clone($path, &$buffer = null)
    {
        if(!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $this->setPath($path);
        exec('git clone ' . $this->getRepository() . ' '. $this->getPath(), $lines);

        $buffer = implode('', $lines);
        return $this;
    }

    public function push(&$buffer = null)
    {
        $current = getcwd();
        chdir($this->getPath());
        exec('git push', $lines);
        chdir($current);

        $buffer = implode("", $lines);
        return $this;
    }


    public function pullAll(&$buffer = null)
    {
        $current = getcwd();
        chdir($this->getPath());
        exec('git fetch --all', $lines);
        exec('git pull --all', $lines);
        chdir($current);
        $buffer = implode("", $lines);
        return $this;
    }


    public function commitRequired()
    {
        $current = getcwd();
        chdir($this->getPath());
        exec('git status', $lines);
        $buffer = implode('', $lines);
        chdir($current);
        if(preg_match('`'.static::GIT_NOTHING_TO_COMMIT.'`', $buffer)) {
            return true;
        }
        else {
            return false;
        }
    }


    public function loadFromObject($data)
    {
        $this->properties = $data;
        $this->owner = new Owner();
        $this->owner->loadFromObject($data->owner);
        return $this;
    }


    public function setApi(Client $api)
    {
        $this->api = $api;
        return $this;
    }



    public function getOwner()
    {
        return $this->owner;
        return $this->api->getOwner();
    }

    public function getName()
    {
        return $this->name;
    }


    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    
    public function getProperties()
    {
        return $this->properties;
    }
}