<?php

declare(strict_types=1);

namespace Kasko\Services\Reviews\Google;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * GoogleOAuthClient — mints access tokens for the Business Profile
 * API using a long-lived refresh token.
 *
 * The Business Profile API v4 requires per-user OAuth; service
 * accounts are not accepted. The install flow (performed once
 * out-of-band) grants the Google account offline access and
 * yields a refresh token stored in GOOGLE_OAUTH_REFRESH_TOKEN.
 * At sync time this class exchanges the refresh token for a
 * short-lived access token (~1 hour) and caches it in memory
 * for the duration of the process.
 *
 * This class never persists the access token. Refresh tokens
 * are long-lived but can be revoked — any 400/401 from the
 * token endpoint surfaces as RuntimeException so the sync
 * command aborts BEFORE the delete step.
 */
class GoogleOAuthClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private ?string $cachedAccessToken = null;
    private int $cachedUntil = 0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {
    }

    public function getAccessToken(): string
    {
        if ($this->cachedAccessToken !== null && time() < $this->cachedUntil) {
            return $this->cachedAccessToken;
        }

        if ($this->clientId === '' || $this->clientSecret === '' || $this->refreshToken === '') {
            throw new RuntimeException('Google OAuth credentials are not configured. Set GOOGLE_OAUTH_CLIENT_ID, GOOGLE_OAUTH_CLIENT_SECRET, and GOOGLE_OAUTH_REFRESH_TOKEN.');
        }

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type'    => 'refresh_token',
                ],
                'timeout' => 15,
            ]);
            $status = $response->getStatusCode();
            $payload = $response->toArray(throw: false);
        } catch (\Throwable $e) {
            $this->logger->error('Google OAuth token request failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Google OAuth token request failed: ' . $e->getMessage(), previous: $e);
        }

        if ($status !== 200 || !isset($payload['access_token'])) {
            $this->logger->error('Google OAuth token endpoint returned error', [
                'status' => $status,
                'error'  => $payload['error'] ?? null,
                'desc'   => $payload['error_description'] ?? null,
            ]);
            throw new RuntimeException(sprintf(
                'Google OAuth token exchange failed (HTTP %d): %s',
                $status,
                $payload['error_description'] ?? ($payload['error'] ?? 'unknown error')
            ));
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $this->cachedAccessToken = (string) $payload['access_token'];
        // Buffer 60s so a token nearing expiry isn't used mid-request.
        $this->cachedUntil = time() + max(60, $expiresIn - 60);

        return $this->cachedAccessToken;
    }
}
