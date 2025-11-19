<?php

/**
 * Totem Auto Atendimento - Clínica Mais Saúde
 * Sistema integrado com API Smile Saúde
 */
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// Funções auxiliares úteis
function formatarCPF($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}
function validarCPF($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}
// Data e hora para exibição inicial
function obterDataAtual()
{
    return date('d/m/Y');
}
function obterHoraAtual()
{
    return date('H:i:s');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Totem Auto Atendimento - Clínica Mais Saúde</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>

<body>
    <div class="app">
        <header class="header">
            <div class="logo">
                <h1>Clínica Mais Saúde</h1>
                <p>Seja bem-vindo(a) ao nosso sistema de autoatendimento!</p>
            </div>
            <div class="time">
                <!-- Relógio analógico -->
                <div class="clock">
                    <div class="hand hour" id="hour-hand"></div>
                    <div class="hand minute" id="minute-hand"></div>
                    <div class="hand second" id="second-hand"></div>
                    <div class="center-dot"></div>
                </div>
                <div class="time-content">
                    <span id="current-time"><?php echo obterHoraAtual(); ?></span>
                    <span id="current-date"><?php echo obterDataAtual(); ?></span>
                </div>
            </div>
        </header>

        <main class="main-content">
            <!-- Welcome Screen -->
            <div id="welcome-screen" class="screen active">
                <div class="welcome-content">
                    <h2>Como podemos ajudá-lo hoje?</h2>
                    <p>Selecione uma das opções abaixo para continuar</p>

                    <div class="options-grid">
                        <button class="option-btn" data-option="Consulta">
                            <div class="ticket-icon-container">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <span>Check-in de Consulta</span>
                        </button>

                        <div class="option-btn-inativo">
                            <button class="option-btn inativo-card" data-option="Exame">
                                <div class="ticket-icon-container">
                                    <i class="fas fa-microscope"></i>
                                </div>
                                <span>Check-in de Exame</span>
                            </button>
                            <div class="em-breve-text">EM BREVE</div>
                        </div>

                        <div class="option-btn-inativo">
                            <button class="option-btn inativo-card" data-option="Terapia">
                                <div class="ticket-icon-container">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <span>Check-in de Terapia</span>
                            </button>
                            <div class="em-breve-text">EM BREVE</div>
                        </div>

                        <div class="option-btn-inativo">
                            <button class="option-btn inativo-card" data-option="Pronto atendimento">
                                <div class="ticket-icon-container">
                                    <i class="fa-solid fa-hospital"></i>
                                </div>
                                <span>Pronto Atendimento</span>
                            </button>
                            <div class="em-breve-text">EM BREVE</div>
                        </div>

                        <div class="option-btn-inativo">
                            <button class="option-btn inativo-card" data-option="senha">
                                <div class="ticket-icon-container">
                                    <i class="fas fa-ticket"></i>
                                </div>
                                <span>Retirar Senha Digital</span>
                            </button>
                            <div class="em-breve-text">EM BREVE</div>
                        </div>


                    </div>
                </div>
            </div>

            <!-- Identification Screen -->
            <div id="identify-screen" class="screen">
                <div class="identify-content">
                    <button class="back-btn">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>

                    <div class="identify-header">
                        <h2>Identificação</h2>
                        <p class="service-type">Serviço: <span id="service-type">Consulta</span></p>
                    </div>

                    <form class="identify-form" id="identify-form">
                        <label for="cpf-value" class="sr-only">CPF</label>
                        <div class="cpf-container" aria-label="Campo CPF">
                            <div class="cpf-display" id="cpf-display" aria-hidden="true">
                                <!-- 11 slots (0..10) -->
                                <span class="cpf-slot" data-index="0"></span>
                                <span class="cpf-slot" data-index="1"></span>
                                <span class="cpf-slot" data-index="2"></span>
                                <span class="cpf-sep">.</span>
                                <span class="cpf-slot" data-index="3"></span>
                                <span class="cpf-slot" data-index="4"></span>
                                <span class="cpf-slot" data-index="5"></span>
                                <span class="cpf-sep">.</span>
                                <span class="cpf-slot" data-index="6"></span>
                                <span class="cpf-slot" data-index="7"></span>
                                <span class="cpf-slot" data-index="8"></span>
                                <span class="cpf-sep">-</span>
                                <span class="cpf-slot" data-index="9"></span>
                                <span class="cpf-slot" data-index="10"></span>
                            </div>
                            <input type="hidden" id="cpf-value" name="cpf" value="">
                        </div>

                        <div class="keyboard-wrapper">
                            <div class="numeric-keyboard">
                                <div class="keyboard-row">
                                    <button type="button" class="key" data-key="1">1</button>
                                    <button type="button" class="key" data-key="2">2</button>
                                    <button type="button" class="key" data-key="3">3</button>
                                </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key" data-key="4">4</button>
                                    <button type="button" class="key" data-key="5">5</button>
                                    <button type="button" class="key" data-key="6">6</button>
                                </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key" data-key="7">7</button>
                                    <button type="button" class="key" data-key="8">8</button>
                                    <button type="button" class="key" data-key="9">9</button>
                                </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key key-wide" data-key="backspace">
                                        <i class="fas fa-delete-left"></i>
                                    </button>
                                    <button type="button" class="key" data-key="0">0</button>
                                    <button type="button" class="key key-clear" data-key="clear" title="Limpar">
                                        <i class="fas fa-trash"></i> Limpar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button id="identify-btn" type="button" class="primary-btn">Continuar</button>
                    </form>
                </div>
            </div>

            <!-- Cards laterais da tela de identificação -->
            <!-- <div class="side-card left-card" id="card-app">
                <div class="card-content">
                    <i class="fas fa-qrcode"></i>
                    <div>
                        <h3>Baixe o app <strong>Smile Saúde</strong></h3>
                        <p>Acompanhe seus atendimentos pelo celular</p>
                        <img src="assets/qr-smile.png" alt="QR Code Smile Saúde" class="qr-code">
                    </div>
                </div>
            </div> -->

            <div class="side-card right-card" id="card-info">
                <div class="card-content">
                    <i class="fas fa-heartbeat"></i>
                    <div>
                        <h3>Saúde ao seu alcance</h3>
                        <p id="dynamic-info">Verifique seus resultados com rapidez e segurança.</p>
                    </div>
                </div>
            </div>

            <!-- Tela de Agendamentos -->
            <div id="appointments-screen" class="screen">
                <div class="appointments-content">
                    <button class="back-btn">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>

                    <h2>Seus Agendamentos</h2>
                    <p class="patient-name">
                        Bem-vindo(a), <span id="patient-name"></span>
                    </p>

                    <!-- Loader enquanto a API carrega -->
                    <div id="loading-inline" class="loading-inline" style="display: flex;">
                        <div class="loading-card">
                            <div class="spinner"></div>
                            <p>Carregando agendamentos...</p>
                        </div>
                    </div>

                    <div id="appointments-list" style="display: none;"></div>
                </div> <!-- fecha appointments-content -->
            </div> <!-- fecha appointments-screen -->

            <!-- Digital Ticket Options Screen -->
            <div id="ticket-options-screen" class="screen">
                <div class="ticket-options-content">
                    <button class="back-btn">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>

                    <h2>Selecione o tipo de atendimento</h2>
                    <p>Escolha a opção desejada para retirar sua senha</p>

                    <div class="ticket-options-grid">
                        <button class="ticket-option-btn" data-ticket-type="atendimento-geral">
                            <div class="ticket-icon-container">
                                <i class="fas fa-headset"></i>
                            </div>
                            <span>Atendimento Geral</span>
                        </button>

                        <button class="ticket-option-btn" data-ticket-type="agendar-consulta">
                            <div class="ticket-icon-container">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <span>Agendar Consulta</span>
                        </button>

                        <button class="ticket-option-btn" data-ticket-type="resultados">
                            <div class="ticket-icon-container">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <span>Resultados de Exames</span>
                        </button>

                        <button class="ticket-option-btn" data-ticket-type="informacoes">
                            <div class="ticket-icon-container">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <span>Informações e Suporte</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Confirmation Screen -->
            <div id="confirmation-screen" class="screen">
                <div class="confirmation-content">
                    <button class="back-btn">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>

                    <div class="confirmation-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <h2>Agendamento confirmado com sucesso</h2>

                    <div class="appointment-details">
                        <p><i class="fas fa-user detail-icon"></i> <strong>Paciente:</strong> <span id="confirm-patient-name"></span></p>
                        <p><i class="fas fa-stethoscope detail-icon"></i> <strong>Serviço:</strong> <span id="confirm-service-type"></span></p>
                        <p><i class="fas fa-user-md detail-icon"></i> <strong id="label-professional">Profissional:</strong> <span id="confirm-professional"></span></p>
                        <p><i class="fas fa-heartbeat detail-icon"></i> <strong>Especialidade:</strong> <span id="confirm-specialty"></span></p>
                        <p><i class="fas fa-clock detail-icon"></i> <strong>Horário:</strong> <span id="confirm-time"></span></p>
                        <!-- <p><i class="fas fa-door-open detail-icon"></i> <strong>Sala:</strong> <span id="confirm-room"></span></p> -->
                        <!-- <div id="confirmation-status" class="confirmation-status"></div> -->
                    </div>

                    <button id="confirm-btn" class="primary-btn">OK</button>
                </div>
            </div>

            <!-- Queue Ticket Screen -->
            <div id="queue-screen" class="screen">
                <div class="queue-content">
                    <button class="back-btn">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>

                    <div class="ticket-body">
                        <div class="ticket-number">
                            <span>Paciente</span>
                            <span id="ticket-patient"></span>
                        </div>

                        <div class="ticket-type">
                            <span>Atendimento Geral</span>
                        </div>

                        <div class="ticket-info">
                            <div class="info-item">
                                <span>Data</span>
                                <strong id="ticket-date"><?php echo obterDataAtual(); ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Hora</span>
                                <strong id="ticket-time"><?php echo obterHoraAtual(); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-footer">
                        <p>Agradecemos a preferência!</p>
                        <p>Por favor, aguarde ser chamado(a) no painel de atendimento.</p>
                    </div>

                    <div class="ticket-actions">
                        <button class="primary-btn" id="new-ticket-btn">
                            <i class="fas fa-redo"></i> Novo Atendimento
                        </button>
                    </div>
                </div>
            </div>
        </main>



        <style>
            @keyframes pulse {
                0% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.05);
                }

                100% {
                    transform: scale(1);
                }
            }

            .btn-confirm {
                animation: pulse 1.5s infinite;
                background-color: #28a745 !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease;
            }

            .btn-confirm:hover {
                animation: none;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }

            .footer-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100px;
                height: 100px;
                margin-right: 12px;
            }

            .footer-icon img.qr-code {
                width: 100%;
                height: 100%;
                background: white;
                padding: 3px;
                border-radius: 6px;
                box-sizing: border-box;
                object-fit: contain;
            }

            .whatsapp-icon {
                display: inline-flex;
                align-items: center;
                margin-left: 4px;
                vertical-align: middle;
                animation: pulse-whatsapp 2s infinite;
                transform-origin: center;
            }

            @keyframes pulse-whatsapp {
                0% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.15);
                }

                100% {
                    transform: scale(1);
                }
            }
        </style>
        <footer class="footer">
            <div class="footer-app">
                <div class="footer-icon">
                    <img src="assets/img/qr_code.png" alt="QR Code WhatsApp" class="qr-code">
                </div>
                <div class="footer-text">
                    <h3>Fale conosco pelo <strong>WhatsApp <span class="whatsapp-icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 48 48">
                                    <path fill="#25D366" d="M4.868 43.132L7.6 34.32A19.9 19.9 0 013.9 23.93C3.9 12.29 13.29 2.9 24.93 2.9S45.9 12.29 45.9 23.93 36.51 44.9 24.87 44.9c-3.77 0-7.43-.98-10.67-2.83l-9.33 1.06z" />
                                    <path fill="#FFF" d="M35.28 28.52c-.59-.3-3.48-1.72-4.02-1.92-.54-.2-.94-.3-1.33.3-.39.59-1.53 1.92-1.88 2.31-.34.39-.69.44-1.28.15-.59-.3-2.48-.91-4.73-2.9-1.75-1.56-2.93-3.48-3.27-4.06-.34-.59-.04-.91.26-1.2.27-.27.59-.69.89-1.03.3-.34.39-.59.59-.98.2-.39.1-.73-.05-1.03-.15-.3-1.33-3.21-1.83-4.4-.48-1.15-.97-.99-1.33-1.01-.34-.02-.73-.02-1.13-.02-.39 0-1.03.15-1.58.73-.54.59-2.07 2.02-2.07 4.92 0 2.9 2.12 5.7 2.41 6.09.3.39 4.17 6.37 10.1 8.94 1.41.61 2.51.97 3.37 1.25 1.41.45 2.69.39 3.7.23 1.13-.17 3.48-1.42 3.97-2.8.49-1.37.49-2.54.34-2.8-.15-.27-.54-.44-1.13-.73z" />
                                </svg></span></strong></h3>
                    <p>Escaneie o QR Code para iniciar uma conversa.</p>
                </div>
            </div>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="./assets/js/app.js"></script>
    <!-- <script>
        // Redirecionar para entrada.php após 15 segundos de inatividade
        let inactivityTime = function () {
            let time;
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;
            document.ontouchstart = resetTimer;
            document.onmousedown = resetTimer;

            function redirect() {
                window.location.href = 'entrada.php';
            }

            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(redirect, 15000); // 15 segundos
            }
        };

        // Iniciar o contador de inatividade
        inactivityTime();
    </script> -->
</body>

</html>