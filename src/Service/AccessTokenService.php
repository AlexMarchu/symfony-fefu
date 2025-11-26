<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use DateTimeImmutable;

class AccessTokenService
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository
    ) {
    }

    public function create(
        User $user,
        DateTimeImmutable $expiresAt = null
    ): AccessToken {
        if (!$expiresAt) {
            $expiresAt = new DateTimeImmutable('+7 days');
        }

        $this->removeByUser($user);

        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt($expiresAt);

        $this->accessTokenRepository->save($accessToken);

        return $accessToken;
    }

    public function remove(?AccessToken $accessToken): void
    {
        if ($accessToken) {
            $this->accessTokenRepository->remove($accessToken);
        }
    }

    public function removeByUser(User $user): void
    {
        $accessToken = $this->accessTokenRepository->findOneByUser($user);
        if ($accessToken) {
            $this->remove($accessToken);
        }
    }
}
