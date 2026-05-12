<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Topo;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persists path collection from the topo path editor (admin + frontend).
 */
final class TopoPathEditorSaveService
{
    public const CSRF_INTENT = 'topo_save_topo_path_helper';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function savePhpLiteral(Topo $topo, string $phpLiteral): void
    {
        $topo->setPathCollection(trim($phpLiteral));
        $this->entityManager->flush();
    }
}
