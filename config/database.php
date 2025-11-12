<?php
// config/database.php - Enhanced version
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'agan_cafe';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Database connection error. Please try again later.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Enhanced session management
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
    }
    
    public static function regenerate() {
        session_regenerate_id(true);
    }
    
    public static function destroy() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
    }
}

SessionManager::start();

// Enhanced CSRF Protection
class CSRFProtection {
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;
    
    public static function generateToken(): string {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::TOKEN_NAME];
    }
    
    public static function verifyToken(?string $token): bool {
        return isset($_SESSION[self::TOKEN_NAME]) 
            && !empty($token) 
            && hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    public static function validateRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[self::TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::verifyToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed');
            }
        }
    }
}

// Wrapper functions for backward compatibility
function generateCSRFToken(): string {
    return CSRFProtection::generateToken();
}

function verifyCSRFToken(?string $token): bool {
    return CSRFProtection::verifyToken($token);
}