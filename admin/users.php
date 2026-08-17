<?php
require_once 'includes/core.php';
requireLogin();

$current = getCurrentUser();
if ($current['role'] !== 'admin') {
    header('Location: dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $user = [
            'id'       => $_POST['id'] ?: generateId(),
            'username' => trim($_POST['username'] ?? ''),
            'name'     => sanitize($_POST['name'] ?? ''),
            'role'     => $_POST['role'] ?? 'editor',
        ];
        if (!empty($_POST['password'])) $user['password_plain'] = $_POST['password'];
        elseif (empty($_POST['id'])) {
            header('Location: users.php?err=' . urlencode('Cal una contrasenya per a un usuari nou.')); exit;
        }
        saveUser($user);
        header('Location: users.php?saved=1'); exit;
    }
    if ($action === 'delete') {
        if (count(getUsers()) <= 1) {
            header('Location: users.php?err=' . urlencode('No pots eliminar l\'últim usuari.')); exit;
        }
        deleteUser($_POST['id']);
        header('Location: users.php?deleted=1'); exit;
    }
}

$users = getUsers();
$edit_id = $_GET['id'] ?? null;
$edit = $edit_id ? getUser($edit_id) : null;

$page_title    = 'Usuaris';
$page_subtitle = count($users) . ' usuari(s) amb accés al panell';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Usuari guardat.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Usuari eliminat.</div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert alert-error">❌ <?= htmlspecialchars($_GET['err']) ?></div><?php endif; ?>

<?php if (empty($users)): ?>
<div class="card" style="margin-bottom:20px">
    <div style="padding:20px 24px;color:#6b7280;font-size:.88rem;line-height:1.6">
        ℹ️ Ara mateix encara no has creat cap usuari — el panell funciona amb la contrasenya única de sempre (Configuració → Canviar contrasenya).
        Si crees el primer usuari ací, <strong>a partir d'eixe moment caldrà entrar amb usuari + contrasenya</strong> (la contrasenya única deixarà de funcionar).
        Recomanem que el primer usuari que crees siga per a tu mateix, amb rol "Administrador".
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title"><?= $edit ? 'Editar usuari' : '+ Nou usuari' ?></div></div>
    <form method="POST" class="card-body form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">
        <div class="form-row-2">
            <div class="form-group"><label>Nom</label><input type="text" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" placeholder="Nom i cognoms"></div>
            <div class="form-group"><label>Usuari (per entrar) *</label><input type="text" name="username" value="<?= htmlspecialchars($edit['username'] ?? '') ?>" required placeholder="ex: dari, maria..."></div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label>Contrasenya <?= $edit ? '(deixa-la en blanc per no canviar-la)' : '*' ?></label>
                <input type="password" name="password" autocomplete="new-password" <?= $edit ? '' : 'required' ?>>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role">
                    <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador (accés total)</option>
                    <option value="editor" <?= ($edit['role'] ?? 'editor') === 'editor' ? 'selected' : '' ?>>Editor (sense usuaris ni configuració)</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit ? 'Actualitzar usuari' : '+ Crear usuari' ?></button>
            <?php if ($edit): ?><a href="users.php" class="btn btn-secondary btn-sm">Cancel·lar</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Tots els usuaris</div></div>
    <?php if (empty($users)): ?>
    <div style="padding:32px;text-align:center;color:#9ca3af">Cap usuari creat encara.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Nom</th><th>Usuari</th><th>Rol</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= htmlspecialchars($u['name'] ?: '—') ?></strong></td>
            <td style="font-family:monospace;font-size:.85rem"><?= htmlspecialchars($u['username']) ?></td>
            <td><span class="badge <?= ($u['role'] ?? '') === 'admin' ? 'badge-gold' : 'badge-gray' ?>"><?= ($u['role'] ?? 'editor') === 'admin' ? 'Administrador' : 'Editor' ?></span></td>
            <td class="td-actions">
                <a href="users.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                <form method="POST" onsubmit="return confirm('Eliminar este usuari? Ja no podrà entrar al panell.')" style="display:inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
