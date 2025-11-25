<?php
include_once 'db_connection.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_POST['username']) || empty($_POST['username'])) {
        throw new Exception('Missing username');
    }

    // PDO does not require manual escaping like real_escape_string
    $username = $_POST['username'];

    // find user and phone
    // PDO: Named parameters or ? placeholders work. Here using ? for simplicity to match structure.
    $sql = "SELECT contact_number FROM `users` WHERE (username = :uname OR id = :uid)";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        // In PDO with ERRMODE_EXCEPTION, prepare() throws exception on failure, 
        // but we check for safety or specific logic if needed.
        throw new Exception('DB prepare failed');
    }

    $stmt->execute([':uname' => $username, ':uid' => $username]);
    
    // PDO: rowCount() works for SELECT in MySQL, but fetch() is safer for portability
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('User not found');
    }
    
    $to_number = $row['contact_number'];

    // Ensure session container exists
    if (!isset($_SESSION['password_reset_otp']) || !is_array($_SESSION['password_reset_otp'])) {
        $_SESSION['password_reset_otp'] = [];
    }

    // basic resend throttle: 30s cooldown and max 3 resends per session
    // Ensure per-user session entry exists and reference it
    if (!isset($_SESSION['password_reset_otp'][$username]) || !is_array($_SESSION['password_reset_otp'][$username])) {
        $_SESSION['password_reset_otp'][$username] = [
            'otp' => null,
            'expires' => 0,
            'phone' => $to_number,
            'attempts' => 0,
            'last_sent' => 0,
            'resend_count' => 0
        ];
    }
    $sessionEntry = &$_SESSION['password_reset_otp'][$username];

    $now = time();
    $cooldown = 60; // seconds (1 minute)
    // check cooldown
    if (!empty($sessionEntry['last_sent']) && ($now - $sessionEntry['last_sent']) < $cooldown) {
        $remain = $cooldown - ($now - $sessionEntry['last_sent']);
        http_response_code(429);
        $blocked = [
            'success' => false,
            'message' => 'Please wait ' . $remain . ' seconds before resending OTP',
            'cooldown_remaining' => $remain,
            'resends_left' => max(0, 3 - (int)$sessionEntry['resend_count'])
        ];
        if (!empty($_POST['debug'])) {
            $blocked['debug'] = [
                'now' => $now,
                'last_sent' => (int)$sessionEntry['last_sent'],
                'rem' => $remain,
                'session' => $sessionEntry
            ];
        }
        echo json_encode($blocked);
        exit;
    }

    // check resend limit
    if (!empty($sessionEntry['resend_count']) && $sessionEntry['resend_count'] >= 3) {
        http_response_code(429);
        $limitResp = [
            'success' => false,
            'message' => 'Resend limit reached. Please try again later.',
            'resends_left' => 0
        ];
        if (!empty($_POST['debug'])) {
            $limitResp['debug'] = [ 'session' => $sessionEntry ];
        }
        echo json_encode($limitResp);
        exit;
    }

    // generate new OTP and prepare session fields
    $otp = sprintf('%06d', random_int(0, 999999));
    $expires = $now + 300; // 5 minutes
    $sessionEntry['otp'] = $otp;
    $sessionEntry['expires'] = $expires;
    $sessionEntry['phone'] = $to_number;
    $sessionEntry['attempts'] = isset($sessionEntry['attempts']) ? $sessionEntry['attempts'] : 0;

    // Log basic event
    error_log("[resendOTP] Resending OTP for user={$username} phone={$to_number} resend_count={$sessionEntry['resend_count']}");

    // SMS provider config
    $iprog_url = 'https://sms.iprogtech.com/api/v1/otp/send_otp';
    $iprog_user = 'WILLIAN ACORDA';
    $iprog_pass = 'ecb82c72eb5547a176cd13c615fa25e4b849121a';
    $iprog_sender = 'BrgySystem';

    $sms_debug = null;
    
    if (!empty($iprog_pass) && !empty($iprog_url)) {
        $message = "Your password reset OTP is: $otp. It expires in 5 minutes.";
        $to_norm = preg_replace('/[^0-9+]/', '', $to_number);
        if (strpos($to_norm, '+') === 0) $to_norm = substr($to_norm, 1);
        if (strlen($to_norm) == 11 && $to_norm[0] === '0') $to_norm = '63' . substr($to_norm, 1);
        if (strlen($to_norm) == 10 && substr($to_norm, 0, 2) !== '63') $to_norm = '63' . $to_norm;

        $postFields = [
            'api_token' => $iprog_pass,
            'message' => $message,
            'phone_number' => $to_norm
        ];
        if (!empty($iprog_sender)) $postFields['sender'] = $iprog_sender;
        if (!empty($iprog_user)) $postFields['user'] = $iprog_user;

        $params = http_build_query($postFields);
        $ch = curl_init($iprog_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $resp = curl_exec($ch);
        $curl_err = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curl_err && stripos($curl_err, 'SSL certificate') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $resp = curl_exec($ch);
            $curl_err = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);

        // Try to parse JSON response from provider
        $decoded = null;
        if (!empty($resp)) {
            $decoded = json_decode($resp, true);
        }

        $provider_error = false;
        if ($curl_err) {
            $provider_error = true;
        } elseif (empty($resp) || !($httpcode >= 200 && $httpcode < 300)) {
            $provider_error = true;
        } elseif (is_array($decoded) && isset($decoded['status'])) {
            if ((int)$decoded['status'] !== 200) {
                $provider_error = true;
            }
        }

        if ($provider_error) {
            error_log("[resendOTP] SMS provider error for user={$username} phone={$to_norm} http_code={$httpcode} resp=" . ($resp ?: 'empty'));
            
            http_response_code(502);
            $err = [
                'success' => false,
                'message' => 'Failed to send OTP via SMS provider',
                'provider' => [
                    'http_code' => $httpcode,
                    'curl_error' => $curl_err,
                    'raw_response' => $resp,
                    'parsed_response' => $decoded
                ]
            ];
            $is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
            if (!empty($_POST['debug']) || $is_local) {
                $err['otp'] = isset($sessionEntry['otp']) ? $sessionEntry['otp'] : $otp;
                $err['show_otp'] = true;
                $err['debug'] = [
                    'session_before' => $sessionEntry,
                    'now' => $now
                ];
            } else {
                if (!empty($_POST['debug'])) {
                    $err['debug'] = [ 'session_before' => $sessionEntry, 'now' => $now ];
                }
            }
            echo json_encode($err);
            exit;
        } else {
            error_log("[resendOTP] SMS sent for user={$username} phone={$to_norm} http_code={$httpcode}");
            $sessionEntry['last_sent'] = $now;
            $sessionEntry['resend_count'] = isset($sessionEntry['resend_count']) ? $sessionEntry['resend_count'] + 1 : 1;
        }
    } else {
        error_log('[resendOTP] SMS provider not configured (iprog_pass or iprog_url missing)');
        $resp = null;
        $decoded = null;
        $curl_err = 'SMS provider not configured';
        $httpcode = 0;
    }

    $out = [
        'success' => true,
        'message' => 'OTP resent',
        'resend_count' => (int)$sessionEntry['resend_count'],
        'resends_left' => max(0, 3 - (int)$sessionEntry['resend_count']),
        'cooldown' => $cooldown,
        'last_sent' => (int)$sessionEntry['last_sent'],
        'cooldown_remaining' => max(0, (($sessionEntry['last_sent'] + $cooldown) - time())),
        'provider_configured' => (!empty($iprog_pass) && !empty($iprog_url))
    ];
    
    if (!empty($_POST['debug'])) {
        $out['debug'] = [
            'otp' => isset($sessionEntry['otp']) ? $sessionEntry['otp'] : null,
            'phone' => isset($sessionEntry['phone']) ? $sessionEntry['phone'] : $to_number,
            'last_sent' => isset($sessionEntry['last_sent']) ? (int)$sessionEntry['last_sent'] : $now,
            'resend_count' => isset($sessionEntry['resend_count']) ? (int)$sessionEntry['resend_count'] : 0,
            'expires' => isset($sessionEntry['expires']) ? (int)$sessionEntry['expires'] : $expires,
            'provider' => [
                'http_code' => isset($httpcode) ? $httpcode : null,
                'curl_error' => isset($curl_err) ? $curl_err : null,
                'raw_response' => isset($resp) ? $resp : null,
                'parsed_response' => isset($decoded) ? $decoded : null,
            ],
            'now' => $now
        ];
    }

    echo json_encode($out);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    // Catch generic exceptions (including those from our PDO logic)
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
} catch (PDOException $e) {
    // Catch specific DB exceptions
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>