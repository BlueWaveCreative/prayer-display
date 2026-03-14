<?php

/**
 * Fetch today's prayer check-in names for a church.
 * Returns array of name strings, or null on failure.
 */
function pcoFetchCheckIns(int $churchId, string $eventId, string $timezone): ?array {
    $accessToken = pcoGetAccessToken($churchId);
    if (!$accessToken) {
        return null;
    }

    // Calculate start of today in the church's timezone
    $tz = new DateTimeZone($timezone);
    $now = new DateTime('now', $tz);
    $startOfToday = new DateTime($now->format('Y-m-d') . ' 00:00:00', $tz);
    $since = $startOfToday->format('c'); // ISO 8601

    $baseUrl = 'https://api.planningcenteronline.com/check-ins/v2';
    $url = $baseUrl . '/events/' . urlencode($eventId)
         . '/check_ins?per_page=100&include=person&where[created_at][gte]='
         . urlencode($since);

    $names = [];
    $db = getDb();

    while ($url) {
        $response = pcoApiGet($url, $accessToken);

        if ($response === null) {
            $db->prepare('UPDATE church_tokens SET last_api_error = ? WHERE church_id = ?')
               ->execute(['API request failed at ' . date('Y-m-d H:i:s'), $churchId]);
            return null;
        }

        // Check for 401 (expired token that wasn't caught by refresh)
        if (isset($response['_http_code']) && $response['_http_code'] === 401) {
            $db->prepare('UPDATE church_tokens SET last_api_error = ? WHERE church_id = ?')
               ->execute(['401 Unauthorized at ' . date('Y-m-d H:i:s'), $churchId]);
            return null;
        }

        // Build person map from included data
        $personMap = [];
        foreach ($response['included'] ?? [] as $item) {
            if ($item['type'] === 'Person') {
                $personMap[$item['id']] = $item['attributes']['name'] ?? 'Unknown';
            }
        }

        // Extract names
        foreach ($response['data'] ?? [] as $checkIn) {
            $createdAt = $checkIn['attributes']['created_at'] ?? null;
            if ($createdAt) {
                $checkInTime = new DateTime($createdAt);
                $checkInTime->setTimezone($tz);
                // Double-check date filter (API may not support where clause fully)
                if ($checkInTime >= $startOfToday) {
                    $personId = $checkIn['relationships']['person']['data']['id'] ?? null;
                    if ($personId && isset($personMap[$personId])) {
                        $names[$personId] = $personMap[$personId]; // Deduplicate by person ID
                    }
                }
            }
        }

        // Pagination
        $url = $response['links']['next'] ?? null;
    }

    // Mark success
    $db->prepare('UPDATE church_tokens SET last_api_success_at = NOW(), last_api_error = NULL WHERE church_id = ?')
       ->execute([$churchId]);

    // Return names sorted by last name so families group together
    $nameList = array_values($names);
    usort($nameList, function ($a, $b) {
        $aLast = substr(strrchr($a, ' '), 1) ?: $a;
        $bLast = substr(strrchr($b, ' '), 1) ?: $b;
        $cmp = strcasecmp($aLast, $bLast);
        return $cmp !== 0 ? $cmp : strcasecmp($a, $b);
    });
    return $nameList;
}

/**
 * Make an authenticated GET request to the PCO API.
 * Returns decoded JSON array or null on failure.
 */
function pcoApiGet(string $url, string $accessToken): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$body) {
        error_log("PCO API request failed: no response from $url");
        return null;
    }

    $data = json_decode($body, true);
    if ($data === null) {
        error_log("PCO API response not valid JSON from $url");
        return null;
    }

    $data['_http_code'] = $httpCode;
    return $data;
}
