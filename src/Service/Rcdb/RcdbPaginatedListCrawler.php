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

class RcdbPaginatedListCrawler
{
    private const BASE_URL = 'https://rcdb.com';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Crawl all RCDB result pages for a given list URL (e.g., r.htm?...).
     *
     * @return array{
     *     coasters: int[],
     *     parks: int[]
     * }
     *
     * @throws RcdbFetchException
     * @throws RcdbParseException
     */
    public function crawlAll(string $relativeUrl): array
    {
        $coasters = [];
        $parks = [];

        // First page will tell us how many pages there are
        $firstPageHtml = $this->fetchHtml($relativeUrl);
        $maxPage = $this->extractMaxPage($firstPageHtml);
        $maxPage = 1;

        $this->logger->info('Detected pagination', ['maxPage' => $maxPage]);

        for ($page = 1; $page <= $maxPage; $page++) {
            $pageUrl = $relativeUrl . '&page=' . $page;
            $html = $this->fetchHtml($pageUrl);
            $result = $this->parseListPage($html);

            $coasters = array_merge($coasters, $result['coasters']);
            $parks    = array_merge($parks, $result['parks']);

            $this->logger->debug('Parsed page', ['page' => $page]);
        }

        return [
            'coasters' => array_values(array_unique($coasters)),
            'parks'    => array_values(array_unique($parks)),
        ];
    }

    private function fetchHtml(string $relativeUrl): string
    {
        $url = str_starts_with($relativeUrl, 'http')
            ? $relativeUrl
            : self::BASE_URL . $relativeUrl;

        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->getContent();
        } catch (ExceptionInterface $e) {
            throw new RcdbFetchException(sprintf('Failed to fetch RCDB page %s: %s', $url, $e->getMessage()), 0, $e);
        }
    }

    private function extractMaxPage(string $html): int
    {
        $crawler = new DomCrawler($html);

        $pageLinks = $crawler->filter('div.stdtbl table tbody tr td')
            ->reduce(fn($node) => preg_match('/\[\d+\]/', $node->text()));

        $max = 1;

        if ($pageLinks->count() > 0) {
            $pageLinks->each(function (DomCrawler $node) use (&$max) {
                $text = trim($node->text());
                if (is_numeric($text) && (int)$text > $max) {
                    $max = (int)$text;
                }
            });
        }

        return $max;
    }

    private function parseListPage(string $html): array
    {
        $crawler = new DomCrawler($html);
        $coasterIds = [];
        $parkIds = [];

        $crawler->filter('div.stdtbl table tbody tr')->each(function (DomCrawler $row) use (&$coasterIds, &$parkIds) {
            $cells = $row->filter('td');
            if ($cells->count() < 3) {
                return;
            }

            // 2nd column = coaster
            $coasterLink = $cells->eq(1)->filter('a[href$=".htm"]');
            if ($coasterLink->count() > 0) {
                $id = $this->extractIdFromHref($coasterLink->attr('href'));
                if ($id !== null) {
                    $coasterIds[] = $id;
                }
            }

            // 3rd column = park
            $parkLink = $cells->eq(2)->filter('a[href$=".htm"]');
            if ($parkLink->count() > 0) {
                $id = $this->extractIdFromHref($parkLink->attr('href'));
                if ($id !== null) {
                    $parkIds[] = $id;
                }
            }
        });

        return [
            'coasters' => $coasterIds,
            'parks'    => $parkIds,
        ];
    }

    private function extractIdFromHref(?string $href): ?int
    {
        if (!$href) {
            return null;
        }
        if (preg_match('~/(\d+)\.htm~', $href, $m)) {
            return (int)$m[1];
        }
        return null;
    }
}
