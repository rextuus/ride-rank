<?php

namespace App\Command;

use App\Service\Rcdb\Crawler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:crawler',
    description: 'Add a short description for your command',
)]
class TestCrawlerCommand extends Command
{
    public function __construct(private readonly Crawler $crawler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get roller coaster ID from argument or use default (4)
        $id = $input->getArgument('arg1') ? (int)$input->getArgument('arg1') : 4;

        $io->title("Fetching data for roller coaster ID: $id");

        try {
            $data = $this->crawler->fetchRollerCoaster($id);
dd($data);
            // Display basic information
            if (isset($data['name'])) {
                $io->section('Basic Information');
                $io->writeln("Name: " . $data['name']);

                if (isset($data['location'])) {
                    $location = implode(
                        ', ',
                        array_map(
                            function ($location) {
                                return implode(' - ', $location);
                            },
                            $data['location']
                        )
                    );

                    $io->writeln("Location: " . $location);
                }

                if (isset($data['status'])) {
                    $io->writeln("Status: " . $data['status']);
                }

                if (isset($data['manufacturer'])) {
                    $io->writeln("Manufacturer: " . $data['manufacturer']);
                }
            }

            // Display image information
            if (isset($data['images']) && isset($data['images']['default'])) {
                $io->section('Images');
                $io->writeln("Default Image: " . ($data['images']['default'] ?? 'N/A'));
                $io->writeln("Json: " . (json_encode($data['images']['rcdb_json']) ?? 'N/A'));

            } else {
                $io->warning("No image data found");
            }

            // Display other sections if available
            foreach (['tracks', 'trains', 'details', 'facts', 'history'] as $section) {
                if (isset($data[$section]) && !empty($data[$section])) {
                    if ($section === 'tracks') {
                        $elements = implode(
                            ', ',
                            array_map(
                                function ($location) {
                                    return implode(' - ', $location);
                                },
                                $data[$section]['Elements']
                            )
                        );
                        $data[$section]['Elements'] = $elements;
                    }

                    if ($section === 'trains') {
                        $elements = implode(
                            ', ',
                            array_map(
                                function ($location) {
                                    return implode(' - ', $location);
                                },
                                $data[$section]['Restraints']
                            )
                        );
                        $data[$section]['Restraints'] = $elements;

                        if (isset($data[$section]['Built by'])){
                            $elements = implode(
                                ', ',
                                array_map(
                                    function ($location) {
                                        return implode(' - ', $location);
                                    },
                                    $data[$section]['Built by']
                                )
                            );
                            $data[$section]['Built by'] = $elements;
                        }else{
                            $data[$section]['Built by'] = '';
                        }
                    }


                    $io->section(ucfirst($section));
                    foreach ($data[$section] as $key => $value) {
                        $io->writeln("$key: $value");
                    }
                }
            }

            $io->success("Data fetched successfully");
        } catch (\Exception $e) {
            $io->error("Error fetching data: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
