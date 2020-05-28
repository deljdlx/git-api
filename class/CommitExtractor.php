<?php

namespace JDLX\GithubAPI;



class CommitExtractor
{

    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getBranches($fetch = false)
    {
        $oldPath = getcwd();
        chdir($this->path);

        if($fetch) {
            exec('git fetch');
        }
        
    
        exec('git branch -a', $lines);
        
        $branches = [];
        foreach($lines as $index => $line) {
            $buffer = trim($line);
            if(strpos($buffer, 'remotes/origin') !== false && strpos($buffer, 'remotes/origin/HEAD') === false) {
                $branch = preg_replace('`remotes/origin/(.*)`', '$1', $buffer);
                $branches[] = $branch;
            }
        }
        chdir($oldPath);
        return $branches;
    }

    public function getBranchCommits($branch)
    {

        $format = '--pretty=format:\'{%n  "commit": "%H",%n  "abbreviated_commit": "%h",%n  "tree": "%T",%n  "abbreviated_tree": "%t",%n  "parent": "%P",%n  "abbreviated_parent": "%p",%n  "refs": "%D",%n  "encoding": "%e",%n  "subject": "%s",%n  "sanitized_subject_line": "%f",%n  "body": "%b",%n  "commit_notes": "%N",%n  "verification_flag": "%G?",%n  "signer": "%GS",%n  "signer_key": "%GK",%n  "author": {%n    "name": "%aN",%n    "email": "%aE",%n    "date": "%aD"%n  },%n  "commiter": {%n    "name": "%cN",%n    "email": "%cE",%n    "date": "%cD"%n  }%n},\'';

        $commits = [];
        $oldPath = getcwd();

        chdir($this->path);

        exec('git status', $lines);
        $buffer = implode("\n", $lines);

        //echo $buffer;
        if(strpos($buffer, 'On branch '.$branch) === false) {
            exec('git checkout '.$branch . ' 2> /dev/null', $void, $result);
        }
        $lines = [];
        


        exec('git log '. $format, $lines);

        $buffer = implode("\n", $lines);
        $buffer = '['.substr($buffer, 0, -1).']';

        $commitLog = json_decode($buffer);

        foreach($commitLog as $index => $commit) {
            if(is_object($commit)) {
                $commits[$commit->commit] = $commit;
            }
        }

        chdir($oldPath);
        return $commits;
    }

    public function getCommitsPerBranches()
    {
        $branches = $this->getBranches();

        $commitsPerBranch = [];

        foreach($branches as $branch) {

            $commitsPerBranch[$branch] = [];

            $commits = $this->getBranchCommits($branch);

            foreach($commits as $index => $commit) {
                $commitsPerBranch[$branch][] = $commit;
            }
        }

        return $commitsPerBranch;
    }

}


