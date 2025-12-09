<?php
?><!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sportello Pagamenti Cittadini</title>
    <style>
      :root {
        color-scheme: light;
        font-family: "Titillium Web", "Segoe UI", system-ui, sans-serif;
      }
      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #eef2ff 0%, #f8fbff 100%);
        color: #0b3155;
      }
      .card {
        width: min(420px, 86vw);
        background: #fff;
        border-radius: 18px;
        padding: 2.5rem;
        box-shadow: 0 25px 60px rgba(22, 58, 99, 0.15);
        text-align: center;
      }
      h1 {
        font-size: clamp(1.7rem, 4vw, 2.2rem);
        margin-bottom: 0.75rem;
      }
      p {
        margin: 0 auto 1.5rem;
        line-height: 1.6;
        color: #203d63;
      }
      .cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 1.75rem;
        border-radius: 999px;
        border: none;
        color: #fff;
        background: #0053a6;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .cta:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 30px rgba(0, 83, 166, 0.3);
      }
      .tag {
        display: inline-flex;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        background: rgba(0, 83, 166, 0.1);
        color: #0053a6;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
      }
    </style>
  </head>
  <body>
    <main class="card">
      <div class="tag">Anteprima Frontoffice</div>
      <h1>Benvenuto nello sportello cittadini</h1>
      <p>
        Questa è una pagina dimostrativa. Qui troverai il percorso guidato per consultare le tasse
        e perfezionare i pagamenti PagoPA con la stessa identità grafica del backoffice.
      </p>
      <a class="cta" href="#">
        Inizia ora
        <span aria-hidden="true">→</span>
      </a>
    </main>
  </body>
</html>
