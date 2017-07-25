<?php

namespace Postcode;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use BeSimple\SoapClient\SoapClientBuilder;
use BeSimple\SoapClient\SoapClientOptionsBuilder;
use BeSimple\SoapCommon\SoapOptionsBuilder;

/**
 * Symfony command for testing SOAP
 * search postcodes by given towns name in UK.
 *
 * @author Tomas Polak <polak.tomas@gmail.com>
 */
class PostcodeCommand extends Command
{
    // Remote soap resource
    const REMOTE_WSDL_UK = 'http://www.webservicex.net/uklocation.asmx?WSDL';

    // Limits for towns in args
    const TOWNS_LIMIT_MIN = 2;
    const TOWNS_LIMIT_MAX = 3;

    /**
     * Configure postcode command.
     * Adds towns in array args.
     */
    protected function configure()
    {
        $this->setName("postcode")
                ->setDescription("Return postcode of given cities by name.")
                ->addArgument(
                    'Towns',
                    InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                    'What is the name of towns?'
                  );
    }

    /**
     * Executes postcode command.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $towns = $input->getArgument('Towns');
        $formatter = $this->getHelper('formatter');

        if ((count($towns) >= self::TOWNS_LIMIT_MIN) && (count($towns) <= self::TOWNS_LIMIT_MAX)){
            try {
                $soapClient = $this->getPostcodeSoapClient();

                foreach ($towns as $town){
                    $postcodes = $this->getUKLocationByTown($soapClient, $town);

                    if (count($postcodes) > 0){
                        // Sucessful postcodes for town.
                        $output->writeln($town . ": " . implode(", ",$postcodes));
                    } else {
                        // Postcodes not found.
                        $warningMessages = array('There are no postcodes for town ' . $town);
                        $formattedBlock = $formatter->formatBlock($warningMessages, 'comment');
                        $output->writeln($formattedBlock);
                    }
                }
            } catch (\SoapFault $e){
                // Handling connection error.
                $errorMessages = array('Connection to soap wasnt established.');
                $formattedBlock = $formatter->formatBlock($errorMessages, 'error');
                $output->writeln($formattedBlock);
            }
        } else {
            // Count of town is out of limits.
            $errorMessages = array(
                'You need to enter from ' .
                self::TOWNS_LIMIT_MIN .
                ' to ' .
                self::TOWNS_LIMIT_MAX .
                ' towns.'
              );
            $formattedBlock = $formatter->formatBlock($errorMessages, 'error');
            $output->writeln($formattedBlock);
        }
    }

    /**
     * Gets soap client with postcode resource.
     * @return SoapClient
     */
    private function getPostcodeSoapClient()
    {
        $soapClientBuilder = new SoapClientBuilder();
        $soapClient = $soapClientBuilder->build(
            SoapClientOptionsBuilder::createWithDefaults(),
            SoapOptionsBuilder::createWithDefaults(self::REMOTE_WSDL_UK)
        );
        return $soapClient;
    }

    /**
     * Gets postcode for given town.
     * @param SoapClient $soapClient
     * @param String $town
     * @return Array
     */
    private function getUKLocationByTown($soapClient, $town)
    {
        // Soap call with params.
        $getUKLocationByTownRequest = new \stdClass;
        $getUKLocationByTownRequest->Town = $town;
        $soapResponse = $soapClient->soapCall('GetUKLocationByTown', [$getUKLocationByTownRequest]);

        // Parse response for PostCode
        preg_match_all("'&lt;PostCode&gt;(.*?)&lt;/PostCode&gt;'si", $soapResponse->getContent(), $postcodeMatch);

        return $postcodeMatch[1];
    }

}

?>
