<?php
/**
 * NOIA v4.0 - Interface d'Administration des Legal Facts
 *
 * INSTALLATION :
 * 1. Uploader ce fichier dans /www/noia/admin_legal_facts.php
 * 2. Accéder via : https://noia.erelys.fr/admin_legal_facts.php
 * 3. IMPORTANT : Sécuriser avec mot de passe (voir ligne 30)
 * 4. SUPPRIMER ce fichier après utilisation pour raisons de sécurité
 *
 * SÉCURITÉ :
 * - Modifier le mot de passe ci-dessous AVANT d'uploader
 * - Supprimer ce fichier après avoir ajouté vos legal facts
 * - Ne jamais laisser ce fichier accessible en production
 */

session_start();
require_once __DIR__ . '/config/config.php';

// ============================================================================
// SÉCURITÉ : Modifier ce mot de passe AVANT d'uploader
// ============================================================================
define('ADMIN_PASSWORD', 'noia_admin_2024'); // ⚠️ CHANGEZ CE MOT DE PASSE !

// ============================================================================
// Authentification simple
// ============================================================================
if (!isset($_SESSION['admin_legal_facts'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_legal_facts'] = true;
        } else {
            $error = "Mot de passe incorrect";
        }
    }

    if (!isset($_SESSION['admin_legal_facts'])) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>NOIA - Admin Legal Facts</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 50px; }
                .login-box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
                button { width: 100%; padding: 10px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; }
                button:hover { background: #1976D2; }
                .error { color: red; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>🔒 NOIA - Admin Legal Facts</h2>
                <form method="POST">
                    <label>Mot de passe :</label>
                    <input type="password" name="password" required autofocus>
                    <button type="submit">Connexion</button>
                </form>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ============================================================================
// Connexion à la base de données
// ============================================================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ============================================================================
// Traitement des actions
// ============================================================================
$message = '';

// Ajouter un legal fact
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $pdo->prepare("INSERT INTO legal_facts (categorie, titre, reference_legale, contenu, priority, source_url, date_maj, is_active) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 1)");
    $stmt->execute([
        $_POST['categorie'],
        $_POST['titre'],
        $_POST['reference_legale'],
        $_POST['contenu'],
        $_POST['priority'],
        $_POST['source_url']
    ]);
    $message = "✅ Legal fact ajouté avec succès !";
}

// Modifier un legal fact
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $stmt = $pdo->prepare("UPDATE legal_facts SET categorie=?, titre=?, reference_legale=?, contenu=?, priority=?, source_url=?, date_maj=CURDATE() WHERE id=?");
    $stmt->execute([
        $_POST['categorie'],
        $_POST['titre'],
        $_POST['reference_legale'],
        $_POST['contenu'],
        $_POST['priority'],
        $_POST['source_url'],
        $_POST['id']
    ]);
    $message = "✅ Legal fact modifié avec succès !";
}

// Activer/Désactiver
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE legal_facts SET is_active = 1 - is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = "✅ Statut modifié !";
}

// Supprimer (soft delete)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("UPDATE legal_facts SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $message = "✅ Legal fact désactivé !";
}

// Récupérer tous les legal facts
$legal_facts = $pdo->query("SELECT * FROM legal_facts ORDER BY categorie, is_active DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer un legal fact pour édition
$edit_fact = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM legal_facts WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_fact = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOIA - Admin Legal Facts</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .message { padding: 15px; background: #4CAF50; color: white; border-radius: 4px; margin-bottom: 20px; }
        .form-box { background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 150px; font-family: monospace; }
        button { padding: 12px 24px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-right: 10px; }
        button:hover { background: #1976D2; }
        button.cancel { background: #999; }
        button.cancel:hover { background: #777; }
        table { width: 100%; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-collapse: collapse; }
        th { background: #2196F3; color: white; padding: 15px; text-align: left; font-weight: 600; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge.active { background: #4CAF50; color: white; }
        .badge.inactive { background: #f44336; color: white; }
        .badge-cat { padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: white; }
        .cat-quorum { background: #FF5722; }
        .cat-fctva { background: #9C27B0; }
        .cat-ifse { background: #3F51B5; }
        .cat-cgct { background: #009688; }
        .cat-m57 { background: #FFC107; color: #333; }
        .cat-rh { background: #E91E63; }
        .cat-marches { background: #00BCD4; }
        .cat-budget { background: #4CAF50; }
        .cat-deliberation { background: #FF9800; }
        .cat-autre { background: #9E9E9E; }
        .actions a { text-decoration: none; padding: 6px 12px; margin: 0 3px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-toggle { background: #FF9800; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-box h3 { color: #999; font-size: 14px; margin-bottom: 10px; }
        .stat-box .number { font-size: 32px; font-weight: bold; color: #2196F3; }
        .logout { float: right; padding: 10px 20px; background: #f44336; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 NOIA - Gestion des Legal Facts</h1>
            <a href="?logout=1" class="logout" onclick="return confirm('Se déconnecter ?')">Déconnexion</a>
            <?php if (isset($_GET['logout'])) { session_destroy(); header('Location: admin_legal_facts.php'); exit; } ?>
        </div>

        <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-box">
                <h3>Total Legal Facts</h3>
                <div class="number"><?php echo count($legal_facts); ?></div>
            </div>
            <div class="stat-box">
                <h3>Actifs</h3>
                <div class="number"><?php echo count(array_filter($legal_facts, fn($f) => $f['is_active'] == 1)); ?></div>
            </div>
            <div class="stat-box">
                <h3>Inactifs</h3>
                <div class="number"><?php echo count(array_filter($legal_facts, fn($f) => $f['is_active'] == 0)); ?></div>
            </div>
        </div>

        <!-- Formulaire Ajout/Édition -->
        <div class="form-box">
            <h2><?php echo $edit_fact ? '✏️ Modifier Legal Fact' : '➕ Ajouter un Legal Fact'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_fact ? 'edit' : 'add'; ?>">
                <?php if ($edit_fact): ?>
                <input type="hidden" name="id" value="<?php echo $edit_fact['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Catégorie :</label>
                    <select name="categorie" required>
                        <option value="">-- Sélectionner --</option>
                        <?php
                        $categories = ['quorum', 'fctva', 'ifse', 'cgct', 'm57', 'rh', 'marches', 'budget', 'deliberation', 'autre'];
                        foreach ($categories as $cat) {
                            $selected = ($edit_fact && $edit_fact['categorie'] == $cat) ? 'selected' : '';
                            echo "<option value='$cat' $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Titre :</label>
                    <input type="text" name="titre" value="<?php echo $edit_fact['titre'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Référence Légale :</label>
                    <input type="text" name="reference_legale" value="<?php echo $edit_fact['reference_legale'] ?? ''; ?>" required placeholder="Ex: Article L2121-17 du CGCT">
                </div>

                <div class="form-group">
                    <label>Contenu :</label>
                    <textarea name="contenu" required><?php echo $edit_fact['contenu'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Priorité :</label>
                    <input type="number" name="priority" value="<?php echo $edit_fact['priority'] ?? 10; ?>" min="1" max="10" required>
                </div>

                <div class="form-group">
                    <label>Source URL :</label>
                    <input type="url" name="source_url" value="<?php echo $edit_fact['source_url'] ?? ''; ?>" placeholder="https://www.legifrance.gouv.fr/...">
                </div>

                <button type="submit"><?php echo $edit_fact ? '💾 Enregistrer' : '➕ Ajouter'; ?></button>
                <?php if ($edit_fact): ?>
                <a href="admin_legal_facts.php"><button type="button" class="cancel">❌ Annuler</button></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Liste des Legal Facts -->
        <div class="form-box">
            <h2>📋 Liste des Legal Facts (<?php echo count($legal_facts); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Catégorie</th>
                        <th>Titre</th>
                        <th>Référence</th>
                        <th>Date MAJ</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($legal_facts as $fact): ?>
                    <tr>
                        <td><?php echo $fact['id']; ?></td>
                        <td><span class="badge-cat cat-<?php echo $fact['categorie']; ?>"><?php echo strtoupper($fact['categorie']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($fact['titre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($fact['reference_legale']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($fact['date_maj'])); ?></td>
                        <td><?php echo $fact['priority']; ?>/10</td>
                        <td><span class="badge <?php echo $fact['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $fact['is_active'] ? 'Actif' : 'Inactif'; ?></span></td>
                        <td class="actions">
                            <a href="?edit=<?php echo $fact['id']; ?>" class="btn-edit">✏️ Éditer</a>
                            <a href="?toggle=<?php echo $fact['id']; ?>" class="btn-toggle" onclick="return confirm('Changer le statut ?')"><?php echo $fact['is_active'] ? '⏸️ Désactiver' : '▶️ Activer'; ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-box">
            <h3>⚠️ SÉCURITÉ</h3>
            <p>Ce fichier permet de modifier la base de données. Pour raisons de sécurité :</p>
            <ul>
                <li>✅ Utilisez-le uniquement pour configurer vos legal facts initiaux</li>
                <li>✅ SUPPRIMEZ ce fichier après utilisation</li>
                <li>✅ Utilisez phpMyAdmin pour les mises à jour régulières</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
$pdo = null;
?>
