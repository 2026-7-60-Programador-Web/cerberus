<?php
// cada card: [rótulo, valor, página para onde o link leva]
$cards = [
    ["Associação",             "Ativa",     "carteirinha"],
    ["Mensalidade em aberto",  "R$ 350,00", "financeiro"],
    ["Convidados usados",      "3 de 5",    "meus-convidados"],
   
];
?>

<h1 class="h4 mb-4">Olá, <?= e($nome); ?>!</h1>

<div class="row g-3">
    <?php foreach($cards as $card): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card-painel h-100 position-relative">
                <small class="text-secondary"><?= e($card[0]); ?></small>
                <div class="indicador-numero"><?= e($card[1]); ?></div>
                <a href="?pagina=<?= $card[2]; ?>" class="stretched-link small">Ver detalhes</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>