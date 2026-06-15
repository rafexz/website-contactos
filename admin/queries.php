<?php
session_start();

require_once __DIR__ . '/../database/db.php';

if (basename($_SERVER['SCRIPT_NAME']) === 'queries.php' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Acesso negado.');
}

function redirect_to($path)
{
    header('Location: ' . $path);
    exit;
}

function set_flash($message)
{
    $_SESSION['flash'] = $message;
}

function get_flash()
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $message;
}

function admin_is_logged_in()
{
    return !empty($_SESSION['admin_id']);
}

function admin_require_login()
{
    if (!admin_is_logged_in()) {
        redirect_to('login.php');
    }

    if (!get_admin($_SESSION['admin_id'])) {
        admin_logout();
        redirect_to('login.php');
    }
}

function admin_login($username, $password)
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    $passwordIsValid = password_verify($password, $admin['password']);
    $passwordNeedsHash = false;

    if (!$passwordIsValid && hash_equals($admin['password'], $password)) {
        $passwordIsValid = true;
        $passwordNeedsHash = true;
    }

    if (!$passwordIsValid) {
        return false;
    }

    if ($passwordNeedsHash || password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
        $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            (int) $admin['id'],
        ]);
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    return true;
}

function admin_logout()
{
    $_SESSION = [];
    session_destroy();
}

function get_logs()
{
    global $pdo;

    return $pdo->query('SELECT * FROM admin_logs ORDER BY id DESC')->fetchAll();
}

function get_admins()
{
    global $pdo;

    return $pdo->query('SELECT * FROM admins ORDER BY id DESC')->fetchAll();
}

function get_admin($id)
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);

    return $stmt->fetch();
}

function get_contacts()
{
    global $pdo;

    return $pdo->query('SELECT * FROM contacts ORDER BY id DESC')->fetchAll();
}

function get_contact($id)
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM contacts WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);

    return $stmt->fetch();
}

function get_socials()
{
    global $pdo;

    return $pdo->query('SELECT * FROM socials ORDER BY name')->fetchAll();
}

function get_social($id)
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM socials WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);

    return $stmt->fetch();
}

function get_contact_socials()
{
    global $pdo;

    $sql = '
        SELECT
            cs.id,
            cs.contact_id,
            cs.social_id,
            cs.value,
            c.name AS contact_name,
            s.name AS social_name
        FROM contact_socials cs
        JOIN contacts c
            ON c.id = cs.contact_id
        JOIN socials s
            ON s.id = cs.social_id
        ORDER BY cs.id DESC
    ';

    return $pdo->query($sql)->fetchAll();
}

function get_contact_social($id)
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM contact_socials WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);

    return $stmt->fetch();
}

function add_admin($data)
{
    global $pdo;

    $hashedPassword = password_hash(trim($data['password']), PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
    INSERT INTO admins (username, password)
    VALUES (?, ?)
    ');

    $stmt->execute([
        trim($data['username']),
        $hashedPassword
    ]);

    $newAdminId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
    VALUES (?, 'insert', 'admins', ?)
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $newAdminId
    ]);
}

function edit_admin($data)
{
    global $pdo;

    $id = (int) $data['id'];
    $password = trim($data['password'] ?? '');

    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            UPDATE admins 
            SET username = ?, password = ?
            WHERE id = ?
        ');

        $stmt->execute([
            trim($data['username']),
            $hashedPassword,
            $id
        ]);

    } else {
    $stmt = $pdo->prepare('
            UPDATE admins
            SET username = ?
            WHERE id = ?
        ');

    $stmt->execute([
        trim($data['username']),
        $id
    ]);

    }
    $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_table, target_id)
            VALUES (?, 'update', 'admins', ?)
        ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
    
}

function delete_admin($id)
{
    global $pdo;
    $id = (int) $id;

    $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
    $stmt->execute([(int) $id]);

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'delete', 'admins', ?)
    ");
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
}

function add_contact($data)
{
    global $pdo;

    $stmt = $pdo->prepare('
        INSERT INTO contacts (name, email, country_code, phone, image_path)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        trim($data['name']),
        trim($data['email']),
        trim($data['country_code']),
        trim($data['phone']),
        trim($data['image_path']) !== '' ? trim($data['image_path']) : null,
    ]);

    $newContactId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'insert', 'contacts', ?)
    ");
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $newContactId
    ]);
}

function edit_contact($data)
{
    global $pdo;
    $id = (int) $data['id'];

    $stmt = $pdo->prepare('
        UPDATE contacts
        SET name = ?, email = ?, country_code = ?, phone = ?, image_path = ?
        WHERE id = ?
    ');

    $stmt->execute([
        trim($data['name']),
        trim($data['email']),
        trim($data['country_code']),
        trim($data['phone']),
        trim($data['image_path']) !== '' ? trim($data['image_path']) : null,
        $id,
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'update', 'contacts', ?)
    ");
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
}

function delete_contact($id)
{
    global $pdo;
    $id = (int) $id;

    $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = ?');
    $stmt->execute([(int) $id]);

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'delete', 'contacts', ?)
    ");
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
}

function add_social($name)
{
    global $pdo;

    $stmt = $pdo->prepare('INSERT INTO socials (name) VALUES (?)');
    $stmt->execute([trim($name)]);

    $newSocialId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'insert', 'socials', ?)
    ");
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $newSocialId
    ]);
}

function edit_social($data)
{
    global $pdo;
    $id = (int) $data['id'];

    $stmt = $pdo->prepare('UPDATE socials SET name = ? WHERE id = ?');
    $stmt->execute([
        trim($data['name']),
        $id
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id)
        VALUES (?, 'update', 'socials', ?)
    ");

    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
}

function delete_social($id)
{
    global $pdo;
    $id = (int) $id;

    $stmt = $pdo->prepare('DELETE FROM socials WHERE id = ?');
    $stmt->execute([(int) $id]);

    $stmt = $pdo->prepare("
    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
    VALUES (?, 'delete', 'socials', ?)
    ");

    $stmt->execute([
        $_SESSION['admin_id'],
        $id
    ]);
}

function add_contact_social($data)
{
    global $pdo;

    $stmt = $pdo->prepare('
        INSERT INTO contact_socials (contact_id, social_id, value)
        VALUES (?, ?, ?)
    ');

    $stmt->execute([
        (int) $data['contact_id'],
        (int) $data['social_id'],
        trim($data['value']),
    ]);

    $newContactSocialID = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
    VALUES (?, 'insert', 'contact_socials', ?)
    ");

    $stmt->execute([
    $_SESSION['admin_id'],
    $newContactSocialID
    ]);
}

function edit_contact_social($data)
{
    global $pdo;
    $id = (int) $data['id'];

    $stmt = $pdo->prepare('
        UPDATE contact_socials
        SET contact_id = ?, social_id = ?, value = ?
        WHERE id = ?
    ');

    $stmt->execute([
        (int) $data['contact_id'],
        (int) $data['social_id'],
        trim($data['value']),
        $id
    ]);

    $stmt = $pdo->prepare("
    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
    VALUES (?, 'update', 'contact_socials', ?)
    ");

    $stmt->execute([
    $_SESSION['admin_id'],
    $id
    ]);
}

function delete_contact_social($id)
{
    global $pdo;
    $id = (int) $id;

    $stmt = $pdo->prepare('DELETE FROM contact_socials WHERE id = ?');
    $stmt->execute([(int) $id]);

    $stmt = $pdo->prepare("
    INSERT INTO admin_logs (admin_id, action, target_table, target_id)
    VALUES (?, 'delete', 'contact_socials', ?)
    ");

    $stmt->execute([
    $_SESSION['admin_id'],
    $id
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (admin_login($username, $password)) {
                redirect_to('panel.php');
            }

            set_flash('Login invalido.');
            redirect_to('login.php');
        }

        if ($action === 'logout') {
            admin_logout();
            redirect_to('login.php');
        }

        admin_require_login();
        if ($action === 'add_admin') {
            add_admin($_POST);
            set_flash('Admin adicionado.');
        } elseif ($action === 'edit_admin') {
            edit_admin($_POST);
            if ((int) $_POST['id'] === (int) $_SESSION['admin_id']) {
                $_SESSION['admin_username'] = trim($_POST['username']);
            }
            set_flash('Admin editado.');
        } elseif ($action === 'delete_admin') {
            $deletedAdminId = (int) $_POST['id'];
            delete_admin($_POST['id']);
            if ($deletedAdminId === (int) $_SESSION['admin_id']) {
                admin_logout();
                redirect_to('login.php');
            }
            set_flash('Admin apagado');
        } elseif ($action === 'add_contact') {
            add_contact($_POST);
            set_flash('Contacto adicionado.');
        } elseif ($action === 'edit_contact') {
            edit_contact($_POST);
            set_flash('Contacto editado.');
        } elseif ($action === 'delete_contact') {
            delete_contact($_POST['id']);
            set_flash('Contacto apagado.');
        } elseif ($action === 'add_social') {
            add_social($_POST['name']);
            set_flash('Rede social adicionada.');
        } elseif ($action === 'edit_social') {
            edit_social($_POST);
            set_flash('Rede social editada.');
        } elseif ($action === 'delete_social') {
            delete_social($_POST['id']);
            set_flash('Rede social apagada.');
        } elseif ($action === 'add_contact_social') {
            add_contact_social($_POST);
            set_flash('Rede associada ao contacto.');
        } elseif ($action === 'edit_contact_social') {
            edit_contact_social($_POST);
            set_flash('Associacao editada.');
        } elseif ($action === 'delete_contact_social') {
            delete_contact_social($_POST['id']);
            set_flash('Associacao apagada.');
        }
    } catch (PDOException $e) {
        set_flash('Erro: ' . $e->getMessage());
    }

    redirect_to('panel.php');
}
