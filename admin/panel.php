<?php
require_once __DIR__ . '/queries.php';

admin_require_login();

$message = get_flash();
$editingAdmin = null;
$editingContact = null;
$editingSocial = null;
$editingContactSocial = null;

if (!empty($_GET['edit_admin_id'])) {
    $editingAdmin = get_admin($_GET['edit_admin_id']);
}

if (!empty($_GET['edit_contact_id'])) {
    $editingContact = get_contact($_GET['edit_contact_id']);
}

if (!empty($_GET['edit_social_id'])) {
    $editingSocial = get_social($_GET['edit_social_id']);
}

if (!empty($_GET['edit_contact_social_id'])) {
    $editingContactSocial = get_contact_social($_GET['edit_contact_social_id']);
}

$logs = get_logs();
$admins = get_admins();
$contacts = get_contacts();
$socials = get_socials();
$contactSocials = get_contact_socials();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">
    <div class="container-fluid admin-panel">
    <div class="admin-header">
        <div>
            <h1>Painel Admin</h1>
            <p>Logado como: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
        </div>

        <form method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres sair?');">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Sair</button>
        </form>
    </div>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <hr>

    <div class="admin-logs">
    <h2>Logs</h2>
        <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>id</th>
                <th>admin_id</th>
                <th>action</th>
                <th>target_table</th>
                <th>created_at</th>
            </tr>
        </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['id']); ?> </td>
                        <td><?php echo htmlspecialchars($log['admin_id']); ?> </td>
                        <td><?php echo htmlspecialchars($log['action']); ?> </td>
                        <td><?php echo htmlspecialchars($log['target_table']); ?> </td>
                        <td><?php echo htmlspecialchars($log['created_at']); ?> </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>
    <div class="row admin-section">
    <div class="col-md-3">
    <h2><?php echo $editingAdmin ? 'Atualizar Admin' : 'Adicionar Admin'; ?></h2>
    <form class="admin-form" method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres <?php echo $editingAdmin ? 'atualizar este admin' : 'adicionar este admin'; ?>?');">
        <input type="hidden" name="action" value="<?php echo $editingAdmin ? 'edit_admin' : 'add_admin'; ?>">
        <?php if ($editingAdmin): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editingAdmin['id']); ?>">
        <?php endif; ?>

        <p>
            <label>Username<br>
                <input type="text" name="username" value="<?php echo htmlspecialchars($editingAdmin['username'] ?? ''); ?>" required>
            </label>
        </p>

        <p>
            <label>Password<br>
                <input type="text" name="password" <?php echo $editingAdmin ? '' : 'required'; ?>>
            </label>
        </p>

        <button type="submit"><?php echo $editingAdmin ? 'Atualizar Admin' : 'Adicionar Admin'; ?></button>
        <?php if ($editingAdmin): ?>
            <a href="panel.php">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="col-md-9">
    <h2>Admins</h2>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Password</th>
                <th>Editar</th>
                <th>Apagar</th>
                
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?php echo htmlspecialchars($admin['id']); ?> </td>
                    <td><?php echo htmlspecialchars($admin['username']); ?> </td>
                    <td><?php echo htmlspecialchars($admin['password']); ?> </td>
                    <td>
                        <form method="get" action="panel.php" onsubmit="return confirm('Tens a certeza que queres editar este admin?');">
                            <input type="hidden" name="edit_admin_id" value="<?php echo htmlspecialchars($admin["id"]); ?>">
                            <button type="submit">Editar</button>
                        </form>
                   </td>
                    <td>
                        <form method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres apagar este admin?');">
                            <input type="hidden" name="action" value="delete_admin">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($admin["id"]); ?>">
                            <button type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
    
    <hr>

    <div class="row admin-section">
    <div class="col-md-3">
    <h2><?php echo $editingContact ? 'Atualizar contacto' : 'Adicionar contacto'; ?></h2>
    <form class="admin-form" method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres <?php echo $editingContact ? 'atualizar este contacto' : 'adicionar este contacto'; ?>?');">
        <input type="hidden" name="action" value="<?php echo $editingContact ? 'edit_contact' : 'add_contact'; ?>">
        <?php if ($editingContact): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editingContact['id']); ?>">
        <?php endif; ?>

        <p>
            <label>Nome<br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editingContact['name'] ?? ''); ?>" required>
            </label>
        </p>

        <p>
            <label>Email<br>
                <input type="email" name="email" value="<?php echo htmlspecialchars($editingContact['email'] ?? ''); ?>" required>
            </label>
        </p>

        <p>
            <label>Codigo do pais<br>
                <input type="text" name="country_code" maxlength="3" value="<?php echo htmlspecialchars($editingContact['country_code'] ?? ''); ?>" required>
            </label>
        </p>

        <p>
            <label>Telefone<br>
                <input type="text" name="phone" maxlength="15" value="<?php echo htmlspecialchars($editingContact['phone'] ?? ''); ?>" required>
            </label>
        </p>

        <p>
            <label>Imagem<br>
                <input type="text" name="image_path" placeholder="ex: 731945.png" value="<?php echo htmlspecialchars($editingContact['image_path'] ?? ''); ?>">
            </label>
        </p>

        <button type="submit"><?php echo $editingContact ? 'Atualizar contacto' : 'Adicionar contacto'; ?></button>
        <?php if ($editingContact): ?>
            <a href="panel.php">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="col-md-9">
    <h2>Contactos</h2>
    <table class="contacts-table" border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Imagem</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td><?php echo htmlspecialchars($contact['id']); ?></td>
                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                    <td><?php echo htmlspecialchars('+' . $contact['country_code'] . ' ' . $contact['phone']); ?></td>
                    <td><?php echo htmlspecialchars($contact['image_path'] ?? ''); ?></td>
                    <td>
                        <form method="get" action="panel.php" onsubmit="return confirm('Tens a certeza que queres editar este contacto?');">
                            <input type="hidden" name="edit_contact_id" value="<?php echo htmlspecialchars($contact['id']); ?>">
                            <button type="submit">Editar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres apagar este contacto?');">
                            <input type="hidden" name="action" value="delete_contact">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($contact['id']); ?>">
                            <button type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>

    <hr>

    <div class="row admin-section">
    <div class="col-md-3">
    <h2><?php echo $editingSocial ? 'Atualizar rede social' : 'Adicionar rede social'; ?></h2>
    <form class="admin-form" method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres <?php echo $editingSocial ? 'atualizar esta rede social' : 'adicionar esta rede social'; ?>?');">
        <input type="hidden" name="action" value="<?php echo $editingSocial ? 'edit_social' : 'add_social'; ?>">
        <?php if ($editingSocial): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editingSocial['id']); ?>">
        <?php endif; ?>

        <p>
            <label>Nome<br>
                <input type="text" name="name" placeholder="ex: LinkedIn" value="<?php echo htmlspecialchars($editingSocial['name'] ?? ''); ?>" required>
            </label>
        </p>

        <button type="submit"><?php echo $editingSocial ? 'Atualizar rede social' : 'Adicionar rede social'; ?></button>
        <?php if ($editingSocial): ?>
            <a href="panel.php">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="col-md-9">
    <h2>Redes sociais</h2>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($socials as $social): ?>
                <tr>
                    <td><?php echo htmlspecialchars($social['id']); ?></td>
                    <td><?php echo htmlspecialchars($social['name']); ?></td>
                    <td>
                        <form method="get" action="panel.php" onsubmit="return confirm('Tens a certeza que queres editar esta rede social?');">
                            <input type="hidden" name="edit_social_id" value="<?php echo htmlspecialchars($social['id']); ?>">
                            <button type="submit">Editar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres apagar esta rede social?');">
                            <input type="hidden" name="action" value="delete_social">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($social['id']); ?>">
                            <button type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>

    <hr>

    <div class="row admin-section">
    <div class="col-md-3">
    <h2><?php echo $editingContactSocial ? 'Atualizar rede associada' : 'Associar rede social a contacto'; ?></h2>
    <form class="admin-form" method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres <?php echo $editingContactSocial ? 'atualizar esta associacao' : 'associar esta rede social ao contacto'; ?>?');">
        <input type="hidden" name="action" value="<?php echo $editingContactSocial ? 'edit_contact_social' : 'add_contact_social'; ?>">
        <?php if ($editingContactSocial): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editingContactSocial['id']); ?>">
        <?php endif; ?>

        <p>
            <label>Contacto<br>
                <select name="contact_id" required>
                    <?php foreach ($contacts as $contact): ?>
                        <option value="<?php echo htmlspecialchars($contact['id']); ?>" <?php echo (int) ($editingContactSocial['contact_id'] ?? 0) === (int) $contact['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($contact['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>

        <p>
            <label>Rede social<br>
                <select name="social_id" required>
                    <?php foreach ($socials as $social): ?>
                        <option value="<?php echo htmlspecialchars($social['id']); ?>" <?php echo (int) ($editingContactSocial['social_id'] ?? 0) === (int) $social['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($social['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>

        <p>
            <label>Valor/link<br>
                <input type="text" name="value" placeholder="ex: /in/nome/" value="<?php echo htmlspecialchars($editingContactSocial['value'] ?? ''); ?>" required>
            </label>
        </p>

        <button type="submit"><?php echo $editingContactSocial ? 'Atualizar associacao' : 'Associar'; ?></button>
        <?php if ($editingContactSocial): ?>
            <a href="panel.php">Cancelar</a>
        <?php endif; ?>
    </form>
    </div>

    <div class="col-md-9">
    <h2>Redes associadas</h2>
    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th>ID</th>
                <th>Contacto</th>
                <th>Rede social</th>
                <th>Valor/link</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contactSocials as $contactSocial): ?>
                <tr>
                    <td><?php echo htmlspecialchars($contactSocial['id']); ?></td>
                    <td><?php echo htmlspecialchars($contactSocial['contact_name']); ?></td>
                    <td><?php echo htmlspecialchars($contactSocial['social_name']); ?></td>
                    <td><?php echo htmlspecialchars($contactSocial['value']); ?></td>
                    <td>
                        <form method="get" action="panel.php" onsubmit="return confirm('Tens a certeza que queres editar esta associacao?');">
                            <input type="hidden" name="edit_contact_social_id" value="<?php echo htmlspecialchars($contactSocial['id']); ?>">
                            <button type="submit">Editar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="queries.php" onsubmit="return confirm('Tens a certeza que queres apagar esta associacao?');">
                            <input type="hidden" name="action" value="delete_contact_social">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($contactSocial['id']); ?>">
                            <button type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
    </div>
</body>
</html>
