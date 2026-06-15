<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/queries/get_contacts.php';

// ============================================================
// FUNÇÃO PARA VERIFICAR DUPLICADOS (adaptada do Martim)
// Verifica se já existe um email ou telefone igual na BD
// $id_excluir serve para o editar — ignora o próprio contacto
// ============================================================
function verificarDuplicado($pdo, $campo, $valor, $id_excluir = null) {
    if (empty($valor)) return false;
    $sql = "SELECT COUNT(*) FROM contacts WHERE $campo = :valor";
    $params = [':valor' => $valor];
    if ($id_excluir) {
        $sql .= " AND id != :id";
        $params[':id'] = $id_excluir;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

$erros = [];
$dados_form = []; // Guarda os dados para repreencher o formulário em caso de erro

// ============================================================
// ADICIONAR CONTACTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_adicionar'])) {
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefone  = trim($_POST['telefone'] ?? '');
    $linkedin  = trim($_POST['link_linkedin'] ?? '');
    $instagram = trim($_POST['link_instagram'] ?? '');
    $twitter   = trim($_POST['link_twitter'] ?? '');

    // Guardar dados para repreencher o form se houver erro
    $dados_form = compact('nome', 'email', 'telefone', 'linkedin', 'instagram', 'twitter');

    $phone = preg_replace('/\D/', '', $telefone);

    // Verificar duplicados
    if (verificarDuplicado($pdo, 'email', $email)) {
        $erros[] = 'O email <strong>' . htmlspecialchars($email) . '</strong> já existe na base de dados.';
    }
    if (verificarDuplicado($pdo, 'phone', $phone)) {
        $erros[] = 'O número de telefone <strong>' . htmlspecialchars($telefone) . '</strong> já existe na base de dados.';
    }

    if (empty($erros) && !empty($nome) && !empty($email) && !empty($telefone)) {
        $country_code = '351';

        $image_path = null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $ext        = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $image_path = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], ROOT_PATH . '/assets/fotos/' . $image_path);
        }

        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, country_code, phone, image_path) VALUES (:name, :email, :cc, :phone, :img)");
        $stmt->execute([':name' => $nome, ':email' => $email, ':cc' => $country_code, ':phone' => $phone, ':img' => $image_path]);
        $contactId = $pdo->lastInsertId();

        $redes = ['LinkedIn' => $linkedin, 'Instagram' => $instagram, 'Twitter' => $twitter];
        foreach ($redes as $nome_rede => $link) {
            if (!empty($link)) {
                $stmtS = $pdo->prepare("SELECT id FROM socials WHERE name = :name");
                $stmtS->execute([':name' => $nome_rede]);
                $social = $stmtS->fetch();
                if ($social) {
                    $value = parse_url($link, PHP_URL_PATH) ?: $link;
                    $pdo->prepare("INSERT INTO contact_socials (contact_id, social_id, value) VALUES (:cid, :sid, :val)")
                        ->execute([':cid' => $contactId, ':sid' => $social['id'], ':val' => $value]);
                }
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?sucesso=1');
        exit;
    }
}

// ============================================================
// APAGAR CONTACTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_apagar'])) {
    $id = (int)($_POST['id_contacto'] ?? 0);
    if ($id > 0) {
        $stmtFoto = $pdo->prepare("SELECT image_path FROM contacts WHERE id = :id");
        $stmtFoto->execute([':id' => $id]);
        $foto = $stmtFoto->fetchColumn();
        if ($foto && file_exists(ROOT_PATH . '/assets/fotos/' . $foto)) {
            unlink(ROOT_PATH . '/assets/fotos/' . $foto);
        }
        $pdo->prepare("DELETE FROM contacts WHERE id = :id")->execute([':id' => $id]);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?sucesso=2');
        exit;
    }
}

// ============================================================
// EDITAR CONTACTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_editar'])) {
    $id        = (int)($_POST['id_contacto'] ?? 0);
    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefone  = trim($_POST['telefone'] ?? '');
    $linkedin  = trim($_POST['link_linkedin'] ?? '');
    $instagram = trim($_POST['link_instagram'] ?? '');
    $twitter   = trim($_POST['link_twitter'] ?? '');

    if ($id > 0 && !empty($nome) && !empty($email) && !empty($telefone)) {
        $phone = preg_replace('/\D/', '', $telefone);

        $image_path = $_POST['foto_atual'] ?? null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            if ($image_path && file_exists(ROOT_PATH . '/assets/fotos/' . $image_path)) {
                unlink(ROOT_PATH . '/assets/fotos/' . $image_path);
            }
            $ext        = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $image_path = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], ROOT_PATH . '/assets/fotos/' . $image_path);
        }

        $pdo->prepare("UPDATE contacts SET name=:name, email=:email, phone=:phone, image_path=:img WHERE id=:id")
            ->execute([':name' => $nome, ':email' => $email, ':phone' => $phone, ':img' => $image_path, ':id' => $id]);

        $pdo->prepare("DELETE FROM contact_socials WHERE contact_id = :id")->execute([':id' => $id]);

        $redes = ['LinkedIn' => $linkedin, 'Instagram' => $instagram, 'Twitter' => $twitter];
        foreach ($redes as $nome_rede => $link) {
            if (!empty($link)) {
                $stmtS = $pdo->prepare("SELECT id FROM socials WHERE name = :name");
                $stmtS->execute([':name' => $nome_rede]);
                $social = $stmtS->fetch();
                if ($social) {
                    $value = parse_url($link, PHP_URL_PATH) ?: $link;
                    $pdo->prepare("INSERT INTO contact_socials (contact_id, social_id, value) VALUES (:cid, :sid, :val)")
                        ->execute([':cid' => $id, ':sid' => $social['id'], ':val' => $value]);
                }
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?sucesso=3');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contactos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">

    <h1 class="titulo-contactos">Contactos</h1>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success text-center alerta-sucesso" style="border-radius:10px;">
            <?php
                $msgs = ['1' => 'Contacto adicionado com sucesso!', '2' => 'Contacto apagado com sucesso!', '3' => 'Contacto editado com sucesso!'];
                echo $msgs[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
            ?>
        </div>
    <?php endif; ?>

    <!-- Barra de Pesquisa + Botões -->
    <div class="search-and-view">
        <div class="search-wrapper">
            <span class="search-icon-left" id="btn-focus" title="Pesquisar">
                <i class="fa fa-search"></i>
            </span>
            <input type="text" id="search-input" class="form-control" placeholder="Pesquisar contacto..." autocomplete="off">
            <span class="search-icon-right" id="btn-reset" title="Limpar pesquisa" style="display:none;">
                <i class="fa fa-times"></i>
            </span>
        </div>

        <div class="view-btns">
            <button id="btn-grelha" class="btn-vista active" title="Vista em grelha"><i class="fa fa-th"></i></button>
            <button id="btn-lista" class="btn-vista" title="Vista em lista"><i class="fa fa-list"></i></button>
            <button class="btn-vista btn-adicionar" data-toggle="modal" data-target="#modalNovoContacto" title="Adicionar Contacto">
                <i class="fa fa-user-plus"></i>
            </button>
            <button class="btn-vista btn-gerir" data-toggle="modal" data-target="#modalGerirContactos" title="Gerir Contactos">
                <i class="fa fa-sliders"></i>
            </button>
        </div>
    </div>

    <!-- Grelha de Contactos -->
    <div class="row contactos-grid vista-grelha" id="contactos-grid">
        <?php if (empty($contacts)): ?>
            <div class="col-xs-12">
                <p class="text-center text-muted">Nenhum contacto encontrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($contacts as $c): ?>
                <div class="col-item col-sm-6 col-md-4"
                     data-nome="<?php echo strtolower(htmlspecialchars($c['name'])); ?>"
                     data-email="<?php echo strtolower(htmlspecialchars($c['email'])); ?>"
                     data-tel="<?php echo strtolower(htmlspecialchars($c['phone'])); ?>">
                    <div class="contact-card">
                        <div class="foto-wrapper">
                            <?php if (!empty($c['image_path']) && file_exists(ROOT_PATH . "/assets/fotos/" . $c['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars(BASE_URL . "assets/fotos/" . $c['image_path']); ?>" alt="Foto de <?php echo htmlspecialchars($c['name']); ?>">
                            <?php else: ?>
                                <span class="glyphicon glyphicon-user foto-placeholder"></span>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <p class="info-nome"><?php echo htmlspecialchars($c['name']); ?></p>
                            <p class="info-email"><?php echo htmlspecialchars($c['email']); ?></p>
                            <p class="info-tel"><?php echo htmlspecialchars('+' . $c['country_code'] . ' ' . $c['phone']); ?></p>
                            <div class="linkedin-icons">
                                <?php foreach ($c['socials'] as $social): ?>
                                    <a href="<?php echo htmlspecialchars("https://www." . $social['name'] . ".com" . $social['value']); ?>"
                                       target="_blank" title="<?php echo htmlspecialchars($social['name']); ?>">
                                        <i class="fa fa-<?php echo htmlspecialchars(strtolower($social['name'])); ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="sem-resultados" style="display:none;">
        <p class="text-center text-muted">Nenhum contacto encontrado.</p>
    </div>

</div>

<!-- MODAL ADICIONAR -->
<div class="modal fade" id="modalNovoContacto" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-contacto">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Adicionar Novo Contacto</h4>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger" style="border-radius:8px;">
                            <?php foreach ($erros as $erro): ?>
                                <p style="margin:0;"><?php echo $erro; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: João Silva"
                               value="<?php echo htmlspecialchars($dados_form['nome'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required placeholder="email@exemplo.pt"
                                       value="<?php echo htmlspecialchars($dados_form['email'] ?? ''); ?>"
                                       <?php if (!empty($erros) && verificarDuplicado($pdo, 'email', $dados_form['email'] ?? '')): ?>
                                           style="border-color:#d9534f;"
                                       <?php endif; ?>>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label>Telefone *</label>
                                <input type="tel" name="telefone" class="form-control" required placeholder="933000000"
                                       value="<?php echo htmlspecialchars($dados_form['telefone'] ?? ''); ?>"
                                       <?php if (!empty($erros) && verificarDuplicado($pdo, 'phone', preg_replace('/\D/', '', $dados_form['telefone'] ?? ''))): ?>
                                           style="border-color:#d9534f;"
                                       <?php endif; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Foto de Perfil (Opcional)</label>
                        <input type="file" name="foto_perfil" class="form-control" accept="image/*">
                    </div>
                    <hr>
                    <p class="text-muted"><small><strong>Redes Sociais (Opcional)</strong></small></p>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-linkedin"></i></span>
                        <input type="url" name="link_linkedin" class="form-control" placeholder="https://linkedin.com/in/..."
                               value="<?php echo htmlspecialchars($dados_form['linkedin'] ?? ''); ?>">
                    </div>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-instagram"></i></span>
                        <input type="url" name="link_instagram" class="form-control" placeholder="https://instagram.com/..."
                               value="<?php echo htmlspecialchars($dados_form['instagram'] ?? ''); ?>">
                    </div>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-twitter"></i></span>
                        <input type="url" name="link_twitter" class="form-control" placeholder="https://twitter.com/..."
                               value="<?php echo htmlspecialchars($dados_form['twitter'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="acao_adicionar" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL GERIR CONTACTOS -->
<div class="modal fade" id="modalGerirContactos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content modal-contacto">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-sliders"></i> Gerir Contactos</h4>
            </div>
            <div class="modal-body" style="padding:0;">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table table-hover" style="margin:0;">
                        <thead style="position:sticky;top:0;background:#e2e2e2;z-index:1;">
                            <tr>
                                <th style="width:60px;">Foto</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th style="width:120px;" class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $c): ?>
                                <?php
                                    $linkedin_val = $instagram_val = $twitter_val = '';
                                    foreach ($c['socials'] as $s) {
                                        $base = "https://www." . strtolower($s['name']) . ".com";
                                        if (strtolower($s['name']) === 'linkedin')  $linkedin_val  = $base . $s['value'];
                                        if (strtolower($s['name']) === 'instagram') $instagram_val = $base . $s['value'];
                                        if (strtolower($s['name']) === 'twitter')   $twitter_val   = $base . $s['value'];
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($c['image_path']) && file_exists(ROOT_PATH . "/assets/fotos/" . $c['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars(BASE_URL . "assets/fotos/" . $c['image_path']); ?>"
                                                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <span class="glyphicon glyphicon-user" style="font-size:24px;color:#bbb;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="vertical-align:middle;"><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                    <td style="vertical-align:middle;color:#666;font-size:13px;"><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td style="vertical-align:middle;" class="text-right">
                                        <button class="btn btn-xs btn-primary"
                                            data-toggle="modal" data-target="#modalEditarContacto"
                                            data-id="<?php echo $c['id']; ?>"
                                            data-nome="<?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($c['email'], ENT_QUOTES); ?>"
                                            data-telefone="<?php echo htmlspecialchars($c['phone'], ENT_QUOTES); ?>"
                                            data-foto="<?php echo htmlspecialchars($c['image_path'] ?? '', ENT_QUOTES); ?>"
                                            data-linkedin="<?php echo htmlspecialchars($linkedin_val, ENT_QUOTES); ?>"
                                            data-instagram="<?php echo htmlspecialchars($instagram_val, ENT_QUOTES); ?>"
                                            data-twitter="<?php echo htmlspecialchars($twitter_val, ENT_QUOTES); ?>"
                                            title="Editar">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <form action="" method="POST" style="display:inline;"
                                              onsubmit="return confirm('Apagar <?php echo htmlspecialchars($c['name']); ?>?');">
                                            <input type="hidden" name="id_contacto" value="<?php echo $c['id']; ?>">
                                            <button type="submit" name="acao_apagar" class="btn btn-xs btn-danger" title="Apagar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditarContacto" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-contacto">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Editar Contacto</h4>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_contacto" id="edit_id">
                <input type="hidden" name="foto_atual" id="edit_foto_atual">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label>Telefone *</label>
                                <input type="tel" name="telefone" id="edit_telefone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alterar Foto (Opcional)</label>
                        <input type="file" name="foto_perfil" class="form-control" accept="image/*">
                    </div>
                    <hr>
                    <p class="text-muted"><small><strong>Redes Sociais (Opcional)</strong></small></p>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-linkedin"></i></span>
                        <input type="url" name="link_linkedin" id="edit_linkedin" class="form-control" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-instagram"></i></span>
                        <input type="url" name="link_instagram" id="edit_instagram" class="form-control" placeholder="https://instagram.com/...">
                    </div>
                    <div class="input-group" style="margin-bottom:8px;">
                        <span class="input-group-addon"><i class="fa fa-twitter"></i></span>
                        <input type="url" name="link_twitter" id="edit_twitter" class="form-control" placeholder="https://twitter.com/...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="acao_editar" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Fuse.js - biblioteca de pesquisa inteligente (igual ao Martim) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fuse.js/6.6.2/fuse.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<script>
    var btnGrelha     = document.getElementById('btn-grelha');
    var btnLista      = document.getElementById('btn-lista');
    var btnReset      = document.getElementById('btn-reset');
    var btnFocus      = document.getElementById('btn-focus');
    var searchInput   = document.getElementById('search-input');
    var grid          = document.getElementById('contactos-grid');
    var semResultados = document.getElementById('sem-resultados');

    // Mensagem de sucesso desaparece após 3 segundos
    var alerta = document.querySelector('.alerta-sucesso');
    if (alerta) {
        setTimeout(function() {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(function() { alerta.style.display = 'none'; }, 500);
        }, 3000);
    }

    // Abrir modal de adicionar se houver erros (para mostrar os erros)
    <?php if (!empty($erros)): ?>
        $(document).ready(function() {
            $('#modalNovoContacto').modal('show');
        });
    <?php endif; ?>

    // ============================================================
    // FUSE.JS - Pesquisa inteligente (adaptado do Martim)
    // Recolhe os dados de cada card para a lista de pesquisa
    // ============================================================
    // Fuse.js - espera a página carregar completamente
    
    document.addEventListener('DOMContentLoaded', function() {
    var cardElements = Array.from(document.querySelectorAll('.col-item'));

    var listaParaPesquisa = cardElements.map(function(card) {
        return {
            nome:             card.getAttribute('data-nome') || '',
            email:            card.getAttribute('data-email') || '',
            tel:              card.getAttribute('data-tel') || '',
            elementoOriginal: card
        };
    });

    var fuse = new Fuse(listaParaPesquisa, {
        keys: ['nome', 'email', 'tel'],
        threshold: 0.3,
        distance: 100
    });

    searchInput.addEventListener('input', function () {
        var termo = this.value.trim();
        btnReset.style.display = termo.length > 0 ? 'block' : 'none';

        if (termo === '') {
            listaParaPesquisa.forEach(function(item) {
                item.elementoOriginal.style.display = '';
            });
            semResultados.style.display = 'none';
            return;
        }

        var resultados = fuse.search(termo).map(function(res) { return res.item; });

        var visiveis = 0;
        listaParaPesquisa.forEach(function(item) {
            if (resultados.includes(item)) {
                item.elementoOriginal.style.display = '';
                visiveis++;
            } else {
                item.elementoOriginal.style.display = 'none';
            }
        });

        semResultados.style.display = visiveis === 0 ? 'block' : 'none';
    });
});

    btnReset.addEventListener('click', function () {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    });

    function aplicarGrelha() {
        grid.querySelectorAll('.col-item').forEach(function(i) { i.style.width = ''; i.style.float = ''; });
        grid.querySelectorAll('.contact-card').forEach(function(c) { c.style.width = ''; c.style.maxWidth = ''; c.style.minHeight = ''; });
        grid.querySelectorAll('.foto-wrapper').forEach(function(f) { f.style.width = ''; f.style.height = ''; });
        grid.querySelectorAll('.info-tel').forEach(function(t) { t.style.display = ''; });
        grid.classList.remove('vista-lista'); grid.classList.add('vista-grelha');
        btnGrelha.classList.add('active'); btnLista.classList.remove('active');
        localStorage.setItem('vista', 'grelha');
    }

    btnGrelha.addEventListener('click', aplicarGrelha);

    btnLista.addEventListener('click', function () {
        grid.querySelectorAll('.col-item').forEach(function(i) { i.style.width = '100%'; i.style.float = 'none'; });
        grid.querySelectorAll('.contact-card').forEach(function(c) { c.style.width = '100%'; c.style.maxWidth = '100%'; c.style.minHeight = '90px'; });
        grid.querySelectorAll('.foto-wrapper').forEach(function(f) { f.style.width = '60px'; f.style.height = '68px'; });
        grid.querySelectorAll('.info-tel').forEach(function(t) { t.style.display = 'none'; });
        grid.classList.remove('vista-grelha'); grid.classList.add('vista-lista');
        btnLista.classList.add('active'); btnGrelha.classList.remove('active');
        localStorage.setItem('vista', 'lista');
    });

    $('#modalEditarContacto').on('show.bs.modal', function(e) {
        var btn = $(e.relatedTarget);
        document.getElementById('edit_id').value         = btn.data('id');
        document.getElementById('edit_nome').value       = btn.data('nome');
        document.getElementById('edit_email').value      = btn.data('email');
        document.getElementById('edit_telefone').value   = btn.data('telefone');
        document.getElementById('edit_foto_atual').value = btn.data('foto');
        document.getElementById('edit_linkedin').value   = btn.data('linkedin');
        document.getElementById('edit_instagram').value  = btn.data('instagram');
        document.getElementById('edit_twitter').value    = btn.data('twitter');
    });

    if (localStorage.getItem('vista') === 'lista') { btnLista.click(); }
</script>

</body>
</html>
