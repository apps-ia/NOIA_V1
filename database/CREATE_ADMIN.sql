-- ============================================================================
-- NOIA v4.0 - Création/Réinitialisation Compte Admin
-- ============================================================================
-- Ce script crée ou réinitialise le compte administrateur
--
-- IDENTIFIANTS PAR DÉFAUT :
-- Email    : admin@noia.local
-- Password : admin123
--
-- ⚠️ CHANGEZ CE MOT DE PASSE APRÈS LA PREMIÈRE CONNEXION !
-- ============================================================================

-- Supprimer l'ancien compte admin s'il existe
DELETE FROM users WHERE email = 'admin@noia.local';

-- Créer le nouveau compte admin
-- Hash généré avec password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (
    email,
    password_hash,
    nom,
    prenom,
    role,
    commune,
    is_active,
    created_at,
    last_login,
    login_attempts,
    locked_until
)
VALUES (
    'admin@noia.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrateur',
    'NOIA',
    'admin',
    NULL,
    1,
    NOW(),
    NULL,
    0,
    NULL
);

-- Vérifier que le compte a été créé
SELECT
    'Compte admin créé avec succès !' AS status,
    id,
    email,
    nom,
    role,
    is_active,
    created_at
FROM users
WHERE email = 'admin@noia.local';

-- ============================================================================
-- ALTERNATIVE : Créer un admin avec un mot de passe personnalisé
-- ============================================================================
-- Si vous voulez un autre mot de passe, utilisez la fonction PASSWORD() de MySQL :
--
-- DELETE FROM users WHERE email = 'votre@email.fr';
--
-- INSERT INTO users (email, password_hash, nom, prenom, role, is_active, created_at, login_attempts)
-- VALUES (
--     'votre@email.fr',
--     PASSWORD('VotreMotDePasse123'),
--     'Votre Nom',
--     'Votre Prénom',
--     'admin',
--     1,
--     NOW(),
--     0
-- );
--
-- ATTENTION : PASSWORD() de MySQL ne génère PAS un hash bcrypt valide !
-- Cette méthode ne fonctionnera PAS avec PHP password_verify().
--
-- Pour un mot de passe personnalisé, utilisez plutôt le script debug-auth.php
-- ============================================================================
