<?php

declare(strict_types=1);

namespace App\Service\Rcdb;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * Crawler for fetching and parsing roller coaster data from rcdb.com
 *
 * This service uses Symfony's HTTP Client to fetch roller coaster data from rcdb.com
 * and parses the HTML content to extract useful information about the roller coaster.
 *
 * Example usage:
 * ```php
 * // Inject the crawler service in your controller or service
 * public function __construct(private Crawler $crawler) {}
 *
 * // Fetch roller coaster data by ID
 * $data = $this->crawler->fetchRollerCoaster(4);
 * ```
 *
 * The returned data is an array containing various pieces of information about the roller coaster:
 * - name: The name of the roller coaster
 * - location: An array of location information (park, city, state, country)
 * - status: The operational status of the roller coaster
 * - categories: An array of categories the roller coaster belongs to
 * - manufacturer: The manufacturer of the roller coaster
 * - tracks: An array of track specifications (length, height, drop, speed, inversions, duration, elements)
 * - trains: An array of train information (arrangement, restraints, builder)
 * - details: An array of additional details (former status, capacity)
 * - facts: An array of interesting facts about the roller coaster
 * - history: An array of historical information about the roller coaster
 *
 * Requirements:
 * - Symfony HTTP Client (symfony/http-client)
 * - Symfony DomCrawler (symfony/dom-crawler)
 *
 * @see https://rcdb.com/ The Roller Coaster Database
 */
class Crawler
{
    private const BASE_URL = 'https://rcdb.com';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
        private ?ErrorSummaryService $errorSummary = null
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function fetchRollerCoaster(int $id): array
    {
        $url = sprintf('%s/%d.htm', self::BASE_URL, $id);
        $this->logger->info('Fetching roller coaster data', ['id' => $id, 'url' => $url]);

        try {
            return $this->fetchAndParseUrl($url, $id);
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch roller coaster data', [
                'id' => $id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(sprintf('Failed to fetch roller coaster data for ID %d: %s', $id, $e->getMessage()), 0, $e);
        }
    }

    private function isParkPage(DomCrawler $domCrawler): bool
    {
        // Look for the "Parks in der Nähe" or "Nearby Parks" link which indicates this is a park page
        $parkLink = $domCrawler->filter('a[href*="/r.htm?ot=3"]');
        return $parkLink->count() > 0;
    }

    /**
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    private function fetchAndParseUrl(string $url, int $id): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();

            $this->logger->debug('Successfully fetched content', ['url' => $url]);


            return  $this->parseContent($content, $id);

        } catch (ExceptionInterface $e) {
            $this->logger->error('HTTP request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(sprintf('Failed to fetch content from %s: %s', $url, $e->getMessage()), 0, $e);
        } catch (Exception $e) {
            $this->logger->error('Failed to parse content', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(sprintf('Failed to parse content from %s: %s', $url, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseContent(string $content, int $id): array
    {
        try {
            $domCrawler = new DomCrawler($content);

            // Check if this is a park page instead of a coaster page
            if ($this->isParkPage($domCrawler)) {
                $this->logger->warning('Page is a park page, not a coaster page', ['id' => $id]);
                $this->errorSummary?->logError($id, 'This is a park page, not a coaster page');
                throw new IsParcEntryException(sprintf('ID %d points to a park page, not a coaster page', $id));
            }

            $data = [];

            $this->logger->debug('Parsing HTML content');

            // Extract name
            try {
                $nameElement = $domCrawler->filter('#feature h1');
                if ($nameElement->count() > 0) {
                    $data['name'] = $nameElement->text();
                    $this->logger->debug('Extracted name', ['name' => $data['name']]);
                } else {
                    $this->logger->warning('Name element not found');
                    $this->errorSummary?->logError($id, 'Name element not found');
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract name', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract name: ' . $e->getMessage());
            }

            // Extract location
            try {
                $locationElements = $domCrawler->filter('#feature a[href^="/location.htm"], #feature a[href$=".htm"]:not([href^="/g.htm"])');
                if ($locationElements->count() > 0) {
                    $data['location'] = [];
                    $locationElements->each(function (DomCrawler $node) use (&$data) {
                        if (strpos($node->attr('href'), '/location.htm') !== false ||
                            (strpos($node->attr('href'), '.htm') !== false && strpos($node->attr('href'), '/g.htm') === false)) {

                            $url = $node->attr('href');
                            preg_match('~id=(\d+)~', $url, $matches);
                            if (count($matches) === 0) {
                                preg_match('~/(\d+).htm~', $url, $matches);
                            }
                            $id = (int) $matches[1];

                            $location = [
                                'ident' => $node->text(),
                                'id' => $id,
                                'url' => $url,
                            ];
                            $data['location'][] = $location;
                        }
                    });
                    $this->logger->debug('Extracted location', ['location' => $data['location']]);
                } else {
                    $this->logger->warning('Location elements not found');
                    $this->errorSummary?->logError($id, 'Location elements not found');
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract location', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract location: ' . $e->getMessage());
            }

            // Extract status
            try {
                $statusElement = $domCrawler->filter('#feature p');
                if ($statusElement->count() > 0) {
                    $data['status'] = $statusElement->text();

                    // Extract all datetime values from time elements
                    $timeElements = $statusElement->filter('time');
                    $data['statusDate'] = [];

                    if ($timeElements->count() > 0) {
                        $timeElements->each(function (DomCrawler $node) use (&$data) {
                            $datetime = $node->attr('datetime');
                            if ($datetime !== null) {
                                $data['statusDate'][] = $datetime;
                            }
                        });
                        $this->logger->debug('Extracted dates', ['dates' => $data['statusDate']]);
                    }

                    $this->logger->debug('Extracted status', ['status' => $data['status']]);
                } else {
                    $this->logger->warning('Status element not found');
                    $this->errorSummary?->logError($id, 'Status element not found');
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract status', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract status: ' . $e->getMessage());
            }

            // Extract categories
            try {
                $categoryElements = $domCrawler->filter('#feature ul.ll li a');
                if ($categoryElements->count() > 0) {
                    $data['categories'] = [];
                    $categoryElements->each(function (DomCrawler $node) use (&$data) {

                        $url = $node->attr('href');
                        preg_match('~id=(\d+)~', $url, $matches);
                        if (count($matches) === 0) {
                            preg_match('~/(\d+).htm~', $url, $matches);
                        }
                        $id = null;
                        if (count($matches) > 0) {
                            $id = (int) $matches[1];
                        }

                        $location = [
                            'ident' => $node->text(),
                            'id' => $id,
                            'url' => $url,
                        ];


                        $data['categories'][] = $location;
                    });
                    $this->logger->debug('Extracted categories', ['count' => count($data['categories'])]);
                } else {
                    $this->logger->warning('Category elements not found');
                    $this->errorSummary?->logError($id, 'Category elements not found');
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract categories', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract categories: ' . $e->getMessage());
            }

            // Extract manufacturer
            try {
                $imagesElement = $domCrawler->filter('.scroll p a');
                if ($imagesElement->count() > 0) {
                    $data['manufacturer'] = $imagesElement->first()->text();
                    $this->logger->debug('Extracted manufacturer', ['manufacturer' => $data['manufacturer']]);
                } else {
                    $this->logger->warning('Manufacturer element not found');
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract manufacturer', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract manufacturer: ' . $e->getMessage());
            }

            // Extract specifications
            try {
                $specSections = $domCrawler->filter('section');
                $specSections->each(function (DomCrawler $section) use (&$data, $id) {
                    try {
                        $heading = $section->filter('h3');
                        if ($heading->count() > 0) {
                            $sectionName = $heading->text();
                            $this->logger->debug('Processing section', ['section' => $sectionName]);

                            switch ($sectionName) {
                                case 'Tracks':
                                    $trackData = $this->extractTableData($section);
                                    $data['tracks'] = $trackData;
                                    
                                    if (isset($trackData['Elements'])) {
                                        $elements = new DomCrawler($trackData['Elements']);
                                        $parsedElements = $this->getParsedElements($elements);
                                        $data['tracks']['Elements'] = $parsedElements;
                                    } else {
                                        $data['tracks']['Elements'] = [];
                                    }
                                    break;
                                case 'Züge':
                                case 'Trains':
                                    $trainData = $this->extractTableData($section);

                                    $data['trains'] = $trainData;

                                    $data['trains']['Restraints'] = [];
                                    if (isset($trainData['Restraints'])) {
                                        $restraints = new DomCrawler($trainData['Restraints']);

                                        $parsedRestraints = [];
                                        $restraints->filter('a')->each(function (DomCrawler $node) use (&$parsedRestraints) {
                                            $url = $node->attr('href');
                                            preg_match('~id=(\d+)~', $url, $matches);
                                            $id = null;
                                            if (count($matches) > 0) {
                                                $id = (int) $matches[1];
                                            }

                                            $parsedRestraints[] = [
                                                'ident' => $node->text(),
                                                'id' => $id,
                                                'url' => $url,
                                            ];
                                        });

                                        $data['trains']['Restraints'] = $parsedRestraints;
                                    }

                                    $data['trains']['Built by'] = [];
                                    if (isset($trainData['Built by'])) {
                                        $manufacturer = new DomCrawler($trainData['Built by']);
                                        $parsedManufacturer = $this->getParsedElements($manufacturer);

                                        $data['trains']['Built by'] = $parsedManufacturer;
                                    }

                                    break;
                                case 'Details':
                                    $data['details'] = $this->extractTableData($section);
                                    break;
                                case 'Fakten':
                                case 'Facts':
                                    $data['facts'] = $this->extractTextData($section);
                                    break;
                                case 'Geschichte':
                                case 'History':
                                    $data['history'] = $this->extractTextData($section);
                                    break;
                                default:
                                    $this->logger->debug('Skipping unknown section', ['section' => $sectionName]);
                            }
                        }
                    } catch (Exception $e) {
                        $this->logger->warning('Failed to process section', ['error' => $e->getMessage()]);
                        $this->errorSummary?->logError($id, 'Failed to process section: ' . $e->getMessage());
                    }
                });
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract specifications', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract specifications: ' . $e->getMessage());
            }


            try {
                $images = [];
                $imagesElement = $domCrawler->filter('#pic_json');
                $picJson = json_decode($imagesElement->html(), true);

                $images['rcdb_json'] = $picJson;

                $images['default'] = null;
                $images['images'] = null;
                if (isset($picJson['pictures']) && !empty($picJson['pictures'])){
                    $pictures = $picJson['pictures'];
                    $images['default'] = 'https://rcdb.com' . $pictures[0]['url'];
                }

                $data['images'] = $images;
            } catch (Exception $e) {
                $this->logger->warning('Failed to extract images', ['error' => $e->getMessage()]);
                $this->errorSummary?->logError($id, 'Failed to extract images: ' . $e->getMessage());
            }


            $this->logger->info('Successfully parsed HTML content', ['data_keys' => array_keys($data)]);
            return $data;
        } catch (Exception $e) {
            $this->logger->error('Failed to parse HTML content', ['error' => $e->getMessage()]);
            $this->errorSummary?->logError($id, 'Failed to parse HTML content: ' . $e->getMessage());
            throw new RuntimeException(sprintf('Failed to parse HTML content: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractTableData(DomCrawler $section): array
    {
        $data = [];

        try {
            $rows = $section->filter('table.stat-tbl tr');

            $rows->each(function (DomCrawler $row, $i) use (&$data) {
                try {
                    $th = $row->filter('th');
                    $td = $row->filter('td');

                    if ($th->count() > 0 && $td->count() > 0) {
                        $key = $th->text();
                        $value = $td->html();

                        // Clean up the value
                        $value = strip_tags($value, '<a><br>');
                        $data[$key] = $value;
                    }
                } catch (Exception $e) {
                    $this->logger->warning('Failed to process table row', ['row' => $i, 'error' => $e->getMessage()]);
                }
            });

            $this->logger->debug('Extracted table data', ['count' => count($data)]);
        } catch (Exception $e) {
            $this->logger->warning('Failed to extract table data', ['error' => $e->getMessage()]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractTextData(DomCrawler $section): array
    {
        $data = [];

        try {
            $rows = $section->filter('table.text tr');

            $rows->each(function (DomCrawler $row, $i) use (&$data) {
                try {
                    $td = $row->filter('td');
                    if ($td->count() > 0) {
                        $data[$i] = $td->text();
                    }
                } catch (Exception $e) {
                    $this->logger->warning('Failed to process text row', ['row' => $i, 'error' => $e->getMessage()]);
                }
            });

            $this->logger->debug('Extracted text data', ['count' => count($data)]);
        } catch (Exception $e) {
            $this->logger->warning('Failed to extract text data', ['error' => $e->getMessage()]);
        }

        return $data;
    }

    /**
     * @param DomCrawler $elements
     * @return array
     */
    private function getParsedElements(DomCrawler $elements): array
    {
        $parsedElements = [];
        $elements->filter('a')->each(function (DomCrawler $node) use (&$parsedElements) {
            $url = $node->attr('href');
            preg_match('~/(\d+).htm~', $url, $matches);
            $id = null;
            if (count($matches) > 0) {
                $id = (int) $matches[1];
            }

            $parsedElements[] = [
                'ident' => $node->text(),
                'id' => $id,
                'url' => $url,
            ];
        });
        return $parsedElements;
    }
}
