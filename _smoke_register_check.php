<?php

/**
 * TEMPORARY smoke test: sends a real HTTP `register` GraphQL mutation to a
 * locally running server (Lighthouse -> Register -> Sanctum createToken ->
 * personal_access_tokens) and removes the created rows again afterwards.
 * Delete this file once the check has been run.
 *
 * Usage: php _smoke_register_check.php [graphql-url]
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$url = $argv[1] ?? 'http://127.0.0.1:8099/graphql';
$email = 'smoke.'.time().'@example.test';

$payload = json_encode([
    'query' => 'mutation RegisterUser($name: String!, $email: String!, $password: String!) {
        register(name: $name, email: $email, password: $password, device_name: "smoke-device") {
            token
            user { id name email }
        }
    }',
    'variables' => [
        'name' => 'Smoke Tester',
        'email' => $email,
        'password' => 'secret12345',
    ],
]);

$context = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
    'content' => $payload,
    'ignore_errors' => true,
    'timeout' => 30,
]]);

$raw = file_get_contents($url, false, $context);
$status = $http_response_header[0] ?? 'no response';

echo 'URL            : '.$url.PHP_EOL;
echo 'HTTP status    : '.$status.PHP_EOL;
echo 'Body           : '.$raw.PHP_EOL;

$decoded = json_decode((string) $raw, true);

echo 'GraphQL errors : '.(empty($decoded['errors']) ? 'none' : json_encode($decoded['errors'])).PHP_EOL;
echo 'Token returned : '.(empty($decoded['data']['register']['token']) ? 'NO' : 'YES').PHP_EOL;
echo 'Token rows     : '.DB::table('personal_access_tokens')->count().PHP_EOL;

// ── cleanup ────────────────────────────────────────────────────────────────
$user = User::where('email', $email)->first();

if ($user !== null) {
    $user->tokens()->delete();
    $user->forceDelete();
    echo 'Cleanup        : removed smoke user '.$email.PHP_EOL;
} else {
    echo 'Cleanup        : no smoke user created'.PHP_EOL;
}

echo 'Token rows after cleanup: '.DB::table('personal_access_tokens')->count().PHP_EOL;
