<?php

namespace Helper;

class SidHelper
{
    public function __construct($directory, $name)
    {
        $this->directory = $directory;
        $this->name  = $name;
    }
    /** Writes the process id to a pid file
        @return boolean true if the file was written
    */
    public function lock()
    {
        if ($this->isRunning())
            return false;
        $pidFile = $this->directory.$this->filename;
        if (!is_writable($this->directory)) {
            throw new \Exception('Can not write to folder: '.$this->directory);
        }
        if (file_exists($pidFile) && !is_writable($pidFile)) {
            throw new \Exception('Can not write to file: '.$pidFile);
        }
        $file = fopen($pidFile, 'w');
        if ($file === false) {
            return false;
        }
        fputs($file, getmypid());
        fclose($file);
        return true;
    }
    /** Checks if a process is still running
        @return boolean true if the process is still running
    */
    public function isRunning()
    {
        $this->checkDirExists();
        $pidFile = $this->directory.$this->filename;
        if (!file_exists($pidFile)) {
            return false;
        }
        $pid = file_get_contents($pidFile);
        // Empty pid file means the process is not running
        if (empty($pid)) {
            return false;
        }
        if (!is_dir('/proc/'))
        {
            /* OSX Workaround, not as good as using proc dir on linux, but oh well */
            exec('ps -Ac -o pid | awk \'{print $1}\' | grep \'^'.$pid.'$\'', $output, $return);
            if ($return == 0) {
                // process with the same id is still running
                return true;
            }
        }
        /* we assume the system is a linux system here */
        if (!is_dir('/proc/'.$pid)) {
            // process with the same id is still running
            return false;
        }
        return true;
    }
    /** Removes a pid file from the pid directory, kind of optional as if your
    process has already stopped we should already be able to detect it. It's
    nice to clean up though.
        @return boolean true if the file exists and was deleted
    */
    public function unlock()
    {
        $this->checkDirExists();
        $pidFile = $this->directory.$this->filename;
        if (!file_exists($pidFile)) {
            return false;
        }
        if (!is_writable($pidFile)) {
            throw new \Exception('Can not write to file: '.$pidFile);
        }
        return unlink($pidFile);
    }
    private function checkDirExists()
    {
        if (!is_dir($this->directory)) {
            throw new \Exception($this->directory.' directory does not exist for pid files');
        }
    }
    
    public function createNewSid()
    {
        //create a file with the pid extension and write into it the id (e.g home.pid)
        $sidName = $this->getHighestSid() + 1 ;
        file_put_contents($this->directory . $this->name, $sidName);
        
        //create a folder with the ID name
        mkdir($this->directory. "/sids/" . $sidName);
        
        file_put_contents($this->directory. "/sids/" . $sidName . "/info.log", "Started Sid at: " . date("Y-m-d H:i:s") ." \n" );
    }
    
    public function kill()
    {
        $pid = $this->directory.$this->name;
        if (file_exists($pid)) {
            unlink($pid);
            
            return true;    
        }

        return false;
    }
    
    public function updateSidInfo($info)
    {
        // check if pid exists
        if(!$sidName = $this->getCurrentSidId())
        {
            throw \Exception ("There is no session available");
        }
        
        file_put_contents($this->directory. "/sids/" . $sidName . "/info.log", 
                            date("Y-m-d H:i:s") . print_r($info, true) ." \n",
                            FILE_APPEND);
        
    }

    function check($fileName)
    {
        // what to check ? :)
    }
    
    /**
    * Checks to see if there is a pid and in case it exists returns its id
    * 
    * @return mixed
    */
    public function getCurrentSidId()
    {
        $pidFile = $this->directory.$this->name;
        if (!file_exists($pidFile)) {
            return false;
        }
        
        return file_get_contents($pidFile);
    }

    public function getSidDetails()
    {
	$start_file = $this->directory . "/sids/" . $this->getCurrentSidId() . "/start";
	if (!file_exists($start_file))
	{
	    return false;
	}
	
	return file_get_contents($startfile);
    }

    public function startNewCycle()
    {
	$startFile = $this->directory . "/sids/". $this->getCurrentSidId() . "/start";
	file_put_contents($startFile, date("D M j Y G:i:s"));

    }

    public function stopNewCycle()
    {
	$startFile = $this->directory . "/sids/". $this->getCurrentSidId() . "/stop";
	file_put_contents($startFile, date("D M j Y G:i:s"));

    }

    public function getSessionStartTime()
    {
	$startTime = file_get_contents($this->directory . "/sids/". $this->getCurrentSidId() . "/start");
	
	return strtotime($startTime);
    }
    
    
    /**
    * Returns the current Sessions number
    * 
    * @return int()
    */
    private function getHighestSid()
    {
        $i = array_diff(scandir($this->directory . "/sids/", 0), array(".","..", "README.md"));
        
        return max($i);
    }
}