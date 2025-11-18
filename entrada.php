<?php
/**
 * Página de Entrada - Clínica Mais Saúde
 * Sistema em PHP 5.4 - 100% em Português Brasileiro
 */

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Variáveis PHP para o JavaScript
$nomeClinica = 'Clínica Mais Saúde';
$mensagemBoasVindas = 'Seja bem-vindo(a) ao nosso sistema de autoatendimento';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bem-vindo | <?php echo htmlspecialchars($nomeClinica); ?></title>
  <link rel="stylesheet" href="./assets/css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
  <style>
    body {
      background-color: var(--primary-dark);
      color: #fff;
      font-family: 'Inter', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
      margin: 0;
      text-align: center;
    }

    .intro-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      max-width: 800px;
      padding: 2rem;
      opacity: 0;
    }

    .logo-container {
      background: white;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      padding: 20px;
      box-sizing: border-box;
    }

    .intro-container img {
      width: 100%;
      height: auto;
      object-fit: contain;
    }

    .intro-logo {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      letter-spacing: 1px;
    }

    .intro-subtitle {
      font-size: 1.3rem;
      font-weight: 400;
      opacity: 0.9;
      margin-bottom: 2.5rem;
    }

    .pulse-circle {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      position: relative;
      box-shadow: 0 0 0 rgba(255,255,255,0.3);
    }

    .pulse-circle::before {
      content: "";
      position: absolute;
      border: 3px solid rgba(255, 255, 255, 0.5);
      width: 100%;
      height: 100%;
      border-radius: 50%;
      animation: pulse 1.8s infinite;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
        opacity: 0.8;
      }
      70% {
        transform: scale(1.4);
        opacity: 0;
      }
      100% {
        transform: scale(1);
        opacity: 0;
      }
    }

    .pulse-circle i {
      font-size: 3rem;
      color: #fff;
      z-index: 2;
    }

    .click-text {
      font-size: 1.1rem;
      margin-top: 1.5rem;
      opacity: 0.85;
    }

    .footer-brand {
      position: absolute;
      bottom: 30px;
      font-size: 0.95rem;
      opacity: 0.7;
      letter-spacing: 0.5px;
    }

    @media (max-width: 600px) {
      .intro-logo {
        font-size: 2.2rem;
      }
      .intro-subtitle {
        font-size: 1rem;
      }
      .pulse-circle {
        width: 120px;
        height: 120px;
      }
      .pulse-circle i {
        font-size: 2.2rem;
      }
    }
  </style>
</head>
<body>
  <div class="intro-container" id="intro">
    <div class="logo-container">
      <img src="./assets/img/brand-mais-saude.svg" alt="logo">
    </div>
    <div class="intro-logo"><?php echo htmlspecialchars($nomeClinica); ?></div>
    <div class="intro-subtitle"><?php echo htmlspecialchars($mensagemBoasVindas); ?></div>
    <div class="pulse-circle" id="start-btn">
      <i class="fas fa-hand-pointer"></i>
    </div>
    <div class="click-text">Toque na tela para começar</div>
  </div>
  <div class="footer-brand">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($nomeClinica); ?></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
  <script>
    // Variáveis PHP disponíveis no JavaScript
    var nomeClinica = '<?php echo addslashes($nomeClinica); ?>';
    var mensagemBoasVindas = '<?php echo addslashes($mensagemBoasVindas); ?>';

    // Fade-in inicial
    anime({
      targets: '.intro-container',
      opacity: [0, 1],
      translateY: [30, 0],
      duration: 1500,
      easing: 'easeOutExpo'
    });

    // Movimento flutuante no ícone
    anime({
      targets: '.pulse-circle i',
      translateY: [0, -10],
      direction: 'alternate',
      loop: true,
      easing: 'easeInOutSine',
      duration: 1000
    });

    // Ao clicar, faz animação de saída e redireciona
    const startBtn = document.getElementById('start-btn');
    startBtn.addEventListener('click', function() {
      anime({
        targets: '#intro',
        opacity: [1, 0],
        scale: [1, 0.9],
        duration: 800,
        easing: 'easeInOutQuad',
        complete: function() {
          window.location.href = 'index.php';
        }
      });
    });

    // Também permite clicar em qualquer parte da tela
    document.body.addEventListener('click', function() {
      startBtn.click();
    });
  </script>
</body>
</html>

