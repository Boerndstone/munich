<?php

namespace App\Command;

use App\Repository\AreaRepository;
use App\Service\AreasService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:travel-time:import',
    description: 'Import travel times from var/travel_times.json into the database. No outbound HTTPS needed.',
)]
class TravelTimeImportCommand extends Command
{
    private const FILENAME = 'var/travel_times.json';

    public function __construct(
        private AreaRepository $areaRepository,
        private EntityManagerInterface $entityManager,
        private AreasService $areasService,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = $this->projectDir . '/' . self::FILENAME;
        if (!is_readable($path)) {
            $io->error('File not found or not readable: ' . self::FILENAME);
            $io->writeln('Generate it locally with: php bin/console app:travel-time:export');

            return Command::FAILURE;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!\is_array($data)) {
            $io->error('Invalid JSON in ' . self::FILENAME);

            return Command::FAILURE;
        }

        $updated = 0;
        foreach ($data as $areaId => $minutes) {
            $area = $this->areaRepository->find((int) $areaId);
            if ($area === null) {
                continue;
            }
            $area->setTravelTimeMinutes((int) $minutes);
            $this->entityManager->persist($area);
            $updated++;
        }

        $this->entityManager->flush();
        $this->areasService->clearCache();

        $io->success(sprintf('Imported %d travel time(s) into the database. Areas cache cleared.', $updated));

        return Command::SUCCESS;
    }
}
