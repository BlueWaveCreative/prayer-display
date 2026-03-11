<?php

/**
 * Generate the PCO OAuth authorization URL for a church.
 * Creates and stores a state parameter to prevent CSRF.
 */
function pcoGetAuthorizeUrl(int $churchId): string {
    $db = getDb();
    $state = bin2hex(random_bytes(32));

    // Clean up old states for this church
    $db->prepare('DELETE FROM oauth_states WHERE church_id = ?')->execute([$churchId]);

    // Store new state
    $stmt = $db->prepare('INSERT INTO oauth_states (state, church_id) VALUES (?, ?)');
    $stmt->execute([$state, $churchId]);

    $params = http_build_query([
        'client_id'     => PCO_CLIENT_ID,
        'redirect_uri'  => PCO_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'check_ins',
        'state'         => $state,
    ]);

    return 'https://api.planningcenteronline.com/oauth/authorize?' . $params;
}

/**
 * Exchange an authorization code for access + refresh tokens.
 * Returns token data array or null on failure.
 */
function pcoExchangeCode(string $code): ?array {
    $response = pcoTokenRequest([
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'client_id'     => PCO_CLIENT_ID,
        'client_secret' => PCO_CLIENT_SECRET,
        'redirect_uri'  => PCO_REDIRECT_URI,
    ]);
    return $response;
}

/**
 * Refresh an expired access token using the refresh token.
 * Returns new token data array or null on failure.
 */
function pcoRefreshToken(string $refreshToken): ?array {
    $response = pcoTokenRequest([
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken,
        'client_id'     => PCO_CLIENT_ID,
        'client_secret' => PCO_CLIENT_SECRET,
    ]);
    return $response;
}

/**
 * Store tokens in the database for a church.
 */
function pcoStoreTokens(int $churchId, array $tokenData): void {
    $db = getDb();
    $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 7200));

    $stmt = $db->prepare('SELECT id FROM church_tokens WHERE church_id = ?');
    $stmt->execute([$churchId]);

    if ($stmt->fetch()) {
        $stmt = $db->prepare(
            'UPDATE church_tokens SET access_token = ?, refresh_token = ?, token_expires_at = ?, last_refreshed_at = NOW() WHERE church_id = ?'
        );
        $stmt->execute([$tokenData['access_token'], $tokenData['refresh_token'], $expiresAt, $churchId]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO church_tokens (church_id, access_token, refresh_token, token_expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$churchId, $tokenData['access_token'], $tokenData['refresh_token'], $expiresAt]);
    }
}

/**
 * Get a valid access token for a church, refreshing if needed.
 * Returns access token string or null if refresh fails.
 */
function pcoGetAccessToken(int $churchId): ?string {
    $db = getDb();
    $stmt = $db->prepare('SELECT access_token, refresh_token, token_expires_at FROM church_tokens WHERE church_id = ?');
    $stmt->execute([$churchId]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    // Refresh if expiring within 5 minutes
    $expiresAt = strtotime($row['token_expires_at']);
    if ($expiresAt - time() < 300) {
        $newTokens = pcoRefreshToken($row['refresh_token']);
        if (!$newTokens) {
            // Log refresh failure
            $db->prepare('UPDATE church_tokens SET last_api_error = ? WHERE church_id = ?')
               ->execute(['Token refresh failed at ' . date('Y-m-d H:i:s'), $churchId]);
            return null;
        }
        pcoStoreTokens($churchId, $newTokens);
        return $newTokens['access_token'];
    }

    return $row['access_token'];
}

/**
 * Internal: Make a token request to PCO.
 */
function pcoTokenRequest(array $params): ?array {
    $ch = curl_init('https://api.planningcenteronline.com/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$body) {
        error_log("PCO token request failed: HTTP $httpCode — $body");
        return null;
    }

    $data = json_decode($body, true);
    if (empty($data['access_token'])) {
        error_log("PCO token response missing access_token: $body");
        return null;
    }

    return $data;
}
