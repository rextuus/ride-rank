<?php

namespace App\Command;

use App\Entity\Coaster;
use App\Common\Entity\Enum\LocationType;
use App\Entity\Manufacturer;
use App\Entity\Track;
use App\Entity\TrackElement;
use App\Service\Rcdb\Crawler;
use App\Service\Rcdb\ImportService;
use App\Service\Rcdb\RcdbPaginatedListCrawler;
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
    public function __construct(
        private readonly Crawler $crawler,
        private readonly ImportService $importService,
        private readonly RcdbPaginatedListCrawler $listCrawler
    ) {
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
            
            $coaster = $this->importService->importFromCrawlerArray($id, $data, false);
            
            $io->success(sprintf('Coaster "%s" (ID: %d) imported successfully', $coaster->getName(), $coaster->getId()));

            return Command::SUCCESS;

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

    /**
     * @param array<string, mixed> $data
     */
    public function parseResponse(array $data): void
    {
        $name = $data['name'];
        $status = $data['status'];
        $manufacturerName = $data['manufacturer'];
        $images = $data['images'];

        $coaster = new Coaster();
        $coaster->setIdent($name);
        $coaster->setName($name);
        $coaster->setStatus($status);

        // Manufacturer
        $manufacturer = new Manufacturer();
        $manufacturer->setName($manufacturerName);
        $coaster->setManufacturer($manufacturer);

        // Images
        $coaster->setImages($images['rcdb_json']);
        $coaster->setRcdbImageUrl($images['default']);

        // Locations
        if (isset($data['location'])) {
            foreach ($data['location'] as $locData) {
                $location = new Location();
                $location->setName($locData['ident']);
                $location->setRcdbId($locData['id']);
                $location->setRcdbUrl($locData['url']);
                $coaster->addLocation($location);
            }
        }

        // Categories
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $catData) {
                $category = new Category();
                $category->setName($catData['ident']);
                if ($catData['id']) {
                    $category->setRcdbId($catData['id']);
                }
                $category->setRcdbUrl($catData['url']);
                $coaster->addCategory($category);
            }
        }

        // Track
        if (isset($data['tracks'])) {
            $track = new Track();
            $tData = $data['tracks'];

            // Helper to extract float from strings like "1276.3 ft"
            $extractFloat = function ($val) {
                if (!$val) return null;
                preg_match('/[0-9.]+/', $val, $matches);
                return isset($matches[0]) ? (float)$matches[0] : null;
            };

            $track->setLength($extractFloat($tData['Length'] ?? null));
            $track->setHeight($extractFloat($tData['Height'] ?? null));
            $track->setSpeed($extractFloat($tData['Speed'] ?? null));
            $track->setInversions((int)($tData['Inversions'] ?? 0));
            $track->setVerticalAngle((int)($extractFloat($tData['Vertical Angle'] ?? null)));

            if (isset($tData['Elements'])) {
                foreach ($tData['Elements'] as $index => $elData) {
                    $element = new TrackElement();
                    $element->setName($elData['ident']);
                    $element->setRcdbId($elData['id']);
                    $element->setRcdbUrl($elData['url']);
                    $element->setPosition($index);
                    $track->addElement($element);
                }
            }
            $coaster->setTrack($track);
        }

        // Trains
        if (isset($data['trains'])) {
            $train = new Train();
            $trData = $data['trains'];
            $train->setArrangement($trData['Arrangement'] ?? null);

            if (!empty($trData['Built by'])) {
                $builderData = $trData['Built by'][0]; // Take the first one for simplicity
                $builder = new Manufacturer();
                $builder->setName($builderData['ident']);
                $builder->setRcdbId($builderData['id']);
                $builder->setRcdbUrl($builderData['url']);
                $train->setBuiltBy($builder);
            }

            // Restraints are problematic because they are currently linked to Location
            // But for now we just show we have a place for them
            if (!empty($trData['Restraints'])) {
                $restraintData = $trData['Restraints'][0];
                $restraint = new Location();
                $restraint->setName($restraintData['ident']);
                $restraint->setRcdbId($restraintData['id']);
                $restraint->setType(LocationType::NOT_DETERMINED);
                $train->setRestraint($restraint);
            }

            $coaster->setTrain($train);
        }

        // Details, Facts, History
        foreach (['details' => 'detail', 'facts' => 'fact', 'history' => 'history'] as $key => $type) {
            if (isset($data[$key])) {
                foreach ($data[$key] as $content) {
                    $detail = new Detail();
                    $detail->setContent($content);
                    $detail->setType($type);
                    $coaster->addDetail($detail);
                }
            }
        }
    }
}
