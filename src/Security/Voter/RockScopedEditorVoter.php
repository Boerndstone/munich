<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Service\RockAccessService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Use is_granted(self::ATTRIBUTE) anywhere the UI or access rules should match
 * {@see RockAccessService::isRockScoped()} (rock editor, not super admin).
 */
final class RockScopedEditorVoter extends Voter
{
    public const ATTRIBUTE = 'ROCK_SCOPED_EDITOR';

    public function __construct(
        private readonly RockAccessService $rockAccessService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ATTRIBUTE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $this->rockAccessService->isRockScoped($user instanceof UserInterface ? $user : null);
    }
}
