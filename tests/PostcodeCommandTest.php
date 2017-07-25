<?php

use Postcode\PostcodeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

require_once  './vendor/autoload.php';

class PostcodeCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCorrectTowns()
    {
        $app = new Application();
        $app->add(new PostcodeCommand());

        $command = $app->find('postcode');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            'Towns'         => array("London", "Liverpool")
        ));

        $this->assertRegExp('/TN21, RG26, SP11/', $commandTester->getDisplay());
    }
}


?>
