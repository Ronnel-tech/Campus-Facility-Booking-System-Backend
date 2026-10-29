<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once SYSTEM_DIR . 'kernel/Model.php';

if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false && empty($_POST)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) $_POST = $json;
}

if (!function_exists('bcrypt')) {
    function bcrypt($value)
    {
        return password_hash((string) $value, PASSWORD_DEFAULT);
    }
}

spl_autoload_register(function ($class) {
    if (strpos($class, '\\') !== false) return;
    $model = APP_DIR . 'models' . DIRECTORY_SEPARATOR . $class . '.php';
    if (is_file($model)) require_once $model;
});

if (!function_exists('request')) {
    function request()
    {
        return load_class('Request', 'kernel');
    }
}

if (!function_exists('response')) {
    function response()
    {
        return new class {
            public function json($data, $status = 200)
            {
                $response = load_class('Response', 'kernel');
                $response->set_status_code($status);
                $response->set_json_content($data);
                $response->send();
                exit;
            }
        };
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

if (!function_exists('now')) {
    function now()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('auth')) {
    function auth()
    {
        static $guard;
        if ($guard !== null) return $guard;

        $guard = new class {
            public function user()
            {
                $headers = function_exists('getallheaders') ? getallheaders() : [];
                $authorization = $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
                if (!preg_match('/^Bearer\s+([^.]+\.[^.]+)$/i', trim($authorization), $matches)) return null;

                $parts = explode('.', $matches[1], 2);
                $payload = json_decode(base64_decode($parts[0]), true);
                if (!is_array($payload) || empty($payload['sub'])) return null;

                return User::find($payload['sub']);
            }
        };

        return $guard;
    }
}
