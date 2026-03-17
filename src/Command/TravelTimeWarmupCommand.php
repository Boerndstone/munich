<?php

namespace App\Command;

use App\Repository\AreaRepository;
use App\Service\AreasService;
use App\Service\TravelTimeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:travel-time:warmup',
    description: 'Warm the travel-time cache (Munich → areas) to avoid OSRM rate limits on first page load.',
)]
class TravelTimeWarmupCommand extends Command
{
    public function __construct(
        private AreaRepository $areaRepository,
        private TravelTimeService $travelTimeService,
        private AreasService $areasService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('test', 't', InputOption::VALUE_NONE, 'Only test connection to OSRM and exit (no warmup)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('test')) {
            $error = $this->travelTimeService->testConnection();
            if ($error !== null) {
                $io->error('OSRM connection failed: ' . $error);
                $io->writeln('');
                $io->writeln('Common causes on shared hosting:');
                $io->writeln('  - Outbound HTTPS blocked by firewall');
                $io->writeln('  - allow_url_fopen disabled (PHP) or cURL not available');
                $io->writeln('  - SSL/TLS or DNS issues');
                return Command::FAILURE;
            }
            $io->success('Connection to OSRM OK.');
            return Command::SUCCESS;
        }

        $areas = $this->areaRepository->findBy(
            ['online' => 1],
            ['sequence' => 'ASC']
        );

        $count = 0;
        foreach ($areas as $area) {
            $lat = $area->getLat() !== null ? (float) $area->getLat() : null;
            $lng = $area->getLng() !== null ? (float) $area->getLng() : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $minutes = $this->travelTimeService->getDrivingMinutesFromMunich($lng, $lat);
            $count++;

            $io->writeln(sprintf(
                '  %s: %s',
                $area->getName(),
                $minutes !== null ? "~{$minutes} Min." : 'n/a'
            ));

            // OSRM demo server: ~1 request per second
            if ($count < count($areas)) {
                sleep(1);
            }
        }

        $this->areasService->clearCache();
        $io->success(sprintf('Travel time cache warmed for %d area(s). Areas cache cleared so next page load will show travel times.', $count));

        return Command::SUCCESS;
    }
}
