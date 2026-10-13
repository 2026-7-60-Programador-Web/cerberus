<?php
$perfil = [
    "nome"     => $nome,
    "email"    => $_SESSION["email"],
    "telefone" => "(85) 99999-0000",
    "cpf"      => "000.000.000-00",
];

$dependentes = [
    ["nome" => "Ana Souza",   "parentesco" => "Filha"],
    ["nome" => "Carlos Souza", "parentesco" => "Cônjuge"],
];
?>

<h1 class="h4 mb-3">Meu perfil</h1>

<div class="card-painel">
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aba-dados" type="button">Dados pessoais</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-foto" type="button">Foto</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-senha" type="button">Alterar senha</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-dependentes" type="button">Dependentes</button></li>
    </ul>

    <div class="tab-content">

        <!-- DADOS PESSOAIS -->
        <div class="tab-pane fade show active" id="aba-dados">
            <form action="#" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" name="nome" value="<?= e($perfil['nome']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="email" value="<?= e($perfil['email']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="telefone" value="<?= e($perfil['telefone']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">CPF</label>
                    <input type="text" class="form-control" value="<?= e($perfil['cpf']); ?>" disabled>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-laranja">Salvar alterações</button>
                </div>
            </form>
        </div>

        <!-- FOTO -->
        <div class="tab-pane fade" id="aba-foto">
            <form action="#" method="POST" enctype="multipart/form-data">
                <img id="previaFoto" src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Foto de perfil" class="foto-perfil mb-3 d-block">
                <input type="file" class="form-control mb-3" id="inputFoto" name="foto" accept="image/*">
                <button type="submit" class="btn btn-laranja">Enviar foto</button>
            </form>
        </div>

        <!-- ALTERAR SENHA -->
        <div class="tab-pane fade" id="aba-senha">
            <form action="#" method="POST" style="max-width: 400px;">
                <div class="mb-3">
                    <label class="form-label">Senha atual</label>
                    <input type="password" class="form-control" name="senha_atual" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nova senha</label>
                    <input type="password" class="form-control" name="nova_senha" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar nova senha</label>
                    <input type="password" class="form-control" name="confirmar_senha" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-laranja">Alterar senha</button>
            </form>
        </div>

        <!-- DEPENDENTES -->
        <div class="tab-pane fade" id="aba-dependentes">
            <div class="table-responsive mb-4">
                <table class="table align-middle">
                    <thead><tr><th>Nome</th><th>Parentesco</th></tr></thead>
                    <tbody>
                        <?php foreach($dependentes as $dep): ?>
                            <tr>
                                <td><?= e($dep["nome"]); ?></td>
                                <td><?= e($dep["parentesco"]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2 class="h6">Adicionar dependente</h2>
            <form action="#" method="POST" class="row g-2">
                <div class="col-md-5"><input type="text" class="form-control" name="nome" placeholder="Nome" required></div>
                <div class="col-md-4"><input type="text" class="form-control" name="parentesco" placeholder="Parentesco" required></div>
                <div class="col-md-3"><button type="submit" class="btn btn-outline-laranja w-100">Adicionar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    // mostra a prévia da foto escolhida antes de enviar
    document.getElementById('inputFoto').addEventListener('change', (e) => {
        const arquivo = e.target.files[0];
        if (arquivo) {
            document.getElementById('previaFoto').src = URL.createObjectURL(arquivo);
        }
    });
</script>