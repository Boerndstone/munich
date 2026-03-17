<?php

namespace App\Command;

use App\Repository\AreaRepository;
use App\Service\TravelTimeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:travel-time:export',
    description: 'Fetch driving times from Munich to each area (OSRM) and write to var/travel_times.json. Run locally or in CI; then run app:travel-time:import on the server.',
)]
class TravelTimeExportCommand extends Command
{
    private const FILENAME = 'var/travel_times.json';

    public function __construct(
        private AreaRepository $areaRepository,
        private TravelTimeService $travelTimeService,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $error = $this->travelTimeService->testConnection();
        if ($error !== null) {
            $io->error('OSRM connection failed: ' . $error);
            $io->writeln('');
            $io->writeln('Run this command where HTTPS to OSRM works (e.g. locally or in Docker).');
            $io->writeln('Then upload ' . self::FILENAME . ' to the server and run: php bin/console app:travel-time:import');

            return Command::FAILURE;
        }

        $areas = $this->areaRepository->findBy(
            ['online' => 1],
            ['sequence' => 'ASC']
        );

        $data = [];
        $count = 0;
        foreach ($areas as $area) {
            $lat = $area->getLat() !== null ? (float) $area->getLat() : null;
            $lng = $area->getLng() !== null ? (float) $area->getLng() : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $minutes = $this->travelTimeService->getDrivingMinutesFromMunich($lng, $lat);
            $count++;
            $io->writeln(sprintf('  %s: %s', $area->getName(), $minutes !== null ? "~{$minutes} Min." : 'n/a'));

            if ($minutes !== null) {
                $data[(string) $area->getId()] = $minutes;
            }

            if ($count < count($areas)) {
                sleep(1);
            }
        }

        $path = $this->projectDir . '/' . self::FILENAME;
        (new Filesystem())->mkdir(\dirname($path));
        file_put_contents($path, json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

        $io->success(sprintf('Wrote %d travel time(s) to %s. Upload this file to the server and run: php bin/console app:travel-time:import', \count($data), self::FILENAME));

        return Command::SUCCESS;
    }
}
