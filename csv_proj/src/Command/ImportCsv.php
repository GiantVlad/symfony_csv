<?php
/**
 * Created by PhpStorm.
 * User: mac_mac
 * Date: 2019-03-04
 * Time: 22:47
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Translation\Loader\CsvFileLoader;
use App\Service\CSVImport;


class ImportCsv extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:import-csv';
    private  $import;

    public function __construct(CSVImport $import)
    {
        $this->import = $import;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Import items from Csv.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command Imports products from *.csv file.')
            // configure an argument
            ->addArgument('test_mode', InputArgument::OPTIONAL, 'In the test mode DB won\'t be rewrite.')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);

        $output->writeln('Param: '. $input->getArgument('test_mode'));

        // the value returned by someMethod() can be an iterator (https://secure.php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        $csv = array();
        $file = fopen('./stock.csv', 'r');

        while (($result = fgetcsv($file)) !== false)
        {
            $csv[] = $result;
        }

        fclose($file);

        $output->writeln(print_r($csv));


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->write('create a user.');
    }
}