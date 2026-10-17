<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cerberus — Projeto Integrador</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --fundo: #0E0E0E;
      --cartao: #181818;
      --borda: #2A2A2A;
      --texto: #F2EFEA;
      --texto-suave: #A8A39B;
      --laranja: #FF7A1A;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: var(--fundo);
      color: var(--texto);
      font-family: 'Barlow', Arial, sans-serif;
      line-height: 1.6;
    }
    a { color: var(--texto); text-decoration: none; }
    a:hover { color: var(--laranja); }
    .container { max-width: 1120px; margin: 0 auto; padding: 0 32px; }
    h1, h2, .logo { font-family: 'Barlow Condensed', Arial, sans-serif; font-weight: 700; }

    header .container {
      display: flex; flex-wrap: wrap; gap: 16px;
      align-items: center; justify-content: space-between;
      padding-top: 24px; padding-bottom: 24px;
    }
    .logo { font-size: 28px; letter-spacing: 0.12em; color: var(--laranja); }
    nav { display: flex; flex-wrap: wrap; gap: 24px; font-weight: 500; }

    .hero { padding: 96px 0 112px; }
    .etiqueta {
      color: var(--laranja); font-weight: 600; font-size: 14px;
      letter-spacing: 0.16em; text-transform: uppercase;
    }
    .hero h1 { margin: 16px 0 24px; font-size: 96px; line-height: 1; letter-spacing: 0.02em; }
    .hero p { margin: 0 0 40px; max-width: 640px; font-size: 22px; color: #CFCAC2; }
    .botoes { display: flex; flex-wrap: wrap; gap: 16px; }
    .btn { padding: 14px 28px; border-radius: 6px; font-weight: 600; }
    .btn-cheio { background: var(--laranja); color: var(--fundo); }
    .btn-cheio:hover { color: var(--fundo); opacity: 0.9; }
    .btn-contorno { border: 1px solid var(--laranja); }

    section { padding: 64px 0; }
    h2 { margin: 0 0 40px; font-size: 44px; letter-spacing: 0.04em; }

    .grade {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
    }
    .cartao {
      background: var(--cartao); border: 1px solid var(--borda);
      border-radius: 8px; padding: 28px;
    }
    .numero {
      color: var(--laranja); font-family: 'Barlow Condensed', Arial, sans-serif;
      font-weight: 700; font-size: 20px; letter-spacing: 0.1em;
    }
    .cartao h3 { margin: 8px 0; font-size: 22px; }
    .cartao p { margin: 0; color: var(--texto-suave); }

    .passos { gap: 32px; }
    .passo { border-top: 3px solid var(--laranja); padding-top: 16px; }
    .passo h3 { margin: 0 0 8px; font-size: 20px; }
    .passo p { margin: 0; color: var(--texto-suave); }

    .sobre {
      background: var(--cartao); border: 1px solid var(--laranja);
      border-radius: 8px; padding: 40px;
    }
    .sobre h2 { margin-bottom: 16px; }
    .sobre p { margin: 0 0 12px; max-width: 760px; color: #CFCAC2; }
    .sobre .autor { color: var(--texto-suave); margin: 0; }

    footer { margin-top: 48px; border-top: 1px solid var(--borda); }
    footer .container { padding-top: 32px; padding-bottom: 32px; color: var(--texto-suave); font-size: 14px; }

    @media (max-width: 600px) {
      .hero { padding: 64px 0 72px; }
      .hero h1 { font-size: 64px; }
      .hero p { font-size: 18px; }
      h2 { font-size: 34px; }
      .sobre { padding: 28px; }
    }
  </style>
</head>
<body>

  <header>
    <div class="container">
      <div class="logo">CERBERUS</div>
      <nav>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#como-funciona">Como funciona</a>
        <a href="#projeto">O projeto</a>
      </nav>
    </div>
  </header>

  <main>
    <div class="container hero">
      <div class="etiqueta">Projeto Integrador</div>
      <h1>Cerberus</h1>
      <p>Sistema de cadastro e controle de sócios de clubes: dados dos sócios, agendamentos e convites reunidos em um só lugar.</p>
      <div class="botoes">
        <a class="btn btn-cheio" href="#funcionalidades">Ver funcionalidades</a>
        <a class="btn btn-contorno" href="#projeto">Sobre o projeto</a>
      </div>
    </div>

    <section id="funcionalidades">
      <div class="container">
        <h2>Funcionalidades</h2>
        <div class="grade">
          <div class="cartao">
            <div class="numero">01</div>
            <h3>Dados dos sócios</h3>
            <p>Cadastro completo e organizado de cada sócio do clube, sempre acessível para consulta e atualização.</p>
          </div>
          <div class="cartao">
            <div class="numero">02</div>
            <h3>Agendamentos</h3>
            <p>Reserva de horários e espaços do clube, com o controle feito pelo próprio sistema.</p>
          </div>
          <div class="cartao">
            <div class="numero">03</div>
            <h3>Controle de convites</h3>
            <p>Emissão e acompanhamento dos convites dos sócios para seus convidados.</p>
          </div>
          <div class="cartao">
            <div class="numero">04</div>
            <h3>Controle de acesso</h3>
            <p>Gestão de quem pode entrar e utilizar o clube, com visão para administrador e para o sócio.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="como-funciona">
      <div class="container">
        <h2>Como funciona</h2>
        <div class="grade passos">
          <div class="passo">
            <h3>1. Cadastro</h3>
            <p>O clube registra seus sócios e mantém os dados em um único sistema.</p>
          </div>
          <div class="passo">
            <h3>2. Uso do clube</h3>
            <p>Sócios fazem agendamentos e solicitam convites para seus convidados.</p>
          </div>
          <div class="passo">
            <h3>3. Controle</h3>
            <p>A administração acompanha tudo e aprova ou bloqueia o que for necessário.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="projeto">
      <div class="container">
        <div class="sobre">
          <h2>Sobre o projeto integrador</h2>
          <p>O Cerberus foi desenvolvido como projeto integrador com o objetivo de facilitar o cadastro e o controle de sócios de clubes, substituindo anotações soltas por um sistema único e organizado.</p>
          <p class="autor">Autor: [SEU NOME] · Curso: [SEU CURSO] · Instituição: [SUA INSTITUIÇÃO]</p>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container">Cerberus · Projeto Integrador</div>
  </footer>

</body>
</html>
