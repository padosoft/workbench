<?php/** * Created by PhpStorm. * User: Alessandro * Date: 02/09/2016 * Time: 12:04 */namespace Padosoft\Workbench;use Padosoft\Workbench\WorkbenchSettings;use Illuminate\Console\Command;use File;use Config;use League\CommonMark\CommonMarkConverter;use GitWrapper\GitWrapper;use Symfony\Component\Process\ExecutableFinder;class WorkbenchApiGeneration{    private $workbenchSettings;    private $command;    private $phpBinary;    private $gitBinary;    /**     * WorkbenchApiGeneration constructor.     * @param \Padosoft\Workbench\WorkbenchSettings $workbenchSettings     * @param Command $command     */    public function __construct(WorkbenchSettings $workbenchSettings, Command $command){        $this->workbenchSettings = $workbenchSettings;        $this->command = $command;        $finder = new ExecutableFinder();        $this->gitBinary = '"'.str_replace("\\","/",$finder->find('git')).'"';        if (!$this->gitBinary) {            throw new GitException('Unable to find the Git executable.');        }        $this->phpBinary = '"'.str_replace("\\","/",$finder->find('php')).'"';        if (!$this->phpBinary) {            throw new Exception('Unable to find the Php executable.');        }    }    /**     *     */    public function apigeneration()    {        $source = \Padosoft\Workbench\Parameters\Dir::adjustPath($this->workbenchSettings->requested['dir']['valore'].$this->workbenchSettings->requested['domain']['valore']);        $destination = \Padosoft\Workbench\Parameters\Dir::adjustPath(Config::get('workbench.diraccess.'.$this->workbenchSettings->requested['dirtype']['valore'].'.doc').$this->workbenchSettings->requested['organization']['valore']).$this->workbenchSettings->requested['domain']['valore'];        exec($this->phpBinary.' '.\Padosoft\Workbench\Parameters\Dir::adjustPath(Config::get('workbench.common_dev_lib_path')).'apigen.phar generate --source '.$source.' --destination '.$destination.'/dev-master');        File::copyDirectory($destination.'/dev-master/resources/', $destination.'/resources/');        $readmepathsource = \Padosoft\Workbench\Parameters\Dir::adjustPath($source).'readme.md';        $readmepathdestination = \Padosoft\Workbench\Parameters\Dir::adjustPath($destination).'index.html';        $this->transformReadmeMd($readmepathsource, $readmepathdestination);        //$gitWrapper = new GitWrapper();        //$gitWorkingCopy=$gitWrapper->init($destination,[]);        $gitSimpleWrapper = new GitSimpleWrapper($destination,null);        $gitSimpleWrapper->git("init");        $extension = ($this->workbenchSettings->requested["git"]["valore"]==Parameters\Git::BITBUCKET ? "org" : "com");        $gitSimpleWrapper->git("remote add origin https://".$this->workbenchSettings->requested['user']['valore'].":".$this->workbenchSettings->requested['password']['valore']."@".$this->workbenchSettings->requested["git"]["valore"].".". $extension ."/".$this->workbenchSettings->requested['organization']['valore']."/".$this->workbenchSettings->requested['packagename']['valore'].".git" );        $gitSimpleWrapper->git("checkout -b gh-pages");        $gitSimpleWrapper->git("add .");        $gitSimpleWrapper->git("commit -m Workbench commit");        $gitSimpleWrapper->git("push origin gh-pages");    }    /**     *     */    public function apiSamiGeneration()    {        $source = \Padosoft\Workbench\Parameters\Dir::adjustPath($this->workbenchSettings->requested['dir']['valore'].$this->workbenchSettings->requested['domain']['valore']);        $destination = \Padosoft\Workbench\Parameters\Dir::adjustPath(Config::get('workbench.diraccess.'.$this->workbenchSettings->requested['dirtype']['valore'].'.doc').$this->workbenchSettings->requested['organization']['valore']).$this->workbenchSettings->requested['domain']['valore'];        $samistring = $this->phpBinary.' '.\Padosoft\Workbench\Parameters\Dir::adjustPath(Config::get('workbench.common_dev_lib_path')).'sami.phar update '.$source.'sami_config.php';        exec($samistring);        //echo __DIR__;        File::copyDirectory(__DIR__.'/resources/resources/', $destination.'/resources/'); //todo cambia da dove prendi resources        $readmepathsource = \Padosoft\Workbench\Parameters\Dir::adjustPath($source).'readme.md';        $readmepathdestination = \Padosoft\Workbench\Parameters\Dir::adjustPath($destination).'index.html';        $this->transformReadmeMd($readmepathsource, $readmepathdestination);        $gitSimpleWrapper = new GitSimpleWrapper($destination,null);        $gitSimpleWrapper->git("init");        $extension = ($this->workbenchSettings->requested["git"]["valore"]==Parameters\Git::BITBUCKET ? "org" : "com");        try {            $gitSimpleWrapper->git("remote rm origin");            $this->command->line("delete origin");        }        catch (\Exception $e) {            $this->command->line("delete origin");        }        $gitSimpleWrapper->git("remote add origin https://".$this->workbenchSettings->requested['user']['valore'].":".$this->workbenchSettings->requested['password']['valore']."@".$this->workbenchSettings->requested["git"]["valore"].".". $extension ."/".$this->workbenchSettings->requested['organization']['valore']."/".$this->workbenchSettings->requested['packagename']['valore'].".git" );        $this->command->line("gh-pages search");        $output = "";        try {            $this->command->line("$destination");            $output = $gitSimpleWrapper->git("rev-parse --quiet --verify gh-pages");        }        catch (\Exception $e) {                $output = "";                //$gitWrapper->getDispatcher()                //dd($e->getMessage() ."\r\n". $e->getTraceAsString());            }        $this->command->line("output is: ". implode("",$output));        if(implode("",$output)=="") {            $gitSimpleWrapper->git("checkout -b gh-pages");            $this->command->line("Create and checkout to gh-pages");        }        if(implode("",$output)!="") {            $gitSimpleWrapper->git("checkout gh-pages");            $this->command->line("Checkout to gh-pages");        }        try {            $gitSimpleWrapper->git('add .');        }        catch (\Exception $e) {        }        try {            $gitSimpleWrapper->git('commit "Workbench commit"');        }        catch (\Exception $e) {        }        $gitSimpleWrapper->git("push origin gh-pages");        $this->command->line("pushed gh-pages");        try {            $gitSimpleWrapper->git("remote rm origin");            $this->command->line("delete origin");        }        catch (\Exception $e) {            $this->command->line("delete origin");        }        //sleep(3);    }    /**     * @param $readmepathsource     * @param $readmepathdestination     */    public function transformReadmeMd($readmepathsource,$readmepathdestination) {        if(!File::exists($readmepathsource)) {            $this->command->error('File '.$readmepathsource.' not exist');            exit();        }        $dir = \Padosoft\Workbench\Parameters\Dir::adjustPath(__DIR__).'resources/index.html';        if(!File::exists($dir)) {            $this->command->error('File '.$dir.' not exist');            exit();        }        File::copy($dir,$readmepathdestination);        $index = file_get_contents($readmepathdestination);        $readme = file_get_contents($readmepathsource);        $converter = new CommonMarkConverter();        $index = str_replace("@@@readme", $converter->convertToHtml($readme),$index);        /*$documentation="<h1>API Documentation</h1><p>Please see API documentation at http://".$this->workbenchSettings->requested['organization']['valore'].".github.io/".$this->workbenchSettings->requested['packagename']['valore']."</p>";        $documentation_mod = "<a name=api-documentation ></a>"."<h1>API Documentation</h1>        <p>Please see API documentation at <a href ='http://".$this->workbenchSettings->requested['organization']['valore'].".github.io/".$this->workbenchSettings->requested['packagename']['valore']."/master/build/'>".$this->workbenchSettings->requested['packagename']['valore']."</a></p>";        $destination = File::dirname($readmepathdestination);        $documentation_mod = $documentation_mod."<ul>";        $documentation_mod = $documentation_mod."<li><a href = 'https://".$this->workbenchSettings->requested['organization']['valore'].".github.io/".$this->workbenchSettings->requested['packagename']['valore']."/master'>master</a></li>";        $documentation_mod = $documentation_mod."</ul>";        $index = str_replace($documentation, $documentation_mod,$index);*/        $index = str_replace('@@@package_name', $this->workbenchSettings->requested['packagename']['valore'],$index);        $index = str_replace('@@@organization', $this->workbenchSettings->requested['organization']['valore'],$index);        file_put_contents($readmepathdestination, $index);    }}