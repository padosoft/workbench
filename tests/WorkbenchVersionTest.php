<?php
/**
 * Created by PhpStorm.
 * User: Alessandro
 * Date: 30/03/2016
 * Time: 12:18
 */

namespace Padosoft\WorkbenchVersion\Test;


use Illuminate\Support\Facades\Artisan;

class WorkbenchVersionTest extends \Padosoft\LaravelTest\TestBase
{

    protected $workbench;

    public function setUp()
    {
        //$this->workbench = new Workbench();
        parent::setUp();
    }


    /** @test */
    public function testHardWorkCreateNoOk()
    {


        Artisan::call('workbench:version',[


        ]);

    }


}
