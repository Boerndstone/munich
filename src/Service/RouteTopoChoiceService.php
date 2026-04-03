<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Rock;
use App\Entity\Topo;
use App\Repository\TopoRepository;

/**
 * Topo labels (name) for admin route forms; values are topo.number → routes.topo_id.
 */
final class RouteTopoChoiceService
{
    public function __construct(private TopoRepository $topoRepository)
    {
    }

    /**
     * @return array<string, int> label => topo number
     */
    public function choicesForRock(?Rock $rock): array
    {
        if ($rock === null) {
            return [];
        }

        /** @var list<Topo> $topos */
        $topos = $this->topoRepository->findAllForRock($rock);
        /** @var list<Topo> $numberedTopos */
        $numberedTopos = array_values(array_filter(
            $topos,
            static fn (Topo $topo): bool => $topo->getNumber() !== null
        ));

        $nameCounts = [];
        foreach ($numberedTopos as $topo) {
            $name = (string) ($topo->getName() ?? '');
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
        }

        $choices = [];
        foreach ($numberedTopos as $topo) {
            $name = (string) ($topo->getName() ?? '');
            $number = $topo->getNumber();
            $label = $name;
            if (($nameCounts[$name] ?? 0) > 1) {
                $label = $name.' (Nr. '.$number.')';
            }
            $choices[$label] = $number;
        }

        return $choices;
    }
}
