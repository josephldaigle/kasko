<?php

namespace Kasko\Security;

use Kasko\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * NOTE: This authenticator was Guard-based before the Symfony 5.4 upgrade
 * and was ported to the new authenticator system. There is no `login`
 * route registered in this application, so supports() never returns true
 * in practice and this authenticator is effectively dormant.
 *
 * The original checkCredentials() threw an "unimplemented" exception; that
 * behavior is preserved here via CustomCredentials so nothing that was
 * previously broken starts silently succeeding.
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        return 'login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $uuid = (string) $request->request->get('uuid', '');
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        $request->getSession()->set(Security::LAST_USERNAME, $uuid);

        return new Passport(
            new UserBadge($uuid, function (string $identifier): Tenant {
                $user = $this->entityManager->getRepository(Tenant::class)
                    ->findOneBy(['uuid' => $identifier]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Your username/password did not match.');
                }

                return $user;
            }),
            new CustomCredentials(
                function () {
                    // Preserves the original Guard behavior: credential
                    // checking is not implemented. Since no login route
                    // is registered, this path is unreachable.
                    throw new \RuntimeException('TODO: implement credential check for '.self::class);
                },
                $password,
            ),
            [new CsrfTokenBadge('authenticate', $csrfToken)],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse('/');
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('login');
    }
}
