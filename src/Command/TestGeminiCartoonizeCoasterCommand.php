<?php

namespace App\Command;

use App\Repository\CoasterRepository;
use App\Service\CoasterImageTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:gemini-cartoonize-coaster',
    description: 'Downloads a coaster image, sends it to Gemini, uploads result to Cloudinary'
)]
final class TestGeminiCartoonizeCoasterCommand extends Command
{
    public function __construct(
        private readonly CoasterRepository $coasterRepository,
        private readonly CoasterImageTransformer $coasterImageTransformer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'coasterId',
            InputArgument::REQUIRED,
            'ID of the coaster entity'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $coasterId = (int) $input->getArgument('coasterId');

        $coaster = $this->coasterRepository->find($coasterId);
        if (!$coaster) {
            $io->error('Coaster not found.');
            return Command::FAILURE;
        }

        if (!$coaster->getRcdbImageUrl()) {
            $io->error('Coaster has no rcdbImageUrl.');
            return Command::FAILURE;
        }

        $io->text('Transforming image…');
        $t2 = microtime(true);
        $cloudinaryUrl = $this->coasterImageTransformer->transform($coaster);
        $t3 = microtime(true);
        $io->text(sprintf("Transformation finished in %.2f seconds", $t3 - $t2));

        $io->success(sprintf('Cartoon image generated and uploaded: %s', $cloudinaryUrl));

        return Command::SUCCESS;
    }
}
