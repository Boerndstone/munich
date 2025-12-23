<?php

namespace App\Command;

use App\Repository\AreaRepository;
use App\Repository\RockRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-sitemap',
    description: 'Generates a sitemap.xml file for SEO purposes',
)]
class GenerateSitemapCommand extends Command
{
    private const BASE_URL = 'https://munichclimbs.de';

    public function __construct(
        private readonly AreaRepository $areaRepository,
        private readonly RockRepository $rockRepository,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating Sitemap');

        $urls = $this->collectUrls();
        $xml = $this->generateXml($urls);

        $filePath = $this->projectDir . '/public/sitemap.xml';
        file_put_contents($filePath, $xml);

        $io->success(sprintf('Sitemap generated with %d URLs: %s', count($urls), $filePath));

        return Command::SUCCESS;
    }

    private function collectUrls(): array
    {
        $urls = [];
        $now = new \DateTimeImmutable();

        // Homepage (German)
        $urls[] = [
            'loc' => self::BASE_URL . '/',
            'lastmod' => $now->format('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        // Homepage (English)
        $urls[] = [
            'loc' => self::BASE_URL . '/en',
            'lastmod' => $now->format('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ];

        // Areas (German)
        $areas = $this->areaRepository->findBy(['online' => 1], ['sequence' => 'ASC']);
        foreach ($areas as $area) {
            $urls[] = [
                'loc' => self::BASE_URL . '/' . $area->getSlug(),
                'lastmod' => $now->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // Rocks (German and English)
        $rocks = $this->rockRepository->findBy(['online' => true], ['nr' => 'ASC']);
        foreach ($rocks as $rock) {
            $area = $rock->getArea();
            if (!$area || $area->getOnline() !== 1) {
                continue;
            }

            // German version
            $urls[] = [
                'loc' => self::BASE_URL . '/' . $area->getSlug() . '/' . $rock->getSlug(),
                'lastmod' => $now->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];

            // English version
            $urls[] = [
                'loc' => self::BASE_URL . '/en/' . $area->getSlug() . '/' . $rock->getSlug(),
                'lastmod' => $now->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    private function generateXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}

