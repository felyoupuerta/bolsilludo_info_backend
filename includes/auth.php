<?php
require_once 'config.php';

class Auth {
    
    /**
     * Sanitiza y valida la entrada del usuario
     */
    private static function sanitizeInput($input) {
        return trim(htmlspecialchars(strip_tags($input)));
    }
    
    /**
     * Registra errores para debugging
     */
    private static function logError($message) {
        error_log("[AUTH ERROR] " . date('Y-m-d H:i:s') . " - " . $message);
    }
    
    /**
     * Valida el formato del email
     */
    private static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida la fortaleza de la contraseña
     */
    private static function validatePassword($password) {
        // Mínimo 6 caracteres
        if (strlen($password) < 6) {
            return false;
        }
        return true;
    }
    
    /**
     * Autentica un usuario
     */
    public static function login($username, $password) {
        try {
            // Sanitizar entrada
            $username = self::sanitizeInput($username);
            
            // Validar entrada
            if (empty($username) || empty($password)) {
                self::logError("Login attempt with empty credentials");
                return false;
            }
            
            $db = getDBConnection();
            if (!$db) {
                self::logError("Database connection failed during login");
                return false;
            }
            
            $stmt = $db->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) {
                self::logError("Failed to prepare login statement: " . $db->error);
                $db->close();
                return false;
            }
            
            $stmt->bind_param("s", $username);
            
            if (!$stmt->execute()) {
                self::logError("Failed to execute login query: " . $stmt->error);
                $stmt->close();
                $db->close();
                return false;
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['login_time'] = time();
                    
                    $stmt->close();
                    $db->close();
                    return true;
                } else {
                    self::logError("Invalid password for user: " . $username);
                }
            } else {
                self::logError("User not found: " . $username);
            }
            
            $stmt->close();
            $db->close();
            return false;
            
        } catch (Exception $e) {
            self::logError("Exception during login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un nuevo usuario
     */
    public static function register($username, $email, $password) {
        try {
            // Sanitizar entrada
            $username = self::sanitizeInput($username);
            $email = self::sanitizeInput($email);
            
            // Validar entrada
            if (empty($username) || empty($email) || empty($password)) {
                self::logError("Registration attempt with empty fields");
                return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
            }
            
            if (strlen($username) < 3) {
                return ['success' => false, 'error' => 'El nombre de usuario debe tener al menos 3 caracteres'];
            }
            
            if (!self::validateEmail($email)) {
                return ['success' => false, 'error' => 'El formato del email no es válido'];
            }
            
            if (!self::validatePassword($password)) {
                return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
            }
            
            $db = getDBConnection();
            if (!$db) {
                self::logError("Database connection failed during registration");
                return ['success' => false, 'error' => 'Error de conexión a la base de datos'];
            }
            
            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            if (!$stmt) {
                self::logError("Failed to prepare check user statement: " . $db->error);
                $db->close();
                return ['success' => false, 'error' => 'Error interno del servidor'];
            }
            
            $stmt->bind_param("ss", $username, $email);
            
            if (!$stmt->execute()) {
                self::logError("Failed to execute check user query: " . $stmt->error);
                $stmt->close();
                $db->close();
                return ['success' => false, 'error' => 'Error interno del servidor'];
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                $db->close();
                return ['success' => false, 'error' => 'El nombre de usuario o email ya está en uso'];
            }
            
            $stmt->close();
            
            // Crear nuevo usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            
            if (!$stmt) {
                self::logError("Failed to prepare insert user statement: " . $db->error);
                $db->close();
                return ['success' => false, 'error' => 'Error interno del servidor'];
            }
            
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                $stmt->close();
                $db->close();
                return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
            } else {
                self::logError("Failed to insert new user: " . $stmt->error);
                $stmt->close();
                $db->close();
                return ['success' => false, 'error' => 'Error al crear el usuario'];
            }
            
        } catch (Exception $e) {
            self::logError("Exception during registration: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Cierra la sesión del usuario
     */
    public static function logout() {
        // Limpiar todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir al inicio
        header("Location: index.php");
        exit();
    }
    
    /**
     * Verifica si el usuario está logueado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Verifica si el usuario es administrador
     */
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Requiere que el usuario esté logueado
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['flash_error'] = 'Por favor, inicia sesión para acceder a esta página.';
            header("Location: login.php");
            exit();
        }
    }
    
    /**
     * Requiere permisos de administrador
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            $_SESSION['flash_error'] = 'No tienes permisos para acceder a esta página';
            header("Location: index.php");
            exit();
        }
    }
    
    /**
     * Obtiene información del usuario actual
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Verifica si la sesión ha expirado (opcional)
     */
    public static function checkSessionTimeout($timeout = 3600) { // 1 hora por defecto
        if (self::isLoggedIn() && isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > $timeout) {
                self::logout();
                return false;
            }
        }
        return true;
    }
}
?>
