<?php
// config/app.php - Application configuration
return [
    // Application Settings
    'name' => 'Agan Cafe',
    'version' => '2.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production', // development, testing, production
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Asia/Kathmandu',
    'locale' => 'en_US',
    
    // Security Settings
    'session' => [
        'lifetime' => 7200, // 2 hours in seconds
        'name' => 'agan_session',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    
    'csrf' => [
        'token_name' => 'csrf_token',
        'token_length' => 32,
        'expire_time' => 3600, // 1 hour
    ],
    
    'rate_limit' => [
        'login_attempts' => 5,
        'login_window' => 3600, // 1 hour
        'general_requests' => 100,
        'general_window' => 3600,
    ],
    
    // File Upload Settings
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'upload_path' => 'uploads/',
        'temp_path' => 'uploads/temp/',
    ],
    
    // Business Settings
    'business' => [
        'currency' => 'NPR',
        'currency_symbol' => 'Rs.',
        'tax_rate' => 13.0, // VAT rate in Nepal
        'delivery_fee' => 50.00,
        'min_order_amount' => 200.00,
        'max_delivery_distance' => 10, // km
        'prep_time_buffer' => 5, // minutes added to estimated prep time
    ],
    
    // Email Settings (for future implementation)
    'mail' => [
        'driver' => 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@agancafe.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Agan Cafe',
        ],
    ],
    
    // Payment Gateway Settings (for future implementation)
    // 'payment' => [
    //     'esewa' => [
    //         'merchant_id' => $_ENV['ESEWA_MERCHANT_ID'] ?? '',
    //         'secret_key' => $_ENV['ESEWA_SECRET_KEY'] ?? '',
    //         'success_url' => '/payment/success',
    //         'failure_url' => '/payment/failure',
    //     ],
    //     'khalti' => [
    //         'public_key' => $_ENV['KHALTI_PUBLIC_KEY'] ?? '',
    //         'secret_key' => $_ENV['KHALTI_SECRET_KEY'] ?? '',
    //     ],
    // ],
    
    // Logging Settings
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'error', // debug, info, warning, error
        'max_files' => 30,
        'path' => 'logs/',
    ],
    
    // Cache Settings (for future implementation)
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => 'cache/',
            ],
        ],
    ],
];

// config/constants.php - Application constants
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('LOG_PATH', APP_ROOT . '/logs/');
define('CACHE_PATH', APP_ROOT . '/cache/');

// Order statuses
define('ORDER_STATUS_PENDING', 'Pending');
define('ORDER_STATUS_CONFIRMED', 'Confirmed');
define('ORDER_STATUS_PREPARING', 'Preparing');
define('ORDER_STATUS_OUT_FOR_DELIVERY', 'Out for Delivery');
define('ORDER_STATUS_DELIVERED', 'Delivered');
define('ORDER_STATUS_CANCELLED', 'Cancelled');

// User roles
define('ADMIN_ROLE_SUPER', 'Super Admin');
define('ADMIN_ROLE_MANAGER', 'Manager');
define('ADMIN_ROLE_STAFF', 'Staff');

// Payment methods
define('PAYMENT_CASH_ON_DELIVERY', 'Cash on Delivery');
define('PAYMENT_ONLINE', 'Online Payment');

// Payment statuses
define('PAYMENT_STATUS_PENDING', 'Pending');
define('PAYMENT_STATUS_PAID', 'Paid');
define('PAYMENT_STATUS_FAILED', 'Failed');
define('PAYMENT_STATUS_REFUNDED', 'Refunded');