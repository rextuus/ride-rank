<?php

declare(strict_types=1);

namespace App\Service\Rcdb;

use App\Service\Rcdb\Exception\RcdbFetchException;
use App\Service\Rcdb\Exception\RcdbParseException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RcdbListCrawler
{
    private const BASE_URL = 'https://rcdb.com';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Fetches ONE rcdb list page and extracts coaster + park IDs.
     *
     * Example:
     *   /r.htm?page=193&st=93&ot=2
     *
     * @return array{
     *     coasters: int[],
     *     parks: int[]
     * }
     *
     * @throws RcdbFetchException
     * @throws RcdbParseException
     */
    public function fetchIdsFromList(string $relativeUrl): array
    {
        $url = str_starts_with($relativeUrl, 'http')
            ? $relativeUrl
            : self::BASE_URL . $relativeUrl;

        $this->logger->info('Fetching RCDB list page', ['url' => $url]);

        try {
            $response = $this->httpClient->request('GET', $url);
            $html = $response->getContent();

            return $this->parseListPage($html);

        } catch (ExceptionInterface $e) {
            throw new RcdbFetchException(
                sprintf('Failed to fetch list page %s: %s', $url, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @return array{
     *     coasters: int[],
     *     parks: int[]
     * }
     */
    private function parseListPage(string $html): array
    {
        try {
            $crawler = new DomCrawler($html);

            $coasterIds = [];
            $parkIds = [];

            /**
             * Table layout:
             * 0 = camera
             * 1 = coaster
             * 2 = park
             */
            $crawler->filter('div.stdtbl table tbody tr')->each(
                function (DomCrawler $row) use (&$coasterIds, &$parkIds) {

                    $cells = $row->filter('td');
                    if ($cells->count() < 3) {
                        return;
                    }

                    // Coaster (column 2)
                    $coasterLink = $cells->eq(1)->filter('a[href$=".htm"]');
                    if ($coasterLink->count() > 0) {
                        $id = $this->extractIdFromHref($coasterLink->attr('href'));
                        if ($id !== null) {
                            $coasterIds[] = $id;
                        }
                    }

                    // Park (column 3)
                    $parkLink = $cells->eq(2)->filter('a[href$=".htm"]');
                    if ($parkLink->count() > 0) {
                        $id = $this->extractIdFromHref($parkLink->attr('href'));
                        if ($id !== null) {
                            $parkIds[] = $id;
                        }
                    }
                }
            );

            return [
                'coasters' => array_values(array_unique($coasterIds)),
                'parks'    => array_values(array_unique($parkIds)),
            ];

        } catch (\Exception $e) {
            throw new RcdbParseException(
                sprintf('Failed to parse list page: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    private function extractIdFromHref(?string $href): ?int
    {
        if ($href === null) {
            return null;
        }

        if (preg_match('~/(\d+)\.htm~', $href, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
