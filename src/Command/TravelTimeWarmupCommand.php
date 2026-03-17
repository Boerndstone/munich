<?php

namespace App\Command;

use App\Repository\AreaRepository;
use App\Service\AreasService;
use App\Service\TravelTimeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
