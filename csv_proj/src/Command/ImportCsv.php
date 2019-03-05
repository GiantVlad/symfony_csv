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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Translation\Loader\CsvFileLoader;
use App\Entity\ProductData;


class ImportCsv extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:import-csv';
    private  $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

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
            'Product Creator',
            '============',
            '',
        ]);

        if (!empty($input->getArgument('test_mode'))) {
            $param = $input->getArgument('test_mode');
            if ($param === 'test') {
                $output->writeln('Warning! The script is running in test mode!');
            } else {
                $output->writeln('An invalid parameter was entered. Param: '. $input->getArgument('test_mode').' Pls, try again.');
                return;
            }
        }

        // the value returned by someMethod() can be an iterator (https://secure.php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        $csv = array();
        $file = fopen('./stock.csv', 'r');

        while (($result = fgetcsv($file)) !== false)
        {
            $csv[] = $result;
        }

        fclose($file);
        $entityManager = $this->container->get('doctrine')->getManager();
        $now = new \DateTime('now');
        foreach ($csv as $val) {
            $product = new ProductData();
            $product->setStrProductName('Keyboard')
                ->setIntProductPrice(2333)
                ->setIntProductStock(5)
                ->setStrProductDesc('Ergonomic and stylish!')
                ->setStrProductCode('VO-7856')
                ->setDtmAdded('2018-12-12 23:12:44')
                ->setDtmDiscontinued($now->format('Y-m-d h:i:s'));

            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $entityManager->persist($product);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();
        }

        //$output->writeln(print_r($csv));


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->write('You are about to ');
        $output->write('create a user.');
    }
}