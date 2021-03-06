<?php
namespace Padosoft\Workbench\Parameters;

use Padosoft\Workbench\Workbench;
use Padosoft\Workbench\Traits\Enumerable;
use Illuminate\Console\Command;

/**
 * Class Action
 * @package Padosoft\Workbench
 */
class Action implements IEnumerable
{
    use Enumerable;

    const CREATE = "create";
    const DELETE = "delete";
    const CONFIG = "action";

    private $command;
    private $requested;

    public function __construct(Command $command)
    {
        $this->command=$command;
        $this->requested=$this->command->workbenchSettings->requested;
    }

    public function read($silent)
    {
        if($silent && !$this->requested["action"]["valore-valido"] && !$this->requested["action"]["valore-valido-default"]){
            $this->exitWork("Action is not correct, choice from 'create' or 'delete'");
        }

        if($silent && !$this->requested["action"]["valore-valido"] && $this->requested["action"]["valore-valido-default"]){
            $this->requested["action"]["valore"]=$this->requested["action"]["valore-default"];
            $this->requested["action"]["valore-valido"]= true;
        }


        if(!$silent && !$this->requested["action"]["valore-valido"]){
            $this->requested["action"]["valore"] = $this->command->choice('What do you want to do?', ['create', 'delete'],0);
            $this->requested["action"]["valore-valido"]= true;

        }
        $this->command->getWorkbenchSettings()->setRequested($this->requested);
    }

    private function exitWork($error)
    {
        $this->command->error($error);
        exit();
    }
}