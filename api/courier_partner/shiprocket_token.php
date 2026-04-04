<?php
/**
 * Server-side Shiprocket auth: login (generate JWT) and logout.
 * POST JSON: { "action": "login"|"logout", "email"?, "password"?, "token"? }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/middleware.php';

global $role_id;
if ((int) $role_id !== 1
    && !get_permission('courier_partner', 'is_add')
    && !get_permission('courier_partner', 'is_edit')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode((string) $raw, true);
if (!is_array($body)) {
    $body = [];
}

/**
 * Normalize token string (strip Bearer prefix, trim).
 */
function shiprocketNormalizeTokenString(string $raw): string
{
    $t = trim($raw);
    if (stripos($t, 'bearer ') === 0) {
        $t = trim(substr($t, 7));
    }
    return $t;
}

/**
 * True if value looks like a JWT (three dot-separated segments).
 */
function shiprocketLooksLikeJwt(string $s): bool
{
    $s = shiprocketNormalizeTokenString($s);
    return $s !== '' && (bool) preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_.-]+$/', $s);
}

/**
 * Extract JWT from Shiprocket login JSON (keys vary by API version).
 */
function shiprocketExtractTokenFromDecoded($decoded): string
{
    if (!is_array($decoded)) {
        return '';
    }
    $keyCandidates = [
        'token', 'access_token', 'jwt', 'auth_token', 'id_token',
        'accessToken', 'authToken', 'bearer_token', 'api_token',
    ];
    foreach ($keyCandidates as $k) {
        if (!empty($decoded[$k]) && is_string($decoded[$k])) {
            $t = shiprocketNormalizeTokenString($decoded[$k]);
            if (shiprocketLooksLikeJwt($t)) {
                return $t;
            }
            if ($t !== '') {
                return $t;
            }
        }
    }
    if (isset($decoded['data']) && is_array($decoded['data'])) {
        foreach ($keyCandidates as $k) {
            if (!empty($decoded['data'][$k]) && is_string($decoded['data'][$k])) {
                $t = shiprocketNormalizeTokenString($decoded['data'][$k]);
                if ($t !== '') {
                    return $t;
                }
            }
        }
    }
    $stack = [$decoded];
    while ($stack) {
        $cur = array_pop($stack);
        if (!is_array($cur)) {
            continue;
        }
        foreach ($cur as $v) {
            if (is_string($v) && shiprocketLooksLikeJwt($v)) {
                return shiprocketNormalizeTokenString($v);
            }
            if (is_array($v)) {
                $stack[] = $v;
            }
        }
    }
    return '';
}

/**
 * Find JWT in raw HTTP body (non-JSON or embedded).
 */
function shiprocketExtractTokenFromRaw(string $resp): string
{
    $resp = trim($resp);
    if ($resp === '') {
        return '';
    }
    if (preg_match('/[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_.-]{10,}/', $resp, $m)) {
        return $m[0];
    }
    return '';
}

$action = strtolower(trim((string) ($body['action'] ?? '')));

if ($action === 'login') {
    $email = trim((string) ($body['email'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    if ($email === '' || $password === '') {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
        exit;
    }

    $ch = curl_init('https://apiv2.shiprocket.in/v1/external/auth/login');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: WHMS-CourierPartner/1.0',
        ],
        CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password]),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        echo json_encode(['status' => 'error', 'message' => 'Connection error: ' . $curlErr]);
        exit;
    }

    $resp = (string) $resp;
    $decoded = json_decode($resp, true);
    $token = '';
    if (is_array($decoded)) {
        $token = shiprocketExtractTokenFromDecoded($decoded);
    }
    if ($token === '') {
        $token = shiprocketExtractTokenFromRaw($resp);
    }
    $token = shiprocketNormalizeTokenString($token);

    $loginOk = ($httpCode >= 200 && $httpCode < 300);
    if ($loginOk && $token !== '') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Shiprocket token generated (valid ~10 days). Save the partner to store it.',
            'token' => $token,
        ]);
        exit;
    }

    $detail = '';
    if (is_array($decoded)) {
        $detail = (string) ($decoded['message'] ?? $decoded['error'] ?? $decoded['errors'] ?? $decoded['msg'] ?? '');
        if ($detail === '') {
            $detail = json_encode($decoded);
        }
    } else {
        $detail = substr($resp, 0, 800);
    }

    $hint = '';
    if ($loginOk && $token === '') {
        $hint = 'Shiprocket returned success but no JWT was found in the body. Check API response shape or network/HTML intercept.';
    }

    echo json_encode([
        'status' => 'error',
        'message' => $loginOk ? ($hint ?: 'Token not found in Shiprocket response') : 'Shiprocket login failed',
        'http_code' => $httpCode,
        'detail' => $detail,
        'hint' => $hint,
    ]);
    exit;
}

if ($action === 'logout') {
    $token = trim((string) ($body['token'] ?? ''));
    if ($token === '') {
        echo json_encode(['status' => 'error', 'message' => 'Token is required for logout']);
        exit;
    }
    if (stripos($token, 'bearer ')             === 0) {
        $token = trim(substr($token, 7));
    }

    $ch = curl_init('https://apiv2.shiprocket.in/v1/external/auth/logout');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        echo json_encode(['status' => 'error', 'message' => 'Connection error: ' . $curlErr]);
        exit;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Shiprocket token logged out. Clear the Token field and save if needed.',
            'http_code' => $httpCode,
        ]);
        exit;
    }

    $decoded = json_decode((string) $resp, true);
    $detail = is_array($decoded) ? json_encode($decoded) : substr((string) $resp, 0, 500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Shiprocket logout failed',
        'http_code' => $httpCode,
        'detail' => $detail,
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action. Use login or logout.']);

