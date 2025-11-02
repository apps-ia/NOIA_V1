<?php
/**
 * NOIA MVP - Authentication
 */

require_once __DIR__ . '/../Database/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Connexion utilisateur
     */
    public function login($email, $password) {
        $stmt = $this->db->prepare("
            SELECT id, email, nom, prenom, password_hash
            FROM noia_users
            WHERE email = ? AND is_active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];

            // Mettre à jour last_login
            $stmt = $this->db->prepare("UPDATE noia_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            return true;
        }

        return false;
    }

    /**
     * Déconnexion
     */
    public function logout() {
        session_destroy();
        return true;
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Récupérer l'utilisateur courant
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'nom' => $_SESSION['user_nom'],
            'prenom' => $_SESSION['user_prenom']
        ];
    }

    /**
     * Rate limiting
     */
    public function checkRateLimit($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM noia_conversations
            WHERE user_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$userId, RATE_LIMIT_PERIOD]);
        $result = $stmt->fetch();

        return $result['count'] < RATE_LIMIT_REQUESTS;
    }
}
