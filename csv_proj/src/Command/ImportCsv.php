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
use App\Entity\ProductData;


class ImportCsv extends Command
{
    // the name of the command (the part after "bin/console")
    /**
     * @var string
     */
    protected static $defaultName = 'app:import-csv';

    /**
     * @var ContainerInterface
     */
    private  $container;

    const COLUMN_NAMES = [
        'Product Code',
        'Product Name',
        'Product Description',
        'Stock',
        'Cost in GBP',
        'Discontinued'
    ];
    const MIN_PRICE = 5;
    const MAX_PRICE = 1000;
    const MIN_STOCK = 10;
    const CSV_PATH = './upload/stock.csv';


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

    /**
     * It executes command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            '==============================',
            'Import Products from CSV v.001',
            '==============================',
            '',
        ]);

        $param = false;

        // check CL params
        if (!empty($input->getArgument('test_mode'))) {
            $param = $input->getArgument('test_mode');
            if ($param !== 'test')  {
                $output->writeln([
                    '==============================',
                    "Error! An invalid parameter '{$input->getArgument('test_mode')}' has been received. Pls, try again.",
                    '==============================',
                    '',
                ]);
                return;
            }
        }

        try {

            $csv = $this->getCsv();

        } catch (\Exception $e) {
            $output->writeln([
                '==============================',
                "{$e->getMessage()}",
                '==============================',
                '',
            ]);
            return;
        }

        if(empty($csv)) {

            $output->writeln([
                '==============================',
                'Error in csv data!',
                'CSV file is empty, broken or invalid',
                '==============================',
                '',
            ]);
            return;
        }

        $entityManager = $this->container->get('doctrine')->getManager();
        $repository = $this->container->get('doctrine')->getRepository(ProductData::class);

        $now = new \DateTime('now');
        $first_row = true;

        $invalid_rows = [];
        $row_updated = $row_created = $row_processed = $row_successful = $row_skipped = 0;

        foreach ($csv as $row_n => $val) {

            if ($first_row) {
                if (count(self::COLUMN_NAMES) !== count($val)) {
                    $output->writeln([
                        '==============================',
                        'Error in CSV data!',
                        "Invalid header in CSV file",
                        '==============================',
                        '',
                    ]);
                    return;
                }
                foreach (self::COLUMN_NAMES as $column)

                    $key = array_search($column, $val);

                    if(!$key) {
                        $output->writeln([
                            '==============================',
                            'Error in CSV data!',
                            "Invalid column's name '{$column}' in CSV file",
                            '==============================',
                            '',
                        ]);
                        return;
                    }
                $first_row = false;
                continue;
            }

            $row_processed +=1;

            //validate count of columns in the row
            if (count(self::COLUMN_NAMES) !== count($val)) {
                array_push($invalid_rows, [
                    'mess'=>"Number of row ".($row_n+1).". Invalid count of columns. ".implode(" // ",$val),
                    'row_data' => implode(',', $val),
                ]);
                $row_skipped +=1;
                continue;
            }

            // Check if the product Code is not empty
            if (empty($val[0])) {
                array_push($invalid_rows, [
                    'mess'=>"Number of row ".($row_n+1).". Product code is empty.",
                    'row_data' => implode(',', $val),
                ]);
                $row_skipped +=1;
                continue;
            }

            // Check if price is float number
            if (!is_numeric($val[4]) || floatval(($val)[4]) < 0) {
                array_push($invalid_rows, [
                    'mess'=>"Number of row ".($row_n+1).". Price is not a valid float number: '".$val[4]."'",
                    'row_data' => implode(',', $val),
                    ]);
                $row_skipped +=1;
                continue;
            }

            // Check if stock is integer or empty
            if (!empty($val[3]) && (!is_numeric($val[3]) || (int)$val[3] < 0 || (int)$val[3] != floatval($val[3]))) {
                array_push($invalid_rows, [
                    'mess'=>"Number of row ".($row_n+1).". Stock is not integer: '{$val[3]}'",
                    'row_data' => implode(',', $val)
                ]);
                $row_skipped +=1;
                continue;
            }

            // Cheap items mast be skipped
            if (floatval($val[4]) < self::MIN_PRICE && (int)$val[3] < self::MIN_STOCK) {
                $output->writeln([
                    '==============================',
                    'The Item has been skipped',
                    implode(" // ",$val),
                    '==============================',
                    '',
                ]);
                $row_skipped +=1;
                continue;
            }

            // Expensive items must be skipped
            if (floatval($val[4]) > self::MAX_PRICE) {
                $output->writeln([
                    '==============================',
                    'The Item has been skipped',
                    implode(" // ",$val),
                    '==============================',
                    '',
                ]);
                $row_skipped +=1;
                continue;
            }

            $product = $repository->findOneBy(['strProductCode' => $val[0]]);;

            if (!$product) {
                $product = new ProductData();
                $product = $product->setStrProductCode($val[0]);

                $row_created +=1;
            } else {
                $row_updated +=1;
            }
            $product->setStrProductName($val[1])
                ->setIntProductPrice(floatval($val[4])*100)
                ->setIntProductStock((int)$val[3])
                ->setStrProductDesc($val[2])
                ->setDtmAdded($now);

            if (!empty($val[5])) {
                $product = $product->setDtmDiscontinued($now);
            }

            // if it is not in test mode
            if (!$param) {
                // actually executes the queries (i.e. the INSERT query)
                $entityManager->persist($product);
                $entityManager->flush();
            }
            $row_successful +=1;
        }

        // show errors
        if (!empty($invalid_rows)){
            $output->writeln([
                '==============================',
                'Errors in csv data:',
            ]);

            foreach ($invalid_rows as $row) {
                $output->writeln($row['mess']);
            }
            $output->writeln([
                '******************************'
            ]);

            foreach ($invalid_rows as $row) {
                $output->writeln($row['row_data']);
            }
            $output->writeln([
                '==============================',
                '',
            ]);
        }

        if (!empty($param)) {
            $output->writeln([
                '==============================',
                'Warning! The script is running in test mode!',
                '==============================',
                '',
            ]);
        }
        // outputs a message followed by a "\n"
        $output->writeln([
            '==============================',
            'Import finished!',
            "Processed {$row_processed}",
            "Skipped: {$row_skipped}",
            "Created: {$row_created}",
            "Updated: {$row_updated}",
            "Successful: {$row_successful}",
            '==============================',
        ]);
    }

    /**
     * It reads SCV file
     * @return array
     */
    private function getCsv() {

        $file = fopen(self::CSV_PATH, 'r');
        $csv =[];
        while (($result = fgetcsv($file)) !== false)
        {
            $csv[] = $result;
        }

        fclose($file);
        return $csv;
    }
}