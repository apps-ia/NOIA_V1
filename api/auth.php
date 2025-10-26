<?php
/**
 * NOIA v4.0 - Système d'Authentification Sécurisé
 *
 * Fonctionnalités :
 * - Authentification multi-niveaux (admin, agent, guest)
 * - Protection contre brute force (limitation tentatives)
 * - Sessions sécurisées avec tokens CSRF
 * - Journalisation des accès
 * - Conformité RGPD
 */

require_once __DIR__ . '/../config/config.php';

class Auth {

    private $pdo;
    private $session_duration = 7200; // 2 heures par défaut
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes en secondes

    public function __construct() {
        $this->initDatabase();
        $this->initSession();
    }

    /**
     * Initialiser la connexion base de données
     */
    private function initDatabase() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Erreur de connexion à la base de données');
        }
    }

    /**
     * Initialiser la session sécurisée
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);

            session_start();

            // Régénérer l'ID de session périodiquement
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Authentifier un utilisateur
     */
    public function login($email, $password, $remember = false) {
        try {
            // Validation des entrées
            if (empty($email) || empty($password)) {
                return ['success' => false, 'error' => 'Email et mot de passe requis'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Email invalide'];
            }

            // Vérifier si le compte est verrouillé
            if ($this->isAccountLocked($email)) {
                $this->logAccess(null, 'login_blocked', 'blocked', 'Compte temporairement verrouillé');
                return ['success' => false, 'error' => 'Compte temporairement verrouillé. Réessayez dans 15 minutes.'];
            }

            // Récupérer l'utilisateur
            $stmt = $this->pdo->prepare("
                SELECT id, email, password_hash, nom, prenom, role, commune, is_active
                FROM users
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->incrementLoginAttempts($email);
                $this->logAccess(null, 'login_failed', 'error', 'Utilisateur non trouvé');
                return ['success' => false, 'error' => 'Identifiants invalides'];
            }

            // Vérifier si le compte est actif
            if (!$user['is_active']) {
                $this->logAccess($user['id'], 'login_failed', 'error', 'Compte désactivé');
                return ['success' => false, 'error' => 'Compte désactivé. Contactez l\'administrateur.'];
            }

            // Vérifier le mot de passe
            if (!password_verify($password, $user['password_hash'])) {
                $this->incrementLoginAttempts($email);
                $this->logAccess($user['id'], 'login_failed', 'error', 'Mot de passe incorrect');
                return ['success' => false, 'error' => 'Identifiants invalides'];
            }

            // Authentification réussie
            $this->resetLoginAttempts($email);
            $this->updateLastLogin($user['id']);

            // Créer la session
            $session_id = $this->createSession($user, $remember);

            // Stocker les informations en session PHP
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_commune'] = $user['commune'];
            $_SESSION['csrf_token'] = $this->generateCSRFToken();
            $_SESSION['session_id'] = $session_id;

            $this->logAccess($user['id'], 'login_success', 'success');

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'nom' => $user['nom'],
                    'prenom' => $user['prenom'],
                    'role' => $user['role'],
                    'commune' => $user['commune']
                ],
                'session_id' => $session_id,
                'csrf_token' => $_SESSION['csrf_token']
            ];

        } catch (Exception $e) {
            $this->logError('Login error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de l\'authentification'];
        }
    }

    /**
     * Déconnecter l'utilisateur
     */
    public function logout() {
        try {
            if (isset($_SESSION['session_id'])) {
                // Supprimer la session de la base
                $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['session_id']]);

                $this->logAccess($_SESSION['user_id'] ?? null, 'logout', 'success');
            }

            // Détruire la session PHP
            $_SESSION = [];

            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }

            session_destroy();

            return ['success' => true, 'message' => 'Déconnexion réussie'];

        } catch (Exception $e) {
            $this->logError('Logout error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la déconnexion'];
        }
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function check() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
            return false;
        }

        try {
            // Vérifier que la session existe en base et n'est pas expirée
            $stmt = $this->pdo->prepare("
                SELECT user_id, expires_at
                FROM sessions
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                'id' => $_SESSION['session_id'],
                'user_id' => $_SESSION['user_id']
            ]);

            $session = $stmt->fetch();

            if (!$session) {
                $this->logout();
                return false;
            }

            // Vérifier expiration
            if (strtotime($session['expires_at']) < time()) {
                $this->logout();
                return false;
            }

            // Mettre à jour last_activity
            $this->updateSessionActivity($_SESSION['session_id']);

            return true;

        } catch (Exception $e) {
            $this->logError('Check session error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir l'utilisateur courant
     */
    public function getCurrentUser() {
        if (!$this->check()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, nom, prenom, role, commune
                FROM users
                WHERE id = :id AND is_active = 1
            ");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            return $stmt->fetch();

        } catch (Exception $e) {
            $this->logError('Get current user error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier le rôle de l'utilisateur
     */
    public function hasRole($required_role) {
        if (!$this->check()) {
            return false;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Hiérarchie : admin > agent > guest
        $hierarchy = ['admin' => 3, 'agent' => 2, 'guest' => 1];

        $user_level = $hierarchy[$user['role']] ?? 0;
        $required_level = $hierarchy[$required_role] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Vérifier le token CSRF
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Créer une session en base de données
     */
    private function createSession($user, $remember = false) {
        $session_id = bin2hex(random_bytes(32));
        $token = bin2hex(random_bytes(32));

        $duration = $remember ? 86400 * 7 : $this->session_duration; // 7 jours ou 2h
        $expires_at = date('Y-m-d H:i:s', time() + $duration);

        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (id, user_id, token, ip_address, user_agent, created_at, expires_at, last_activity)
            VALUES (:id, :user_id, :token, :ip, :user_agent, NOW(), :expires, NOW())
        ");

        $stmt->execute([
            'id' => $session_id,
            'user_id' => $user['id'],
            'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires' => $expires_at
        ]);

        return $session_id;
    }

    /**
     * Mettre à jour l'activité de la session
     */
    private function updateSessionActivity($session_id) {
        $stmt = $this->pdo->prepare("UPDATE sessions SET last_activity = NOW() WHERE id = :id");
        $stmt->execute(['id' => $session_id]);
    }

    /**
     * Mettre à jour la dernière connexion
     */
    private function updateLastLogin($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
    }

    /**
     * Vérifier si le compte est verrouillé
     */
    private function isAccountLocked($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT login_attempts, locked_until
                FROM users
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Incrémenter les tentatives de connexion échouées
     */
    private function incrementLoginAttempts($email) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET login_attempts = login_attempts + 1,
                    locked_until = CASE
                        WHEN login_attempts + 1 >= :max_attempts
                        THEN DATE_ADD(NOW(), INTERVAL :lockout_duration SECOND)
                        ELSE NULL
                    END
                WHERE email = :email
            ");

            $stmt->execute([
                'max_attempts' => $this->max_login_attempts,
                'lockout_duration' => $this->lockout_duration,
                'email' => $email
            ]);

        } catch (Exception $e) {
            $this->logError('Increment login attempts error: ' . $e->getMessage());
        }
    }

    /**
     * Réinitialiser les tentatives de connexion
     */
    private function resetLoginAttempts($email) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET login_attempts = 0, locked_until = NULL
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);

        } catch (Exception $e) {
            $this->logError('Reset login attempts error: ' . $e->getMessage());
        }
    }

    /**
     * Générer un token CSRF
     */
    private function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Journaliser un accès
     */
    private function logAccess($user_id, $action, $status, $error_message = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO access_logs (user_id, action, ip_address, user_agent, status, error_message, created_at)
                VALUES (:user_id, :action, :ip, :user_agent, :status, :error_message, NOW())
            ");

            $stmt->execute([
                'user_id' => $user_id,
                'action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'status' => $status,
                'error_message' => $error_message
            ]);

        } catch (Exception $e) {
            error_log('Access log error: ' . $e->getMessage());
        }
    }

    /**
     * Logger une erreur
     */
    private function logError($message) {
        if (DEBUG_MODE) {
            error_log('[NOIA Auth] ' . $message);
        }
    }
}
