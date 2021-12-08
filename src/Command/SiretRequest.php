<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Description of SiretRequest
 *
 * @author flo
 */
class SiretRequest extends Command
{

    // the name of the command
    protected static $defaultName = 'app:siret';

    protected function configure()
    {
        $this->setDescription("Recherche de SIRET")
                ->addArgument('source')
                ->addArgument('sortie')
                ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Décalage', 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = HttpClient::create();
        $fch = fopen($input->getArgument('source'), 'r');
        $header = fgetcsv($fch);

        $offset = $input->getOption('offset');
        $lineCount = 2;

        if (2 == $offset) {
            $dump = fopen($input->getArgument('sortie'), "w");
            fputcsv($dump, ['source', 'siret', 'titre', 'adresse', 'code postal']);
        } else {
            $dump = fopen($input->getArgument('sortie'), "w+");
        }

        while ($row = fgetcsv($fch)) {

            if ((0 !== strlen(trim($row[2]))) && ($lineCount >= $offset)) {

                $recherche = "*** $lineCount : {$row[2]} {$row[3]} {$row[5]} ***";
                $output->writeln($recherche);

                $query = urlencode("{$row[2]} {$row[5]}");
                $url = "https://data.opendatasoft.com/api/records/1.0/search/?dataset=sirene_v3%40public&q=$query&rows=5&sort=datederniertraitementetablissement";
                $response = $client->request('GET', $url);
                $json = json_decode($response->getContent());

                if ($json->nhits == 1) {
                    $data = $json->records[0]->fields;
                    fputcsv($dump, [$recherche, "{$data->siret} ", $data->l1_adressage_unitelegale, $data->adresseetablissement, $data->codepostaletablissement]);
                } else {
                    fputcsv($dump, []);
                }

                sleep(1);
            }

            $lineCount++;
        }

        fclose($dump);

        return 0;
    }

}
