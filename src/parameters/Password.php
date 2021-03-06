<?php
/**
 * Copyright (c) Padosoft.com 2016.
 */

namespace Padosoft\Workbench\Parameters;


use Padosoft\Workbench\Workbench;
use Padosoft\Workbench\Traits\Enumerable;
use Illuminate\Console\Command;
use Padosoft\HTTPClient\HttpHelperFacade;

class Password implements IEnumerable
{
    use Enumerable {
        Enumerable::isValidValue as isValidValueTrait;
    }

    const CONFIG = "git.password";

    private $command;
    private $requested;

    public function __construct(Command $command)
    {
        $this->command=$command;
        $this->requested=$this->command->workbenchSettings->requested;
    }

    public function read($silent)
    {

        if($silent && !$this->requested["password"]["valore-valido"] && !$this->requested["password"]["valore-valido-default"]){
            $this->exitWork("The password for git can't be void");
        }

        if($silent && !$this->requested["password"]["valore-valido"] && $this->requested["password"]["valore-valido-default"]){
            $this->requested["password"]["valore"]=$this->requested["password"]["valore-default"];
            $this->requested["password"]["valore-valido"]= true;
        }

        if(!$silent && !$this->requested["password"]["valore-valido"]){


            if($this->requested["password"]["valore-valido-default"])
            {
                if($this->command->confirm("Do you want use password in the config?","yes"))
                {
                    $this->requested["password"]["valore"] = $this->requested["password"]["valore-default"];
                    $this->requested["password"]["valore-valido"] = true;
                }
                else{
                    $this->requested["password"]["valore-valido"] = false;
                }

            }

            if(!$this->requested["password"]["valore-valido"]){
                $this->requested["password"]["valore"] = $this->command->secret('Git repository\'s password');

            }
            if(!$this->testPassword())
            {
                $this->command->error("Invalid username or password!");
                exit();
            }
            $this->requested["password"]["valore-valido"]= true;
        }
        $this->command->getWorkbenchSettings()->setRequested($this->requested);
    }

    private function exitWork($error)
    {
        $this->command->error($error);
        exit();
    }
    
    public static function isValidValue($valore)
    {
        if(!isset($valore) || trim($valore)=="")
        {
            return false;
        }
        return true;
    }

    public function testPassword()
    {

        if($this->requested["git"]["valore"]=='bitbucket') {
            $response = HttpHelperFacade::sendPostJsonWithAuth("https://api.bitbucket.org/",[],$this->requested["user"]["valore"],$this->requested["password"]["valore"]);

        }
        else {
            $response = HttpHelperFacade::sendPostJsonWithAuth("https://api.github.com/",[],$this->requested["user"]["valore"],$this->requested["password"]["valore"]);
        }


        if(strtoupper($response->psr7response->getReasonPhrase())=="UNAUTHORIZED")
        {
            return false;
        }
        return true;
    }

}