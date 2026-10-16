<?php
// Aba "Configurações" — é carregada dentro de painel.php (que já faz o login e define e() e $nome).

date_default_timezone_set("America/Fortaleza");

// TEMPORÁRIO: o feedback é gravado neste arquivo para o administrador ler.
// Quando existir a tabela no banco, troque só o trecho marcado com "BANCO".
$arquivoFeedback = __DIR__ . "/../../database/feedbacks.json";

$tiposFeedback = [
    "sugestao"  => "Sugestão",
    "elogio"    => "Elogio",
    "problema"  => "Problema ou erro",
    "outro"     => "Outro",
];

// opções da tela inicial (como os cards da imagem de referência)
$opcoes = [
    "suporte"  => ["titulo" => "Suporte",  "icone" => "bi-question-circle", "texto" => "Manual rápido com soluções para os problemas mais comuns."],
    "feedback" => ["titulo" => "Feedback", "icone" => "bi-chat-left-text",  "texto" => "Envie sua opinião ou reporte problemas ao administrador."],
];

// manual do sistema: [pergunta, resposta]
$manual = [
    ["Meu QR Code da carteirinha não aparece ou não é lido",
     "Atualize a página e confira sua conexão com a internet. Na portaria, aumente o brilho da tela e limpe o vidro do celular. Se aparecer \"Associação vencida\", regularize sua situação no Financeiro."],
    ["Como gerar um código para meu convidado",
     "Abra \"Meus convidados\", digite o nome do convidado e toque em \"Gerar código\". Envie o código (ou o QR Code) para ele e peça que o apresente na portaria."],
    ["Meu convidado não conseguiu entrar com o código",
     "Cada código vale por 1 dia a partir da geração. Confira no histórico se ele ainda está como \"Válido\" e se foi digitado corretamente. Se estiver expirado, gere um novo código."],
    ["Não consigo copiar o código PIX",
     "Em Financeiro, toque em \"PIX\" e depois em \"Copiar código\". Se o botão não funcionar no seu aparelho, pressione e segure o texto do código, selecione tudo e copie manualmente."],
    ["Não consigo entrar no sistema",
     "Confira se o e-mail e a senha estão corretos (atenção às letras maiúsculas). Persistindo o problema, procure a secretaria do clube."],
    ["A página aparece sem cores ou com defeitos",
     "Isso costuma ser falha de conexão. Atualize a página; se continuar, tente outro navegador ou outra rede."],
];

// qual parte está aberta? (vazio = tela inicial)
$secao = $_GET["secao"] ?? "";
if(!array_key_exists($secao, $opcoes)){
    $secao = "";
}

$erro     = "";
$tipo     = "";
$mensagem = "";
$sucesso  = $_SESSION["msg_feedback"] ?? "";
unset($_SESSION["msg_feedback"]);

// ---------- enviar feedback ----------
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $secao    = "feedback";
    $tipo     = $_POST["tipo"] ?? "";
    $mensagem = trim($_POST["mensagem"] ?? "");

    if(!array_key_exists($tipo, $tiposFeedback)){
        $erro = "Escolha o tipo do feedback.";
    } elseif(mb_strlen($mensagem) < 10){
        $erro = "Escreva uma mensagem com pelo menos 10 caracteres.";
    } else {
        // BANCO: trocar este bloco por um INSERT na tabela de feedbacks
        $lista = is_file($arquivoFeedback) ? (json_decode(file_get_contents($arquivoFeedback), true) ?: []) : [];
        $lista[] = [
            "id"        => count($lista) + 1,
            "nome"      => $nome,
            "email"     => $_SESSION["email"] ?? "",
            "tipo"      => $tipo,
            "mensagem"  => mb_substr($mensagem, 0, 1000),
            "criado_em" => date("Y-m-d H:i:s"),
            "lido"      => false,
        ];
        $salvou = file_put_contents($arquivoFeedback, json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        if($salvou === false){
            $erro = "Não foi possível enviar agora. Tente novamente.";
        } else {
            // redireciona para não reenviar o formulário se a página for atualizada
            $_SESSION["msg_feedback"] = "Feedback enviado ao administrador. Obrigado!";
            header("Location: ?pagina=configuracoes&secao=feedback");
            exit;
        }
    }
}
?>

<?php if($secao === ""): ?>

    <!-- ==================== TELA INICIAL ==================== -->
    <h1 class="h4 mb-1">Configurações</h1>
    <p class="text-secondary mb-4">Obtenha ajuda ou envie sua opinião ao clube.</p>

    <div class="row g-3">
        <?php foreach($opcoes as $chave => $op): ?>
            <div class="col-12 col-md-6">
                <div class="card-painel h-100 d-flex align-items-center gap-3 position-relative">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:56px; height:56px; background: var(--laranja-suave); color: var(--laranja);">
                        <i class="bi <?= $op["icone"]; ?> fs-4"></i>
                    </div>
                    <div>
                        <a href="?pagina=configuracoes&secao=<?= $chave; ?>" class="stretched-link fw-semibold text-decoration-none" style="color: inherit;">
                            <?= e($op["titulo"]); ?>
                        </a>
                        <div class="small text-secondary"><?= e($op["texto"]); ?></div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>

    <!-- ==================== CABEÇALHO DA SEÇÃO ==================== -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="?pagina=configuracoes" class="btn btn-outline-laranja" title="Voltar" aria-label="Voltar">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0"><?= e($opcoes[$secao]["titulo"]); ?></h1>
            <small class="text-secondary"><?= e($opcoes[$secao]["texto"]); ?></small>
        </div>
    </div>

    <?php if($secao === "suporte"): ?>

        <!-- ==================== SUPORTE: MANUAL ==================== -->
        <div class="accordion mb-3" id="manual">
            <?php foreach($manual as $i => $item): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#item<?= $i; ?>">
                            <?= e($item[0]); ?>
                        </button>
                    </h2>
                    <div id="item<?= $i; ?>" class="accordion-collapse collapse" data-bs-parent="#manual">
                        <div class="accordion-body"><?= e($item[1]); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card-painel d-flex flex-column flex-sm-row align-items-sm-center gap-3">
            <div>
                <div class="fw-semibold">Não encontrou a solução?</div>
                <small class="text-secondary">Conte o que aconteceu e o administrador vai analisar.</small>
            </div>
            <a href="?pagina=configuracoes&secao=feedback" class="btn btn-laranja ms-sm-auto">Enviar feedback</a>
        </div>

    <?php else: ?>

        <!-- ==================== FEEDBACK ==================== -->
        <?php if($sucesso): ?>
            <div class="alert alert-success"><?= e($sucesso); ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger"><?= e($erro); ?></div>
        <?php endif; ?>

        <div class="card-painel">
            <form method="POST" action="?pagina=configuracoes&secao=feedback">
                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select form-select-lg" required>
                        <option value="">Selecione...</option>
                        <?php foreach($tiposFeedback as $valor => $rotulo): ?>
                            <option value="<?= $valor; ?>" <?= $tipo === $valor ? "selected" : ""; ?>><?= e($rotulo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="mensagem" class="form-label">Mensagem</label>
                    <textarea id="mensagem" name="mensagem" class="form-control form-control-lg" rows="5"
                              minlength="10" maxlength="1000" required><?= e($mensagem); ?></textarea>
                    <div class="form-text">Máximo de 1000 caracteres.</div>
                </div>

                <button type="submit" class="btn btn-laranja btn-lg w-100">
                    <i class="bi bi-send me-1"></i> Enviar ao administrador
                </button>
            </form>
        </div>

    <?php endif; ?>

<?php endif; ?>