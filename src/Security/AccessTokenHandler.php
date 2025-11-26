<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\AccessTokenRepository;
use Override;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository
    ) {
    }

    #[Override]
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $accessTokenEntity = $this->accessTokenRepository->findOneByValue($accessToken);

        if (!$accessTokenEntity) {
            throw new BadCredentialsException('Invalid access token!');
        }

        if (!$accessTokenEntity->isValid()) {
            throw new BadCredentialsException('Access token has expired!');
        }

        $user = $accessTokenEntity->getUser();
        if (!$user) {
            throw new BadCredentialsException('Access token has no associated user!');
        }

        $userIdentifier = $user->getUserIdentifier();
        if (empty($userIdentifier)) {
            throw new BadCredentialsException('User identifier is empty!');
        }

        return new UserBadge($userIdentifier);
    }
}
