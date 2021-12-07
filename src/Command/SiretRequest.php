<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
                ->addArgument('fichier')
                ->addArgument('sortie');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = \Symfony\Component\HttpClient\HttpClient::create();
        $fch = fopen($input->getArgument('fichier'), 'r');
        $dump = fopen($input->getArgument('sortie'), "w");
        $header = fgetcsv($fch);

        $lineCount = 2;
        while ($row = fgetcsv($fch)) {
            if (0 === strlen(trim($row[2]))) {
                continue;
            }

            $recherche = "***** $lineCount : {$row[2]} {$row[3]} {$row[5]} *****";
            $output->writeln($recherche);
            fputcsv($dump, [$recherche]);

            $query = urlencode("{$row[2]} {$row[5]}");

            $url = "https://data.opendatasoft.com/api/records/1.0/search/?dataset=sirene_v3%40public&q=$query&rows=5&sort=datederniertraitementetablissement";
            $response = $client->request('GET', $url);
            $json = json_decode($response->getContent());
            foreach ($json->records as $choice) {
                $data = $choice->fields;
                if (isset($data->adresseetablissement)) {
                    $found = "{$data->siret} : {$data->l1_adressage_unitelegale} {$data->adresseetablissement} {$data->codepostaletablissement}";
                    $output->writeln($found);
                    fputcsv($dump, [$data->siret, $data->l1_adressage_unitelegale, $data->adresseetablissement, $data->codepostaletablissement]);
                }
            }

            fputcsv($dump, []);
            $output->writeln("\n");
            $lineCount++;
            sleep(1);
        }

        fclose($dump);

        return 0;
    }

}
