<?php

declare(strict_types=1);

namespace App\Service\Rcdb;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ErrorSummaryService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/crawl_errors.json')]
        private string $errorLogFile
    ) {
    }

    public function logError(int $rcdbId, string $message): void
    {
        $errors = $this->getErrors();
        $errors[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'rcdbId' => $rcdbId,
            'message' => $message,
        ];

        file_put_contents($this->errorLogFile, json_encode($errors, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        if (!file_exists($this->errorLogFile)) {
            return [];
        }

        $content = file_get_contents($this->errorLogFile);
        if (!$content) {
            return [];
        }

        return json_decode($content, true) ?: [];
    }

    public function clear(): void
    {
        if (file_exists($this->errorLogFile)) {
            unlink($this->errorLogFile);
        }
    }
}
