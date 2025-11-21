// DOM Elements
const screens = {
  welcome: document.getElementById('welcome-screen'),
  identify: document.getElementById('identify-screen'),
  appointments: document.getElementById('appointments-screen'),
  confirmation: document.getElementById('confirmation-screen'),
  queue: document.getElementById('queue-screen'),
  'ticket-options': document.getElementById('ticket-options-screen')
};


const currentTimeElement = document.getElementById('current-time');
const currentDateElement = document.getElementById('current-date');
const serviceTypeElement = document.getElementById('service-type');
const patientNameElement = document.getElementById('patient-name');
const confirmPatientNameElement = document.getElementById('confirm-patient-name');
const confirmServiceTypeElement = document.getElementById('confirm-service-type');
const confirmProfessionalElement = document.getElementById('confirm-professional');
const confirmSpecialtyElement = document.getElementById('confirm-specialty');
const confirmTimeElement = document.getElementById("confirm-time");
const confirmRoomElement = document.getElementById('confirm-room');
const appointmentsList = document.getElementById('appointments-list');
const cpfInput = document.getElementById('cpf-value');
const identifyBtn = document.getElementById('identify-btn');
const confirmBtn = document.getElementById('confirm-btn');
const newTicketBtn = document.getElementById('new-ticket-btn');
const ticketNumberElement = document.getElementById('ticket-number');
const ticketDateElement = document.getElementById('ticket-date');
const ticketTimeElement = document.getElementById('ticket-time');
const loadingInline = document.getElementById('loading-inline');


// State
let currentScreen = 'welcome';
let selectedOption = '';
let patientName = '';
let patientCPF = '';
let selectedAppointment = null;
let inactivityTimer = null;
let selectedTicketType = ''; // guarda o tipo de senha digital (Atendimento Geral, etc)
window.currentChaveBeneficiario = null;
let appointments = []; // Declarar appointments como variável global

// Variáveis do carrossel
let currentSlide = 0;
let totalSlides = 0;

// CPFs autorizados a pular a etapa de reconhecimento facial
const FACIAL_BYPASS_CPFS = ['54710755019'];

function solicitarCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                // Permissão concedida
                console.log("Acesso à câmera liberado.");
                
                // Se quiser exibir o vídeo:
                const video = document.getElementById("video");
                if (video) {
                    video.srcObject = stream;
                    video.play();
                }
            })
            .catch(err => {
                // Usuário negou ou erro
                console.error("Erro ao acessar a câmera:", err);
                alert("Não foi possível acessar a câmera: " + err.message);
            });
    } else {
        alert("Navegador não suporta câmera.");
    }
}

// Chama imediatamente ao carregar a página
solicitarCamera();

// Função para buscar agendamentos da API
async function fetchAppointments(cpf) {
  try {
    const cpfNumerico = cpf.replace(/\D/g, '');
    console.log('Iniciando busca de agendamentos para CPF (numérico):', cpfNumerico);

    if (cpfNumerico.length !== 11) {
      throw new Error('CPF inválido. O CPF deve conter 11 dígitos numéricos.');
    }

    // Mostra loader moderno dentro do card
    if (loadingInline) loadingInline.style.display = 'block';
    if (appointmentsList) {
      appointmentsList.style.display = 'none';
      appointmentsList.style.visibility = 'hidden';
    }

    // Autentica usuário
    const authResponse = await fetch(`https://ws.smilesaude.com.br/api/autenticaassociado/${cpfNumerico}/0`);
    const authData = await authResponse.json();
    console.log('Resposta da autenticação:', authData);

    if (authData.msg_erro || !authData.sucesso) {
      throw new Error(authData.msg_erro || 'Falha na autenticação. Verifique os dados.');
    }

    const chaveBeneficiario = authData.chave_beneficiario;
    if (!chaveBeneficiario) throw new Error('Não foi possível identificar a chave do beneficiário.');

    window.currentChaveBeneficiario = chaveBeneficiario;

    console.log('Buscando agendamentos para chave do beneficiário:', chaveBeneficiario);

    // Usa endpoints PHP específicos para cada tipo de serviço
    const endpointMap = {
      'terapia': 'ajax/agendamento_terapia_ajax.php',
      'exame': 'ajax/agendamento_exame_ajax.php'
    };
    const optionKey = (selectedOption || '').toLowerCase();
    const endpoint = endpointMap[optionKey] || 'ajax/agendamento_ajax.php';

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `chave_beneficiario=${encodeURIComponent(chaveBeneficiario)}`
    });
    if (!response.ok) throw new Error(`Erro ao buscar agendamentos: ${response.status}`);

    const data = await response.json();
    console.log('Resposta dos agendamentos:', data);

    if (!data.sucesso || !data.dados || !Array.isArray(data.dados)) {
      console.log('Nenhum agendamento encontrado ou formato de resposta inválido');
      console.log('Dados recebidos:', data);
      return [];
    }

    console.log('Dados brutos da API:', JSON.stringify(data, null, 2));

    const agendamentosMapeados = data.dados.map(agendamento => {
      console.log('Processando agendamento:', agendamento);
      const isConfirmed = agendamento.isConfirmed === '1';
      return {
        id: agendamento.id_atendimento,
        type: agendamento.tipo || 'Consulta',
        title: agendamento.nome_especialidade || 'Consulta',
        especialidade: agendamento.nome_especialidade || 'Consulta',
        profissional: agendamento.nome_profissional || '',
        professional: agendamento.nome_profissional || '',
        location: agendamento.nome_unidade || '',
        date: agendamento.data_agendamento || '',
        time: agendamento.hora_inicio || '',
        horario: agendamento.hora_inicio || '',
        endTime: agendamento.hora_final || '',
        status: isConfirmed ? 'Confirmado' : 'Pendente',
        isConfirmed: isConfirmed,
        room: 'A definir',
        nome_unidade: agendamento.nome_unidade || ''
      };
    });

    console.log('Agendamentos mapeados:', JSON.stringify(agendamentosMapeados, null, 2));
    appointments = agendamentosMapeados; // Atribuir ao array global
    return agendamentosMapeados;

  } catch (error) {
    console.error('Erro ao buscar agendamentos:', error);

    if (loadingInline) loadingInline.style.display = 'none';
    if (appointmentsList) {
      appointmentsList.style.display = 'block';
      appointmentsList.style.visibility = 'visible';
    }

    await Swal.fire({
      icon: 'error',
      title: 'Erro ao buscar agendamentos',
      text: error.message || 'Ocorreu um erro ao buscar seus agendamentos.',
      confirmButtonText: 'Entendi',
      allowOutsideClick: false
    });

    return [];
  }
}


// Format CPF input
function formatCPF(cpf) {
  // Remove all non-digit characters
  cpf = cpf.replace(/\D/g, '');

  // Limit to 11 digits
  cpf = cpf.substring(0, 11);

  // Apply CPF mask
  if (cpf.length > 9) {
    cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})?$/, function (_, p1, p2, p3, p4) {
      return p1 + '.' + p2 + '.' + p3 + (p4 ? '-' + p4 : '');
    });
  } else if (cpf.length > 6) {
    cpf = cpf.replace(/(\d{3})(\d{3})(\d{1,3})?$/, function (_, p1, p2, p3) {
      return p1 + '.' + p2 + (p3 ? '.' + p3 : '');
    });
  } else if (cpf.length > 3) {
    cpf = cpf.replace(/(\d{3})(\d{1,3})?$/, function (_, p1, p2) {
      return p1 + (p2 ? '.' + p2 : '');
    });
  }

  return cpf;
}

// Formata hora para padrão 24h (HH:MM)
function formatTime(time24) {
  if (!time24) return '';
  // Remove segundos se existirem (formato HH:MM:SS -> HH:MM)
  const timeParts = time24.split(':');
  if (timeParts.length >= 2) {
    return `${String(timeParts[0]).padStart(2, '0')}:${String(timeParts[1]).padStart(2, '0')}`;
  }
  return time24;
}


// Format date to DD/MM/YYYY
function formatDate(date) {
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

// Format time to HH:MM:SS
function formatTimeOnly(date) {
  return date.toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  });
}

// Update analog clock hands
let lastSecond = -1;
let lastMinute = -1;
let lastHour = -1;

function updateClockHands() {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();

  // Calcula ângulos
  const hourDeg = ((hours % 12) / 12) * 360 + (minutes / 60) * 30;
  const minuteDeg = (minutes / 60) * 360 + (seconds / 60) * 6;
  const secondDeg = (seconds / 60) * 360;

  const hourHand = document.getElementById('hour-hand');
  const minuteHand = document.getElementById('minute-hand');
  const secondHand = document.getElementById('second-hand');

  // Atualiza sem rotação inversa
  if (secondHand && seconds !== lastSecond) {
    secondHand.style.transition = seconds === 0 ? 'none' : 'transform 0.05s linear';
    secondHand.style.transform = `translate(-50%, -100%) rotate(${secondDeg}deg)`;
    lastSecond = seconds;
  }

  if (minuteHand && minutes !== lastMinute) {
    minuteHand.style.transform = `translate(-50%, -100%) rotate(${minuteDeg}deg)`;
    lastMinute = minutes;
  }

  if (hourHand && hours !== lastHour) {
    hourHand.style.transform = `translate(-50%, -100%) rotate(${hourDeg}deg)`;
    lastHour = hours;
  }
}


// Update date and time
function updateDateTime() {
  const now = new Date();
  const timeString = formatTimeOnly(now);
  const dateString = formatDate(now);

  // Update digital display
  if (currentTimeElement) currentTimeElement.textContent = timeString;
  if (currentDateElement) currentDateElement.textContent = dateString;

  // Update analog clock
  updateClockHands();
}

// Initialize clock with smooth start
function initClock() {
  // Set initial time immediately
  updateDateTime();

  // Then update every second (apenas um setInterval)
  setInterval(updateDateTime, 1000);
}

// ====================== SHOW SCREEN ATUALIZADA ======================
function showScreen(screenName) {
  // Esconde todas as telas
  Object.values(screens).forEach(screen => {
    screen.classList.remove('active');
  });

  // Mostra a tela selecionada
  if (screens[screenName]) {
    screens[screenName].classList.add('active');
    currentScreen = screenName;

    // Ativa seleção de agendamento quando a tela for exibida
    if (screenName === 'appointments') {
      // Pequeno delay para garantir que o DOM foi renderizado
      setTimeout(() => {
        const cards = document.querySelectorAll('.appointment-card');
        if (cards.length > 0) {
          console.log('🔄 Reativando seleção de agendamentos para touch screen');
        }
      }, 100);
    }

    if (screenName === 'confirmation' && confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.style.opacity = '1';
    }
  }

  // Controla scroll do body na tela de identificação
  if (document.body) {
    if (screenName === 'identify') {
      document.body.classList.add('identify-no-scroll');
    } else {
      document.body.classList.remove('identify-no-scroll');
    }
  }

  // Reset CPF na tela de identificação
  if (screenName === 'identify') {
    const cpfInput = document.getElementById('cpf-value');
    const cpfSlots = document.querySelectorAll('.cpf-slot');
    if (cpfInput) cpfInput.value = '';
    cpfDigits = [];
    cpfSlots.forEach(slot => {
      slot.textContent = '';
      slot.classList.remove('pop', 'filled');
    });
  }

  // Reinicia o timer de inatividade
  resetInactivityTimer();

  // Dispara evento personalizado para mudança de tela
  const screenChangedEvent = new CustomEvent('screenChanged', {
    detail: { screen: screenName }
  });
  document.dispatchEvent(screenChangedEvent);
}

// Reset inactivity timer
function resetInactivityTimer() {
  if (inactivityTimer) {
    clearTimeout(inactivityTimer);
  }

  inactivityTimer = setTimeout(() => {
    if (currentScreen !== 'welcome') {
      showScreen('welcome');
      resetForm();
    }
  }, 60000); // 60 seconds
}

// Reset form
function resetForm() {
  selectedOption = '';
  patientName = '';
}

// ====================== RENDERIZAR AGENDAMENTOS (COM CORREÇÃO TOUCH) ======================
async function renderAppointments(cpf) {
  const inlineLoading = document.getElementById('loading-inline');
  if (inlineLoading) inlineLoading.style.display = 'flex';

  if (!cpf) {
    console.error('Erro: CPF não fornecido para renderAppointments');
    const cpfInput = document.getElementById('cpf-value');
    if (cpfInput) {
      cpf = cpfInput.value.replace(/\D/g, '');
      if (cpf.length !== 11) {
        showScreen('identify');
        return;
      }
    } else {
      showScreen('identify');
      return;
    }
  }

  console.log('Iniciando renderização de agendamentos para CPF:', cpf);

  try {
    const appointments = await fetchAppointments(cpf);

    console.log('Agendamentos recebidos para renderização:', appointments);

    appointmentsList.innerHTML = '';

    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'none';

    if (!appointments || appointments.length === 0) {
      console.log('Nenhum agendamento encontrado para o CPF:', cpf);
      appointmentsList.innerHTML = `
                <div class="no-appointments">
                    <div class="no-appointments-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h4>Nenhum agendamento encontrado</h4>
                    <p>Não encontramos agendamentos para o CPF informado.</p>
                    <div class="cpf-display">
                        <span>CPF: ${cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')}</span>
                    </div>
                </div>
            `;
      return;
    }

    const sortedAppointments = [...appointments].sort((a, b) => {
      const [dayA, monthA, yearA] = a.date.split('/').map(Number);
      const [hourA, minuteA] = a.time.split(':').map(Number);
      const [dayB, monthB, yearB] = b.date.split('/').map(Number);
      const [hourB, minuteB] = b.time.split(':').map(Number);

      const dateA = new Date(yearA, monthA - 1, dayA, hourA, minuteA);
      const dateB = new Date(yearB, monthB - 1, dayB, hourB, minuteB);

      return dateA - dateB;
    });

    const carouselHTML = `
            <div class="appointments-carousel">
                <button class="carousel-btn prev" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="appointments-wrapper">
                    ${sortedAppointments.map((appointment, index) => `
                        <div class="appointment-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                            <div class="appointment-card ${appointment.status.toLowerCase()}" data-id="${appointment.id}">
                                <div class="appointment-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: nowrap; gap: 15px;">
                                    <div class="appointment-time" style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                        <i class="far fa-clock" style="color: black !important; min-width: 16px;"></i>
                                        <span>${appointment.time} às ${appointment.endTime}</span>
                                    </div>
                                    ${appointment.type ? `<span style="background: rgba(0, 0, 0, 0.1); color: #333; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; white-space: nowrap; margin-right: auto;">${appointment.type}</span>` : '<span style="flex-grow: 1;"></span>'}
                                    <div class="appointment-date" style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                        <i class="far fa-calendar-alt" style="min-width: 16px;"></i>
                                        <span>${appointment.date}</span>
                                    </div>
                                </div>
                                <div class="appointment-details">
                                    <p class="professional">
                                        <i class="fas fa-user-md"></i>
                                        ${appointment.profissional || appointment.professional || 'Profissional não informado'}
                                    </p>
                                    <p class="specialty">
                                        <i class="fas fa-stethoscope"></i>
                                        ${appointment.especialidade || appointment.title || 'Consulta'}
                                    </p>
                                    <p class="room">
                                        <i class="fas fa-map-marker-alt"></i>
                                        ${appointment.nome_unidade || appointment.location || 'Local não informado'}
                                    </p>
                                    <div class="appointment-status">
                                        ${appointment.isConfirmed ?
        '<span style="background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-check-circle"></i> Confirmado</span>' :
        '<span style="background: #FFC107; color: #333; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;"><i class="far fa-clock" style="color: #FFF;"></i> Aguardando confirmação</span>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <button class="carousel-btn next" aria-label="Próximo">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <div class="carousel-dots">
                    ${sortedAppointments.map((_, index) => `
                        <button class="carousel-dot ${index === 0 ? 'active' : ''}" data-index="${index}" aria-label="Ir para o slide ${index + 1}"></button>
                    `).join('')}
                </div>
            </div>
        `;

    appointmentsList.innerHTML = carouselHTML;
    appointmentsList.style.display = 'block';

    initCarousel();
    enableTouchAppointmentSelection(sortedAppointments); // CHAMADA ATUALIZADA

  } catch (error) {
    console.error('❌ Erro ao carregar agendamentos:', error);
    appointmentsList.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Ocorreu um erro ao carregar seus agendamentos.</p>
                <button class="retry-btn">Tentar novamente</button>
            </div>
        `;

    const retryBtn = appointmentsList.querySelector('.retry-btn');
    if (retryBtn) {
      retryBtn.addEventListener('click', () => renderAppointments(cpf));
    }
  } finally {
    if (inlineLoading) inlineLoading.style.display = 'none';
    if (appointmentsList) {
      appointmentsList.style.display = 'block';
      appointmentsList.style.visibility = 'visible';
    }
  }
}

// ====================== SELEÇÃO DE AGENDAMENTOS OTIMIZADA PARA TOUCH ======================
function enableTouchAppointmentSelection(appointments) {
  const cards = document.querySelectorAll('.appointment-card');
  let selectedCard = null;
  let touchStartTime = 0;
  let touchStartX = 0;
  let touchStartY = 0;

  // Remove seleção e botões de confirmação de todos os cards
  function clearAllSelections() {
    cards.forEach(card => {
      card.classList.remove('selected', 'touch-active');
      const btn = card.querySelector('.confirm-appointment-btn');
      if (btn) btn.remove();
    });
    selectedCard = null;

    // Desabilita o botão de confirmação global
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.6';
    }
  }

  // Adiciona botão de confirmação ao card selecionado
  function addConfirmButton(card) {
    // Remove botão existente se houver
    const existingBtn = card.querySelector('.confirm-appointment-btn');
    if (existingBtn) existingBtn.remove();

    const confirmBtn = document.createElement('button');
    confirmBtn.className = 'confirm-appointment-btn primary-btn';
    confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Agendamento';
    confirmBtn.style.cssText = `
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 30px);
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            background: var(--primary-dark);
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(232, 140, 56, 0.3);
            transition: all 0.3s ease;
            z-index: 10;
            opacity: 0;
            animation: slideUpFade 0.3s ease forwards;
        `;

    // Adiciona estilos CSS para a animação
    if (!document.querySelector('#confirm-btn-styles')) {
      const style = document.createElement('style');
      style.id = 'confirm-btn-styles';
      style.textContent = `
                @keyframes slideUpFade {
                    from {
                        opacity: 0;
                        transform: translateX(-50%) translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                }
                
                .appointment-card.selected {
                    transform: scale(0.98);
                    transition: transform 0.2s ease;
                }
                
                .appointment-card.touch-active {
                    background-color: rgba(232, 140, 56, 0.05) !important;
                }
                
            `;
      document.head.appendChild(style);
    }

    card.appendChild(confirmBtn);

    // Evento de clique/toque no botão de confirmação
    confirmBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      handleAppointmentConfirmation(card, appointments);
    });

    // Também responde a toque no botão
    confirmBtn.addEventListener('touchend', (e) => {
      e.stopPropagation();
      e.preventDefault();
      handleAppointmentConfirmation(card, appointments);
    });
  }

  // Manipula a confirmação do agendamento - VERSÃO COMPLETA ATUALIZADA
  function handleAppointmentConfirmation(card, appointments) {
    const appointmentId = card.dataset.id;
    const appointment = appointments.find(a => a.id === appointmentId);

    if (!appointment) {
      console.error('Agendamento não encontrado');
      return;
    }

    selectedAppointment = appointment;
    
    const confirmHtml = `
      <div class="appointment-confirm" style="text-align: left; padding: 10px;">
        <div class="appointment-details">
          <p><strong>Profissional:</strong> ${appointment.profissional || appointment.professional || '—'}</p>
          <p><strong>Data:</strong> ${appointment.date}</p>
          <p><strong>Horário:</strong> ${appointment.time} às ${appointment.endTime}</p>
          <p><strong>Especialidade:</strong> ${appointment.especialidade || appointment.title || '—'}</p>
          <p><strong>Local:</strong> ${appointment.nome_unidade || appointment.location || '—'}</p>
        </div>
      </div>
    `;

    Swal.fire({
      title: 'Confirmar Check-in',
      html: confirmHtml,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Confirmar Check-in',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn-confirm',
        cancelButton: 'btn-cancel'
      },
      buttonsStyling: false,
      allowOutsideClick: false
    }).then(async (result) => {
      if (result.isConfirmed) {
        const chave = currentChaveBeneficiario;
        
        // 🔄 LOADING DINÂMICO PARA TOUCH
        let currentProgress = "Iniciando confirmação...";
        
        const loadingSwal = Swal.fire({
          title: 'Processando Confirmação',
          html: `
            <div style="text-align: center; padding: 20px;">
              <div class="swal2-spinner" style="margin: 20px auto;"></div>
              <div id="progress-text" style="margin-top: 20px; font-size: 1.1em; font-weight: 500;">
                ${currentProgress}
              </div>
            </div>
          `,
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => {
            const progressElement = document.getElementById('progress-text');
            if (progressElement) {
              setInterval(() => {
                progressElement.textContent = currentProgress;
              }, 100);
            }
          }
        });

        // Função para atualizar o progresso
        function updateProgress(message) {
          currentProgress = message;
        }

        try {
          // CHAMADA CORRIGIDA - passando updateProgress como parâmetro
          const confirmado = await confirmarAgendamentoReal(
            selectedAppointment.id, 
            chave, 
            appointments, 
            updateProgress
          );

          // Finaliza com mensagem de conclusão
          updateProgress("Finalizando processo...");
          await new Promise(resolve => setTimeout(resolve, 500));
          Swal.close();

          if (!confirmado.sucesso) {
            await Swal.fire({
              icon: "error",
              title: "Erro na Confirmação",
              text: confirmado.msg || "Não foi possível confirmar o agendamento.",
              confirmButtonText: "Entendi"
            });
            return;
          }

          // Atualiza interface
          const targetCard = document.querySelector(`.appointment-card[data-id="${selectedAppointment.id}"]`);
          if (targetCard) {
            const statusBadge = targetCard.querySelector(".appointment-status");
            if (statusBadge) {
              statusBadge.innerHTML = `
                <span style="background:#4CAF50; color:white; padding:2px 8px; border-radius:12px; font-size:0.8em; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                  <i class="fas fa-check-circle"></i> Confirmado
                </span>
              `;
            }
          }

          // ✅ MODAL DE SUCESSO COM DUAS OPÇÕES (COM FONT AWESOME)
          await Swal.fire({
            title: '<i class="fas fa-check-circle" style="color: #4CAF50; font-size: 1.2em;"></i> Check-in Confirmado!',
            html: `
              <div style="text-align: center; padding: 15px;">
                <p style="font-size: 1.2em; margin-bottom: 20px; color: #2E7D32;">
                  <i class="fas fa-check-double" style="color: #4CAF50; margin-right: 8px;"></i>
                  Seu agendamento foi confirmado com sucesso!
                </p>
                <p style="color: #666; font-size: 1em; margin-bottom: 25px;">
                  <i class="fas fa-clock" style="color: #E88C38; margin-right: 8px;"></i>
                  Você já está na fila para atendimento.
                </p>
              </div>
            `,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-calendar-alt"></i> Meus Agendamentos',
            cancelButtonText: '<i class="fas fa-home"></i> Tela Inicial',
            confirmButtonColor: '#E88C38',
            cancelButtonColor: '#6C757D',
            reverseButtons: true,
            focusConfirm: false,
            allowOutsideClick: false,
            customClass: {
              confirmButton: 'swal-custom-confirm',
              cancelButton: 'swal-custom-cancel',
              popup: 'swal-custom-popup'
            }
          }).then((result) => {
            if (result.isConfirmed) {
              // Usuário clicou em "Meus Agendamentos" - atualiza lista e volta para agendamentos
              renderAppointments(patientCPF);
              showScreen('appointments');
            } else {
              // Usuário clicou em "Tela Inicial" ou cancelou - vai para welcome
              showScreen('welcome');
              resetForm();
            }
          });

        } catch (error) {
          Swal.close();
          await Swal.fire({
            icon: "error",
            title: '<i class="fas fa-exclamation-triangle"></i> Erro no Processamento',
            text: "Ocorreu um erro durante a confirmação. Tente novamente.",
            confirmButtonText: '<i class="fas fa-check"></i> Entendi'
          });
        }
      }
    });
  }

  // Adiciona eventos para cada card
  cards.forEach(card => {
    // Evento de toque inicial
    card.addEventListener('touchstart', (e) => {
      e.preventDefault();
      touchStartTime = Date.now();
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;

      card.classList.add('touch-active');
    });

    // Evento de movimento do toque (para evitar conflito com swipe)
    card.addEventListener('touchmove', (e) => {
      const touch = e.touches[0];
      const deltaX = Math.abs(touch.clientX - touchStartX);
      const deltaY = Math.abs(touch.clientY - touchStartY);

      // Se o movimento for muito grande, remove o estado ativo (provavelmente é um swipe)
      if (deltaX > 10 || deltaY > 10) {
        card.classList.remove('touch-active');
      }
    });

    // Evento de fim do toque (equivalente ao clique)
    card.addEventListener('touchend', (e) => {
      e.preventDefault();
      card.classList.remove('touch-active');

      const touchDuration = Date.now() - touchStartTime;
      const touch = e.changedTouches[0];
      const deltaX = Math.abs(touch.clientX - touchStartX);
      const deltaY = Math.abs(touch.clientY - touchStartY);

      // Só considera como toque válido se foi rápido e com pouco movimento
      if (touchDuration < 500 && deltaX < 10 && deltaY < 10) {
        handleCardSelection(card);
      }
    });

    // Também mantém suporte a clique com mouse
    card.addEventListener('click', (e) => {
      e.preventDefault();
      handleCardSelection(card);
    });
  });

  // Manipula a seleção do card
  function handleCardSelection(card) {
    // Se clicou no mesmo card que já está selecionado, deseleciona
    if (card === selectedCard) {
      clearAllSelections();
      return;
    }

    // Remove seleção anterior
    clearAllSelections();

    // Adiciona seleção ao novo card
    card.classList.add('selected');
    selectedCard = card;

    // Adiciona botão de confirmação
    addConfirmButton(card);

    // Atualiza o agendamento selecionado
    const appointmentId = card.dataset.id;
    selectedAppointment = appointments.find(a => a.id === appointmentId);
  }

  // Limpa seleção ao mudar de slide
  document.addEventListener('slideChanged', clearAllSelections);

  // Limpa seleção ao sair da tela
  document.addEventListener('screenChanged', (e) => {
    const targetScreen = e.detail?.screen;
    // Limita a limpeza apenas quando saímos da tela de agendamentos para outras telas
    if (targetScreen && targetScreen !== 'appointments' && targetScreen !== 'confirmation') {
      clearAllSelections();
    }
  });
}

// ====================== ATUALIZAÇÃO DA TELA DE CONFIRMAÇÃO ======================
function updateConfirmationScreen() {

  console.log("updateConfirmationScreen() recebeu:", selectedAppointment);
  console.log("HORÁRIO recebido:", selectedAppointment?.horario);

  if (!selectedAppointment) return;

  // Dados do paciente e serviço
  if (confirmPatientNameElement) confirmPatientNameElement.textContent = patientName || 'Paciente';
  if (confirmServiceTypeElement) confirmServiceTypeElement.textContent = selectedAppointment.type || selectedAppointment.tipo || '—';
  if (confirmSpecialtyElement) confirmSpecialtyElement.textContent = selectedAppointment.especialidade || selectedAppointment.nome_especialidade || selectedAppointment.title || '—';

  // Formata o horário
  if (confirmTimeElement) confirmTimeElement.textContent = selectedAppointment.horario || "";


  // Atualiza o status de confirmação
  const confirmationStatusElement = document.getElementById('confirmation-status');
  if (confirmationStatusElement) {
    if (selectedAppointment.isConfirmed) {
      confirmationStatusElement.innerHTML = '<i class="fas fa-check-circle"></i> Agendamento Confirmado';
      confirmationStatusElement.className = 'confirmation-status confirmed';
    } else {
      confirmationStatusElement.innerHTML = '<i class="far fa-clock"></i> Aguardando Confirmação';
      confirmationStatusElement.className = 'confirmation-status pending';
    }
  }

  // Seleciona o campo do "Profissional/Local"
  const professionalLabel = document.querySelector('#label-professional');
  const professionalValue = confirmProfessionalElement;

  if (professionalLabel && professionalValue) {
    if ((selectedAppointment.type || selectedAppointment.tipo || '').toLowerCase() === 'exame') {
      professionalLabel.textContent = 'Local:';
      professionalValue.textContent = selectedAppointment.local || 'Laboratório';
    } else {
      professionalLabel.textContent = 'Profissional:';
      professionalValue.textContent = selectedAppointment.professional || selectedAppointment.profissional || '—';
    }
  }
}

// Generate a random ticket number
function generateTicketNumber() {
  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const randomLetter = letters[Math.floor(Math.random() * letters.length)];
  const randomNumber = Math.floor(Math.random() * 90) + 10; // 10-99
  return `${randomLetter}${randomNumber}`;
}

// Handle numeric keyboard input
document.querySelectorAll('.key').forEach(key => {
  key.addEventListener('click', (e) => {
    e.preventDefault();
    const keyValue = key.getAttribute('data-key');
    const cpfInput = document.getElementById('cpf-value');

    let currentValue = cpfInput.value.replace(/\D/g, ''); // Get only digits

    if (keyValue === 'backspace') {
      // Remove last character
      currentValue = currentValue.slice(0, -1);
    } else if (keyValue === 'clear') {
      // Clear all input
      currentValue = '';
      cpfInput.value = '';
      cpfInput.focus();
      return;
    } else if (keyValue.match(/^\d$/) && currentValue.length < 11) {
      // Add new digit if it's a number and we haven't reached the limit
      currentValue += keyValue;
    }

    // Update input value with formatted CPF
    cpfInput.value = formatCPF(currentValue);
  });
});

// Add shake animation to CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .shake {
        animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        border-color: var(--error) !important;
    }
`;
document.head.appendChild(style);


// Adiciona evento de submit ao formulário de identificação
document.getElementById('identify-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const cpfInput = document.getElementById('cpf-value');
  const cpf = cpfInput.value.replace(/\D/g, '');

  if (cpf.length !== 11) {
    const cpfDisplay = document.getElementById('cpf-display');
    cpfDisplay.classList.add('shake');
    setTimeout(() => cpfDisplay.classList.remove('shake'), 500);
    return;
  }

  showScreen('appointments');

  // 🔸 Remove o overlay global, mostra o loading interno
  const overlay = document.getElementById('loading-overlay');
  const inlineLoading = document.getElementById('loading-inline');

  if (overlay) overlay.style.display = 'none'; // nunca mostrar o global
  if (inlineLoading) inlineLoading.style.display = 'flex'; // mostra o spinner dentro do card


  clearTimeout(inactivityTimer); // Pausa o retorno automático

  try {
    await renderAppointments(cpf);
  } catch (error) {
    console.error('Erro ao carregar agendamentos:', error);
    Swal.fire({
      icon: 'error',
      title: 'Erro ao carregar agendamentos',
      text: 'Ocorreu um erro ao buscar seus agendamentos. Por favor, tente novamente mais tarde.',
      confirmButtonText: 'Entendi'
    });
  } finally {
    if (inlineLoading) inlineLoading.style.display = 'none'; // esconde o spinner interno


    resetInactivityTimer(); //  Retoma o timer normal
  }
});


// Adiciona evento de clique para as opções do menu
// ======= DEBUG - CLIQUE NAS OPÇÕES DO MENU PRINCIPAL =======
document.querySelectorAll('.option-btn').forEach(button => {
  button.addEventListener('click', (e) => {
    const option = e.currentTarget.dataset.option;
    selectedOption = option;

    console.log('🟠 [DEBUG] Botão clicado:', option);

    const serviceTypeElement = document.getElementById('service-type');
    if (serviceTypeElement) {
      serviceTypeElement.textContent = option;
      console.log('🟢 [DEBUG] service-type atualizado para:', option);
    }

    if (option === 'senha') {
      console.log('🟣 [DEBUG] Direcionando para tela: ticket-options');
      showScreen('ticket-options');
    } else {
      console.log('🔵 [DEBUG] Direcionando para tela: identify');
      showScreen('identify');
    }
  });
});



// === Botões Voltar (ajustado para respeitar o histórico real de navegação) ===
document.querySelectorAll('.back-btn').forEach(button => {
  button.addEventListener('click', () => {
    const activeScreen = document.querySelector('.screen.active');

    if (!activeScreen) return;

    const id = activeScreen.id;

    switch (id) {
      case 'identify-screen':
      case 'ticket-options-screen':
        showScreen('welcome');
        resetForm();
        break;

      case 'appointments-screen':
        showScreen('identify');
        break;

      case 'confirmation-screen':
        showScreen('appointments');
        break;

      case 'queue-screen':
        showScreen('ticket-options');
        break;

      default:
        showScreen('welcome');
        resetForm();
    }
  });
});




// ======= AUTENTICAÇÃO DE ASSOCIADO =======
let confirmModalOpen = false; // evita reabertura duplicada

document.getElementById('identify-btn').addEventListener('click', async () => {
  const cpfInputField = document.getElementById('cpf-value');
  const cpfInput = cpfInputField.value.replace(/\D/g, '');

  if (!cpfInput || cpfInput.length !== 11) {
    Swal.fire({
      icon: 'warning',
      title: 'CPF inválido',
      text: 'Digite um CPF válido antes de continuar.',
      confirmButtonColor: '#E88C38'
    });
    return;
  }

  try {
    Swal.fire({
      title: 'Verificando dados...',
      text: 'Aguarde enquanto validamos seu CPF.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const response = await fetch(`https://ws.smilesaude.com.br/api/autenticaassociado/${cpfInput}/0`);
    const data = await response.json();

    if (!data.sucesso) {
      Swal.close();
      await Swal.fire({
        icon: 'warning',
        title: 'Paciente não encontrado',
        text: 'Verifique o CPF e tente novamente.',
        confirmButtonColor: '#E88C38'
      });
      return;
    }

    // 🔹 Busca imagem e fecha loading
    Swal.fire({
      title: 'Carregando informações...',
      html: '<p>Buscando dados complementares do paciente...</p>',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });

    const cpfLimpo = data.cpf.replace(/\D/g, '');
    const imagemBase64 = await buscarImagemBioDoc(cpfLimpo);
    data.imagemBase64 = imagemBase64 || null;
    Swal.close();

    // Evita abrir o mesmo modal duas vezes
    if (confirmModalOpen) return;
    confirmModalOpen = true;

    await Swal.fire({
      title: 'Confirmar Paciente:',
      html: `
        <div class="appointment-confirmation">
          <div class="appointment-header">
            ${data.imagemBase64
          ? `<img src="${data.imagemBase64}"
                       alt="Foto do paciente"
                       style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin-bottom:0.75rem;">`
          : `<i class="fas fa-user-circle" style="font-size:5rem;color:#E88C38;"></i>`
        }
            <h3>${data.nome}</h3>
          </div>
          <div class="appointment-detail">
            <div class="detail-icon"><i class="fas fa-id-card"></i></div>
            <div class="detail-content">
              <span class="detail-label">Carteirinha:</span>
              <span class="detail-value">${data.matricula}</span>
            </div>
          </div>
          <div class="appointment-detail">
            <div class="detail-icon"><i class="fas fa-id-badge"></i></div>
            <div class="detail-content">
              <span class="detail-label">CPF:</span>
              <span class="detail-value">${data.cpf}</span>
            </div>
          </div>
          <div class="appointment-detail">
            <div class="detail-icon"><i class="fas fa-birthday-cake"></i></div>
            <div class="detail-content">
              <span class="detail-label">Data de Nascimento:</span>
              <span class="detail-value">${data.contratos?.[0]?.familia?.[0]?.data_de_nascimento || '—'}</span>
            </div>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Sim, sou eu',
      cancelButtonText: 'Não sou eu',
      confirmButtonColor: '#E88C38',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then(async result => {
      confirmModalOpen = false;

      if (result.isConfirmed) {
        patientName = data.nome;
        patientCPF = data.cpf.replace(/\D/g, '');
        document.getElementById('patient-name').textContent = patientName;

        // 🎉 Aniversário
        const dataNasc = data.contratos?.[0]?.familia?.[0]?.data_de_nascimento || '';
        if (dataNasc) {
          const [dia, mes] = dataNasc.split('/');
          const hoje = new Date();
          const diaHoje = String(hoje.getDate()).padStart(2, '0');
          const mesHoje = String(hoje.getMonth() + 1).padStart(2, '0');
          if (dia === diaHoje && mes === mesHoje) {
            await Swal.fire({
              title: 'Feliz Aniversário!',
              html: `<div style="text-align:center;display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
                      <i class="fas fa-birthday-cake" style="font-size:2.25rem;color:#E88C38;"></i>
                      <h2 style="color:#E88C38; font-weight:700;margin:0;">Parabéns, ${data.nome.split(' ')[0]}!</h2>
                      <p style="font-size:1.1rem; color:#444;margin:0;">Desejamos muita saúde, alegria e sucesso</p>
                      <div style="display:flex;gap:0.5rem;font-size:1.4rem;color:#E88C38;">
                        <i class="fas fa-confetti"></i>
                      </div>
                    </div>`,
              confirmButtonText: 'Obrigado <i class="fa-solid fa-heart"></i>',
              confirmButtonColor: '#E88C38',
              background: '#fff url("https://cdn.pixabay.com/photo/2016/11/29/04/17/confetti-1869795_1280.png") center/cover no-repeat',
              didOpen: () => startConfettiAnimation(),
              willClose: () => stopConfettiAnimation()
            });
          }
        }

        // 🚫 Se for "Retirar Senha Digital", gera ticket direto
        if (selectedOption === 'senha') {
          await Swal.fire({
            title: 'Selecione o tipo de atendimento',
            html: `
              <div style="display:flex;flex-direction:column;gap:1.2rem;align-items:center;margin-top:1rem;width:100%;">
                <button id="btn-normal" class="swal-btn-priority" style="background-color:#E88C38;color:#fff;border:none;border-radius:10px;padding:1rem 0;font-size:1.1rem;font-weight:600;width:260px;">Atendimento Normal</button>
                <button id="btn-preferencial" class="swal-btn-priority" style="background-color:#4CAF50;color:#fff;border:none;border-radius:10px;padding:1rem 0;font-size:1.1rem;font-weight:600;width:260px;">Atendimento Preferencial</button>
                <div id="btn-voltar" style="display:flex;align-items:center;justify-content:center;gap:6px;color:#E88C38;font-weight:600;font-size:1rem;margin-top:1rem;cursor:pointer;">
                  <i class="fas fa-arrow-left"></i><span>Voltar</span>
                </div>
              </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
              document.getElementById('btn-normal').addEventListener('click', () => {
                Swal.close();
                gerarTicket('Normal');
              });
              document.getElementById('btn-preferencial').addEventListener('click', () => {
                Swal.close();
                gerarTicket('Preferencial');
              });
              document.getElementById('btn-voltar').addEventListener('click', () => {
                Swal.close();
                showScreen('ticket-options');
              });
            }
          });
          return;
        }

        // Se o CPF estiver autorizado, pula a etapa facial
        if (FACIAL_BYPASS_CPFS.includes(patientCPF)) {
          await Swal.fire({
            icon: 'success',
            title: 'Reconhecimento facial dispensado',
            text: 'Seu CPF já está autorizado. Prosseguindo para os agendamentos.',
            confirmButtonColor: '#E88C38'
          });

          const cpfAtual = document.getElementById('cpf-value');
          const cpfLimpoAtual = cpfAtual ? cpfAtual.value.replace(/\D/g, '') : patientCPF;
          if (cpfLimpoAtual && cpfLimpoAtual.length === 11) {
            // Mostra a tela de agendamentos imediatamente e carrega dados em paralelo
            showScreen('appointments');
            renderAppointments(cpfLimpoAtual);
          } else {
            showScreen('identify');
          }
          return;
        }

        // ✅ Caso contrário (Consulta, Exame, Terapia) → facial
        await showFaceGuidanceModal();
        Swal.fire({
          title: 'Verificação de Identidade',
          html: `
            <div id="camera-container" style="width:100%;height:320px;position:relative;background:#000;border-radius:12px;overflow:hidden;">
              <video id="camera-stream" autoplay playsinline style="width:100%;height:100%;object-fit:cover;"></video>
            </div>
            <p style="margin-top:15px;">Posicione o rosto dentro do quadro e clique em "Verificar".</p>
          `,
          confirmButtonText: 'Verificar',
          showCancelButton: true,
          cancelButtonText: 'Cancelar',
          allowOutsideClick: false,
          didOpen: () => iniciarCamera(),
          willClose: () => pararCamera(),
          showLoaderOnConfirm: true,
          preConfirm: async () => {
            try {
              const imageBase64 = await capturarFoto();
              if (!imageBase64) throw new Error('Falha na captura de imagem.');

              const TOKEN = 'S2xNaFlKRXIyVEx3WFphMEM5bnRmeGhtcGVkcHVrMjZobm9DLzRxMzFUbTYvNU5pOFI4Zmo4WjFuWkxJL2Qrdg==';
              const URL = 'https://api.biodoc.com.br/api/integrations/verify';
              const idCard = data.cpf.replace(/\D/g, '');

              const response = await axios.post(URL, {
                id: idCard,
                image: imageBase64,
                detail: JSON.stringify({
                  origem: 'Totem',
                  nomePaciente: data.nome,
                  data: new Date().toISOString(),
                })
              }, {
                headers: {
                  Authorization: `Bearer ${TOKEN}`,
                  'Content-Type': 'application/json'
                },
                validateStatus: function (status) {
                  // permite tratar 422 manualmente sem lançar exceção
                  return status >= 200 && status < 500;
                }
              });

              // Trata 422 com mensagem clara para o usuário
              const apiError = String(response.data?.error || response.data?.message || '').toLowerCase();
              const noRecordMsg = 'nenhum registro foi encontrado com o id fornecido';

              if (response.status === 422 || apiError.includes(noRecordMsg) || response.data?.code === 'ERR_CRD_GET_IN_VERIFY') {
                Swal.close();
                await Swal.fire({
                  icon: 'warning',
                  title: 'Identidade não verificada',
                  html: `
                    Não encontramos um registro biométrico correspondente ao seu cadastro.<br><br>
                    Por favor, dirija-se à recepção para prosseguir com o atendimento.
                  `,
                  confirmButtonText: 'OK',
                  allowOutsideClick: false,
                  allowEscapeKey: false
                });

                // Limpa o campo de CPF quando o usuário confirma o alerta
                try {
                  const cpfEl = document.getElementById('cpf-value');
                  if (cpfEl) {
                    cpfEl.value = '';
                    cpfEl.focus();
                  }
                  // Limpa estado visual do CPF (caso esteja usando as caixas visuais)
                  try { cpfDigits = []; } catch (e) { /* ignore */ }
                  if (Array.isArray(window.cpfDigits)) window.cpfDigits = [];
                  if (typeof updateCPFDisplay === 'function') updateCPFDisplay();
                  // Limpa estado global de CPF
                  patientCPF = '';
                } catch (ignore) {
                  console.warn('Não foi possível limpar o campo CPF automaticamente', ignore);
                }

                pararCamera();
                return false;
              }

              if (!response.data.success) throw new Error(response.data.message || 'Verificação falhou');
              return response.data;
            } catch (err) {
              // Se o axios lançou por causa de 422 (raro com validateStatus), trata igualmente
              const resp = err.response;
              const apiError = String(resp?.data?.error || resp?.data?.message || err.message || '').toLowerCase();
              const noRecordMsg = 'nenhum registro foi encontrado com o id fornecido';

              if (resp?.status === 422 || apiError.includes(noRecordMsg) || resp?.data?.code === 'ERR_CRD_GET_IN_VERIFY') {
                Swal.close();
                await Swal.fire({
                  icon: 'warning',
                  title: 'Identidade não verificada',
                  html: `
                    Não encontramos um registro biométrico correspondente ao seu cadastro.<br><br>
                    Por favor, dirija-se à recepção para prosseguir com o atendimento.
                  `,
                  confirmButtonText: 'OK',
                  allowOutsideClick: false,
                });

                // Limpa o campo de CPF quando o usuário confirma o alerta
                try {
                  const cpfEl = document.getElementById('cpf-value');
                  if (cpfEl) {
                    cpfEl.value = '';
                    cpfEl.focus();
                  }
                  try { cpfDigits = []; } catch (e) { /* ignore */ }
                  if (Array.isArray(window.cpfDigits)) window.cpfDigits = [];
                  if (typeof updateCPFDisplay === 'function') updateCPFDisplay();
                  patientCPF = '';
                } catch (ignore) {
                  console.warn('Não foi possível limpar o campo CPF automaticamente', ignore);
                }

                pararCamera();
                return false;
              }

              Swal.showValidationMessage(`❌ ${err.message || 'Erro na verificação facial.'}`);
              return false;
            }
          }
        }).then(resultVerify => {
          if (resultVerify.isConfirmed && resultVerify.value) {
            Swal.fire({
              icon: 'success',
              title: 'Identidade Confirmada!',
              html: '<p>Reconhecimento facial concluído com sucesso.</p>',
              confirmButtonText: 'Continuar',
              customClass: { confirmButton: 'btn-ok-white' }
            }).then(() => {
              // Obtém o CPF do input ou do estado atual
              const cpfInput = document.getElementById('cpf-value');
              const cpf = cpfInput ? cpfInput.value.replace(/\D/g, '') : '';
              if (cpf && cpf.length === 11) {
                renderAppointments(cpf);
                showScreen('appointments');
              } else {
                showScreen('identify');
              }
            });
          }
        });
      } else {
        cpfInputField.value = '';
        cpfInputField.focus();
      }
    });

  } catch (error) {
    confirmModalOpen = false;
    Swal.close();
    Swal.fire({
      icon: 'error',
      title: 'Erro de conexão',
      text: 'Não foi possível acessar o servidor da Smile Saúde.',
      confirmButtonColor: '#E88C38'
    });
    console.error('Erro na autenticação:', error);
  }
});

// 🔧 Ajuste visual específico para alertas compactos
const styleSwalCompact = document.createElement('style');
styleSwalCompact.textContent = `
  .swal2-popup .swal-title-compact {
    margin-bottom: 0.25rem !important;
  }
  .swal2-popup .swal-html-compact {
    margin-top: 0.25rem !important;
  }
`;
document.head.appendChild(styleSwalCompact);


// === Função global para gerar ticket (Normal ou Preferencial) ===
async function gerarTicket(prioridade) {
  try {
    const senha = generateTicketNumber();
    const agora = new Date();
    const tipoAtendimento = formatarTipoAtendimento(selectedTicketType || 'atendimento-geral');

    // Atualiza informações visuais do ticket
    const ticketPatient = document.getElementById('ticket-patient');
    const ticketDate = document.getElementById('ticket-date');
    const ticketTime = document.getElementById('ticket-time');
    const ticketTypeSpan = document.querySelector('.ticket-type span');

    if (ticketPatient) ticketPatient.textContent = patientName || 'Paciente';
    if (ticketDate) ticketDate.textContent = formatDate(agora);
    if (ticketTime) ticketTime.textContent = formatTimeOnly(agora);
    if (ticketTypeSpan) ticketTypeSpan.textContent = `${prioridade} - ${tipoAtendimento}`;

    // 🔹 Envia ticket para a fila no backend (PHP)
    const formData = new FormData();
    formData.append('acao', 'adicionar_fila');
    formData.append('nome', patientName || 'Paciente');
    formData.append('prioridade', prioridade);
    formData.append('tipo_atendimento', tipoAtendimento);
    formData.append('cpf', patientCPF || '');

    const response = await fetch('ajax/atendimento_ajax.php', {
      method: 'POST',
      body: formData
    });

    const texto = await response.text();
    let dados;
    try {
      dados = JSON.parse(texto);
    } catch {
      dados = { sucesso: false, mensagem: texto };
    }

    if (dados.sucesso) {
      Swal.fire({
        icon: 'success',
        title: 'Senha gerada com sucesso!',
        html: `
    <div style="margin-top:-0.8rem;">
      <p style="font-size:1.05rem; margin:0;">
        Tipo: <strong>${tipoAtendimento}</strong><br>
        Prioridade: <strong>${prioridade}</strong>
      </p>
    </div>
  `,
        confirmButtonText: 'Continuar',
        confirmButtonColor: '#E88C38',
        customClass: {
          title: 'swal-title-compact',
          htmlContainer: 'swal-html-compact'
        }
      }).then(() => {
        showScreen('queue');
      });
    } else {
      await Swal.fire({
        icon: 'warning',
        title: 'Aviso',
        html: `<p style="font-size:1.1rem;">${dados.mensagem || 'Você já possui uma senha ativa.'}</p>`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#E88C38'
      });
      showScreen('welcome');
      resetForm();
    }
  } catch (erro) {
    console.error('❌ Erro ao gerar ticket:', erro);
    Swal.fire({
      icon: 'error',
      title: 'Erro de Conexão',
      text: 'Não foi possível conectar ao servidor. Seu ticket não foi gerado.',
      confirmButtonColor: '#E88C38'
    });
  }
}


// Botão OK da tela de confirmação
document.addEventListener('DOMContentLoaded', function () {
  const confirmButton = document.getElementById('confirm-btn');
  if (confirmButton) {
    confirmButton.addEventListener('click', function (e) {
      e.preventDefault();
      showScreen('welcome');
      resetForm();
    });
  }
});


// New ticket button
if (newTicketBtn) {
  newTicketBtn.addEventListener('click', () => {
    showScreen('welcome');
    resetForm();
  });
}

// Document click handler to reset inactivity timer
document.addEventListener('click', () => {
  resetInactivityTimer();
});

// Initialize the app
function init() {
  // Set initial screen
  showScreen('welcome');
  // Não precisa chamar setInterval aqui, já é chamado em initClock()
}

// Inicializa o app quando o DOM estiver totalmente carregado
document.addEventListener('DOMContentLoaded', () => {
  const cpfInput = document.getElementById('cpf-value');
  const identifyBtn = document.getElementById('identify-btn');

  function updateButtonState() {
    const cpf = cpfInput.value.replace(/\D/g, '');
    if (cpf.length === 11) {
      identifyBtn.classList.add('hover-active');
    } else {
      identifyBtn.classList.remove('hover-active');
    }
  }

  // Atualiza o estado do botão quando o input muda
  cpfInput.addEventListener('input', updateButtonState);

  // Atualiza o estado do botão quando uma tecla do teclado virtual é pressionada
  document.querySelectorAll('.key').forEach(key => {
    key.addEventListener('click', () => {
      // Pequeno atraso para garantir que o valor do input seja atualizado
      setTimeout(updateButtonState, 10);
    });
  });

  // Inicializa o relógio e o app
  initClock();
  init();
});

// Inicia a câmera simulada
function startCamera() {
  const video = document.getElementById('camera-stream');
  if (!video) return;

  navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
      video.srcObject = stream;
    })
    .catch(() => {
      Swal.fire({
        title: 'Erro ao acessar a câmera',
        text: 'Não foi possível ativar a câmera. Verifique as permissões do navegador.',
        icon: 'error'
      });
    });
}

// Simula o reconhecimento facial automático
function simulateFaceRecognition() {
  const cameraBox = document.querySelector('.camera-box');
  if (!cameraBox) return;

  // Mostra mensagem visual na tela
  const overlay = document.createElement('div');
  overlay.style.position = 'absolute';
  overlay.style.top = '50%';
  overlay.style.left = '50%';
  overlay.style.transform = 'translate(-50%, -50%)';
  overlay.style.background = 'rgba(0, 0, 0, 0.6)';
  overlay.style.color = '#fff';
  overlay.style.padding = '1rem 2rem';
  overlay.style.borderRadius = '0.75rem';
  overlay.style.fontSize = '1.1rem';
  overlay.style.fontWeight = '500';
  overlay.innerText = 'Reconhecendo rosto...';
  overlay.id = 'face-overlay';
  cameraBox.appendChild(overlay);

  // Simula o processo de reconhecimento (3 segundos)
  setTimeout(async () => {
    overlay.innerText = 'Rosto reconhecido com sucesso ✅';

    // Mostra mensagem SweetAlert de sucesso
    await Swal.fire({
      title: 'Reconhecimento Facial',
      text: 'Rosto reconhecido com sucesso!',
      icon: 'success',
      confirmButtonText: 'Continuar'
    });

    // Remove sobreposição e segue para a tela de agendamentos
    overlay.remove();
    // Obtém o CPF do input ou do estado atual
    const cpfInput = document.getElementById('cpf-value');
    const cpf = cpfInput ? cpfInput.value.replace(/\D/g, '') : '';
    if (cpf && cpf.length === 11) {
      renderAppointments(cpf);
      showScreen('appointments');
    } else {
      showScreen('identify');
    }
  }, 3000);
}

// ======= Fluxo: Ticket -> CPF -> Confirmação =======

// Simulação de "banco de pacientes" (pode ser substituído por API real)
const pacientesMock = {
  "12345678900": { nome: "João Silva", nascimento: "12/03/1980", plano: "Smile Saúde Ouro" },
  "98765432100": { nome: "Maria Oliveira", nascimento: "25/07/1992", plano: "Smile Saúde Prata" }
};

// Função auxiliar para formatar o texto do tipo de atendimento
function formatarTipoAtendimento(tipo) {
  const mapa = {
    'atendimento-geral': 'Atendimento Geral',
    'agendar-consulta': 'Agendar Consulta',
    'resultados': 'Resultados de Exames',
    'informacoes': 'Informações e Suporte'
  };
  return mapa[tipo] || tipo;
}

// Função de transição entre telas
function mostrarTela(id) {
  document.querySelectorAll('.screen').forEach(screen => screen.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

// Quando clica em qualquer opção de atendimento (Retirar Senha Digital)
// Quando clica em qualquer opção dentro de "Retirar Senha Digital"
document.querySelectorAll('.ticket-option-btn').forEach(button => {
  button.addEventListener('click', () => {
    selectedTicketType = button.dataset.ticketType; // salva o tipo de atendimento
    document.getElementById('service-type').textContent = formatarTipoAtendimento(selectedTicketType);

    // Agora vai direto para o CPF
    showScreen('identify');
  });
});


// === Função para buscar imagem facial via BioDoc ===
async function buscarImagemBioDoc(idCard, procedure = '') {
  console.log("🔹 Chamando BioDoc com ID (CPF):", idCard);

  const TOKEN = 'S2xNaFlKRXIyVEx3WFphMEM5bnRmeGhtcGVkcHVrMjZobm9DLzRxMzFUbTYvNU5pOFI4Zmo4WjFuWkxJL2Qrdg==';
  const URL = 'https://api.biodoc.com.br/api/card/integration/mainimage';

  try {
    const response = await axios.get(URL, {
      headers: {
        'Authorization': `Bearer ${TOKEN}`,
        'Accept': 'application/json'
      },
      params: { idCard, procedure }
    });

    const data = response.data?.data || response.data || {};

    console.log("📦 Retorno BioDoc:", data);
    console.log("📸 Tamanho Base64:", data.base64Image?.length || 0);
    console.log("🔗 mainImage URL:", data.mainImage);
    console.log("🔢 cardStatus:", data.cardStatus);

    if (data.base64Image && data.base64Image.trim() !== '') {
      console.log("✅ Retornando imagem em Base64");
      return `data:image/jpeg;base64,${data.base64Image}`;
    }

    if (data.mainImage && data.mainImage.trim() !== '') {
      console.log("✅ Retornando imagem da URL (mainImage)");
      return data.mainImage;
    }

    console.warn(`⚠️ BioDoc: Nenhuma imagem disponível (cardStatus=${data.cardStatus})`);
    return null;

  } catch (error) {
    console.error("❌ Erro ao buscar imagem BioDoc:", error.response?.data || error.message);
    return null;
  }
}

async function abrirVerificacaoBioDoc(idCard, nome) {
  const TOKEN = "S2xNaFlKRXIyVEx3WFphMEM5bnRmeGhtcGVkcHVrMjZobm9DLzRxMzFUbTYvNU5pOFI4Zmo4WjFuWkxJL2Qrdg=="; // substitua pelo token real
  const URL = "https://api.biodoc.com.br/api"; // produção (use sandbox para testes)

  Swal.fire({
    title: "Verificação de Identidade",
    html: `
      <div id="camera-container" style="width:100%;height:320px;position:relative;background:#000;border-radius:12px;overflow:hidden;">
        <video id="camera-stream" autoplay playsinline style="width:100%;height:100%;object-fit:cover;"></video>
      </div>
      <p style="margin-top:15px;">Posicione o rosto dentro do quadro e clique em "Verificar".</p>
    `,
    confirmButtonText: "Verificar",
    showCancelButton: true,
    cancelButtonText: "Cancelar",
    allowOutsideClick: false,
    didOpen: () => iniciarCamera(),
    willClose: () => pararCamera(),
  }).then(async (res) => {
    if (!res.isConfirmed) {
      pararCamera(); // Garante que a câmera seja parada se cancelar
      return;
    }

    const imageBase64 = await capturarFoto(); // <- já retorna base64 puro
    if (!imageBase64) {
      Swal.fire("Erro", "Não foi possível capturar a imagem.", "error");
      return;
    }

    Swal.fire({
      title: "Enviando para análise...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      // 🔹 corpo correto e limpo (application/json)
      const body = {
        id: String(idCard), // id do cartão cadastrado na BioDoc
        image: imageBase64, // base64 pura
        detail: JSON.stringify({
          origem: "Totem",
          nomePaciente: nome,
          data: new Date().toISOString(),
        }),
      };

      console.log("📤 Enviando para BioDoc:", body);

      const response = await axios.post(`${URL}/integrations/verify`, body, {
        headers: {
          Authorization: `Bearer ${TOKEN}`,
          "Content-Type": "application/json",
        },
      });

      Swal.close();

      if (response.data.success) {

        Swal.fire({
          icon: "success",
          title: "Identidade Confirmada!",
          html: "<p>Reconhecimento facial concluído com sucesso.</p>",
          confirmButtonText: "Continuar",
          customClass: {
            confirmButton: "btn-ok-white" // mantém o estilo existente
          }
        })

      }
    } catch (err) {
      console.error("❌ Erro na requisição BioDoc:", err.response?.data || err.message);
      Swal.fire("Erro", "Não foi possível verificar o paciente.", "error");
    }
  });
}

// Variável global para armazenar o stream da câmera
let cameraStream = null;

async function iniciarCamera() {
  const video = document.getElementById("camera-stream");

  if (!video) {
    console.error('Elemento de vídeo não encontrado');
    throw new Error('Elemento de vídeo não encontrado');
  }

  try {
    // Verifica se o navegador suporta a API de mídia
    if (!navigator.mediaDevices && !navigator.getUserMedia) {
      throw new Error('Seu navegador não suporta acesso à câmera');
    }

    // Configurações da câmera
    const constraints = {
      video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user' // 'user' para câmera frontal, 'environment' para traseira
      },
      audio: false
    };

    // Tenta acessar a câmera usando a API moderna
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
    }
    // Fallback para navegadores mais antigos
    else if (navigator.getUserMedia) {
      cameraStream = await new Promise((resolve, reject) => {
        navigator.getUserMedia(constraints, resolve, reject);
      });
    } else {
      throw new Error('Não foi possível acessar a câmera');
    }

    if (!cameraStream) {
      throw new Error('Stream de mídia não disponível');
    }

    console.log('Acesso à câmera concedido');
    video.srcObject = cameraStream;

    // Retorna uma promessa que resolve quando o vídeo estiver pronto
    return new Promise((resolve) => {
      video.onloadedmetadata = () => {
        video.play().then(() => {
          console.log('Vídeo iniciado com sucesso');
          resolve();
        }).catch(error => {
          console.error('Erro ao reproduzir vídeo:', error);
          throw new Error('Não foi possível reproduzir o vídeo da câmera');
        });
      };
    });

  } catch (error) {
    console.error('Erro ao acessar a câmera:', error);

    // Mensagens de erro mais específicas
    let errorMessage = 'Não foi possível acessar a câmera.';

    if (error.name === 'NotAllowedError') {
      errorMessage = 'Permissão para acessar a câmera foi negada.';
    } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
      errorMessage = 'Nenhuma câmera encontrada no dispositivo.';
    } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
      errorMessage = 'A câmera está sendo usada por outro aplicativo ou ocorreu um erro ao acessá-la.';
    }

    await Swal.fire({
      icon: 'error',
      title: 'Erro na Câmera',
      text: errorMessage,
      confirmButtonText: 'Entendi',
      allowOutsideClick: false
    });

    throw error; // Rejeita a promessa para tratamento adicional
  }
}

function pararCamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
}

async function capturarFoto() {
  const video = document.getElementById("camera-stream");
  const canvas = document.createElement("canvas");
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  // 🔹 Remove prefixo e envia apenas base64 puro
  return canvas.toDataURL("image/jpeg").split(",")[1];
}

async function showFaceGuidanceModal() {
  const stepsHTML = `
    <div style="text-align:center;">
      <h2 style="color:var(--primary-dark);margin-bottom:1rem;font-weight:700;">
        Prepare-se para o Reconhecimento Facial
      </h2>

      <!-- instruções -->
      <div id="face-step"
        style="font-size:1.1rem;color:#444;opacity:0.9;
               text-align:center;
               margin:0 auto 1.5rem auto;
               line-height:1.6;
               max-width:380px;">
        1. Centralize seu rosto na moldura
      </div>

      <!-- ícone facial -->
      <div style="position:relative;display:inline-block;
                  width:150px;height:150px;margin:1rem auto 1.5rem;">
        <i class="fas fa-user-circle"
           style="font-size:5rem;color:var(--primary-dark);line-height:150px;"></i>
        <div class="loading-ring" style="
          position:absolute;top:0;left:0;width:150px;height:150px;
          border:6px solid var(--primary);
          border-top-color:var(--primary-dark);
          border-radius:50%;
          animation:spin 1.2s linear infinite;">
        </div>
      </div>

      <!-- contador regressivo -->
      <p id="countdown"
         style="font-size:1.1rem;color:var(--primary-dark);font-weight:600;
                margin-top:1rem;">
         Iniciando em 5 segundos...
      </p>

      <style>
        @keyframes spin {
          from { transform: rotate(0deg);}
          to { transform: rotate(360deg);}
        }
      </style>
    </div>
  `;

  await Swal.fire({
    html: stepsHTML,
    showConfirmButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false,
    backdrop: 'rgba(0,0,0,0.5)',
    didOpen: () => {
      // mensagens animadas
      const messages = [
        '1. Centralize seu rosto na moldura',
        '2. Mantenha boa iluminação',
        '3. Fique imóvel por alguns segundos'
      ];
      let msgIndex = 0;
      const stepEl = document.getElementById('face-step');
      const messageInterval = setInterval(() => {
        msgIndex = (msgIndex + 1) % messages.length;
        stepEl.textContent = messages[msgIndex];
      }, 2000);

      // contador regressivo
      let seconds = 5;
      const countdownEl = document.getElementById('countdown');
      const countdown = setInterval(() => {
        seconds--;
        countdownEl.textContent = `Iniciando em ${seconds} segundos...`;

        if (seconds <= 0) {
          clearInterval(countdown);
          clearInterval(messageInterval); // Limpa o intervalo de mensagens
          Swal.close(); // fecha o modal automaticamente
          startVerificationModal(); // chama a próxima etapa
        }
      }, 1000);
    },
    willClose: () => {
      // Garante limpeza se o modal for fechado antes do tempo
      const messageInterval = window.messageInterval;
      const countdown = window.countdown;
      if (messageInterval) clearInterval(messageInterval);
      if (countdown) clearInterval(countdown);
    }
  });
}

function startVerificationModal() {
  Swal.fire({
    icon: 'success',
    title: 'Identidade Confirmada!',
    html: '<p>Reconhecimento facial concluído com sucesso.</p>',
    confirmButtonText: 'Continuar',
    customClass: {
      confirmButton: 'btn-ok-white'
    }
  })

}

// === Controle de visibilidade do footer ===
function updateFooterVisibility() {
  const footer = document.querySelector('.footer');
  const welcomeScreen = document.getElementById('welcome-screen');

  // Se a tela de boas-vindas estiver ativa, exibe o footer
  if (welcomeScreen.classList.contains('active')) {
    footer.style.display = 'flex';
  } else {
    footer.style.display = 'none';
  }
}

// Observa mudanças de tela
const observer = new MutationObserver(updateFooterVisibility);
document.querySelectorAll('.screen').forEach(screen => {
  observer.observe(screen, { attributes: true, attributeFilter: ['class'] });
});

// Executa uma vez ao carregar a página
updateFooterVisibility();





// ================= CPF NUMÉRICO (COM DISPLAY ANIMADO) =================

// Seletores principais
const cpfHidden = document.getElementById('cpf-value'); // campo oculto real
const cpfSlots = document.querySelectorAll('.cpf-slot'); // cada caixinha visual
const keys = document.querySelectorAll('.key'); // teclas do teclado numérico

let cpfDigits = []; // guarda apenas os números digitados

// Função para formatar CPF (XXX.XXX.XXX-XX)
function formatCPF(digits) {
  const arr = Array.isArray(digits) ? digits : String(digits).split('');
  const s = arr.join('');
  const part1 = s.slice(0, 3);
  const part2 = s.slice(3, 6);
  const part3 = s.slice(6, 9);
  const part4 = s.slice(9, 11);
  return [part1, part2, part3, part4]
    .filter(Boolean)
    .join('.')
    .replace(/\.(\d{3})$/, '-$1');
}

// Atualiza display visual e campo oculto
function updateCPFDisplay() {
  // Limpa slots
  cpfSlots.forEach(slot => {
    slot.textContent = '';
    slot.classList.remove('pop', 'filled');
  });

  // Preenche slots
  cpfDigits.forEach((num, i) => {
    const slot = cpfSlots[i];
    if (slot) {
      slot.textContent = num;
      slot.classList.add('pop');
      setTimeout(() => slot.classList.add('filled'), 350);
    }
  });

  // Atualiza valor real no hidden input
  if (cpfHidden) cpfHidden.value = formatCPF(cpfDigits);
}

// Manipula teclas do teclado numérico
keys.forEach(key => {
  key.addEventListener('click', e => {
    e.preventDefault();
    const keyValue = key.dataset.key;

    if (!keyValue) return;

    if (keyValue === 'backspace') {
      cpfDigits.pop();
    } else if (keyValue === 'clear') {
      cpfDigits = [];
    } else if (/^\d$/.test(keyValue) && cpfDigits.length < 11) {
      cpfDigits.push(keyValue);
    }

    updateCPFDisplay();
  });
});


// Atualiza o nome do paciente no ticket
function exibirTicketPaciente() {
  const nomePaciente = document.getElementById('confirm-patient-name')?.textContent?.trim() || '';
  const campoTicket = document.getElementById('ticket-patient');

  if (campoTicket && nomePaciente) {
    campoTicket.textContent = nomePaciente;
  } else {
    console.warn('⚠️ Nome do paciente não encontrado para exibir no ticket.');
  }
}

// Chame essa função logo após gerar/exibir a tela do ticket
// Exemplo:
document.getElementById('confirm-btn')?.addEventListener('click', () => {
  // ... seu código que muda para a tela #queue-screen ...
  exibirTicketPaciente();

});

// ==== 🎊 Confete Animado de Aniversário (melhorado) ====
let confettiTimeout;
let confettiAnimationFrame;

function startConfettiAnimation() {
  // 🔹 Cria o canvas sobre toda a tela
  let canvas = document.getElementById('confetti-canvas');
  if (!canvas) {
    canvas = document.createElement('canvas');
    canvas.id = 'confetti-canvas';
    Object.assign(canvas.style, {
      position: 'fixed',
      top: '0',
      left: '0',
      width: '100vw',
      height: '100vh',
      pointerEvents: 'none',
      zIndex: '9999'
    });
    document.body.appendChild(canvas);
  }

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const colors = ['#E88C38', '#FFD166', '#06D6A0', '#118AB2', '#EF476F'];
  const confettiCount = 160;
  const confettis = Array.from({ length: confettiCount }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * -canvas.height,
    size: Math.random() * 12 + 6,
    color: colors[Math.floor(Math.random() * colors.length)],
    speedY: Math.random() * 4 + 2.5,
    speedX: Math.random() * 2 - 1,
    rotation: Math.random() * 360,
    rotationSpeed: Math.random() * 6 - 3,
    opacity: Math.random() * 0.4 + 0.6
  }));

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    confettis.forEach((c) => {
      ctx.save();
      ctx.globalAlpha = c.opacity;
      ctx.translate(c.x, c.y);
      ctx.rotate((c.rotation * Math.PI) / 180);
      ctx.fillStyle = c.color;
      ctx.fillRect(-c.size / 2, -c.size / 2, c.size, c.size);
      ctx.restore();

      c.y += c.speedY;
      c.x += c.speedX;
      c.rotation += c.rotationSpeed;

      if (c.y > canvas.height + 20) {
        c.y = Math.random() * -100;
        c.x = Math.random() * canvas.width;
      }
    });
    confettiAnimationFrame = requestAnimationFrame(draw);
  }

  draw();
}

function stopConfettiAnimation() {
  cancelAnimationFrame(confettiAnimationFrame);
  const canvas = document.getElementById('confetti-canvas');
  if (canvas) canvas.remove();
}

// ====================== FUNÇÃO COMPLETA DE CONFIRMAÇÃO ======================
async function confirmarAgendamentoReal(idAtendimento, chaveBeneficiario, appointments, updateProgress) {
  // Se updateProgress não for uma função, criar uma dummy
  if (typeof updateProgress !== 'function') {
    updateProgress = (msg) => console.log(msg);
  }

  if (!idAtendimento || !chaveBeneficiario) {
    console.error("Parâmetros inválidos para confirmação.");
    return { sucesso: false, msg: "Parâmetros inválidos" };
  }

  try {
    // 🔄 ETAPA 1: CONFIRMAR AGENDAMENTO
    updateProgress("Confirmando agendamento...");
    // ... resto do código permanece igual
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    const response = await fetch("ajax/confirmar_agendamento_ajax.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id_atendimento: Number(idAtendimento),
        chave_beneficiario: Number(chaveBeneficiario)
      })
    });

    const data = await response.json();

    if (!data.sucesso) {
      return data;
    }

    const idx = appointments.findIndex(app => app.id === idAtendimento);
    if (idx !== -1) appointments[idx].isConfirmed = true;

    // ===========================================
    // 🔹 GERAR A GUIA APÓS CONFIRMAÇÃO
    // ===========================================
    try {
      // 🔄 ETAPA 2: GERANDO PRÉ-AUTORIZAÇÃO
      updateProgress("Gerando pré-autorização...");
      await new Promise(resolve => setTimeout(resolve, 1000));

      const dadosAgendamento = data.dados_agendamento || {};
      const executante = dadosAgendamento.nome_profissional;
      const solicitante = dadosAgendamento.nome_profissional;
      const idUnidade = dadosAgendamento.id_local_atendimento;
      const nomeEspecialidade = dadosAgendamento.nome_especialidade;

      const gerarGuiaResponse = await fetch("ajax/gerar_guia_ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          chave_beneficiario: Number(chaveBeneficiario),
          executante: executante,
          solicitante: solicitante,
          id_unidade: idUnidade,
          nome_especialidade: nomeEspecialidade
        })
      });

      const guiaJson = await gerarGuiaResponse.json();
      const respostaApi = guiaJson.resposta_api || {};
      const statusCode = respostaApi.STATUS_CODE;
      const numeroGuia = respostaApi.NUMERO_GUIA;

      // ===========================
      // 🎯 G200 → GUIA GERADA COM SUCESSO
      // ===========================
      if (statusCode === "G200") {
        // 🔄 ETAPA 3: ENCAMINHANDO PARA FILA DO PROFISSIONAL
        updateProgress("Encaminhando para fila do profissional...");
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Envia dados para encaminhar à fila do profissional
        try {
          const encaminharPayload = {
            id_atendimento: Number(idAtendimento),
            chave_beneficiario: Number(chaveBeneficiario),
            numero_guia: Number(numeroGuia)
          };

          const encaminhaResp = await fetch("ajax/encaminha_fila_profissional_ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(encaminharPayload)
          });

          await encaminhaResp.json();

        } catch (errEnc) {
          console.error("Erro ao encaminhar para fila do profissional:", errEnc);
        }
      }

      // Adiciona dados da guia ao retorno
      data.dados_guia = guiaJson;

    } catch (erroGuia) {
      console.error("ERRO AO GERAR GUIA:", erroGuia);
      data.erro_guia = erroGuia.message;
    }

    return data;

  } catch (e) {
    console.error("Erro API de confirmação:", e);
    return { sucesso: false, msg: e.message || "Erro na comunicação com a API" };
  }
}

// === SELEÇÃO DE AGENDAMENTO (TOUCH FRIENDLY e FUNCIONAL) ===
function enableAppointmentSelection() {
  const list = document.getElementById("appointments-list");
  if (!list) return;

  function clearAllSelections() {
    document.querySelectorAll(".appointment-card").forEach(card => {
      card.classList.remove("selected");
      const btn = card.querySelector(".confirm-appointment-btn");
      if (btn) btn.remove();
    });
  }

  list.addEventListener("click", function (e) {
    const card = e.target.closest(".appointment-card");
    if (!card) return;

    if (card.classList.contains('selected')) {
      card.classList.remove('selected');
      const btn = card.querySelector('.confirm-appointment-btn');
      if (btn) btn.remove();
      return;
    }

    clearAllSelections();
    card.classList.add("selected");

    const isConfirmed = card.querySelector('.appointment-status span') ? 
        card.querySelector('.appointment-status span').textContent.includes('Confirmado') : 
        false;

    let confirmBtn = null;
    if (!isConfirmed) {
      confirmBtn = document.createElement("button");
      confirmBtn.className = "confirm-appointment-btn primary-btn";
      confirmBtn.textContent = "Confirmar Agendamento";
      card.appendChild(confirmBtn);

      setTimeout(() => confirmBtn.classList.add("visible"), 10);

      confirmBtn.addEventListener("click", async (event) => {
        event.stopPropagation();
        confirmBtn.disabled = true;
        confirmBtn.textContent = "Confirmando...";

        const idAtendimento = card.dataset.id;
        const appointment = appointments.find(a => a.id == idAtendimento);
        const chaveBeneficiario = window.currentChaveBeneficiario || null;

        // Variável para controlar o texto do loading
        let currentProgress = "Iniciando confirmação...";

        // 🔄 LOADING DINÂMICO
        const loadingSwal = Swal.fire({
          title: 'Processando Confirmação',
          html: `
            <div style="text-align: center; padding: 20px;">
              <div class="swal2-spinner" style="margin: 20px auto;"></div>
              <div id="progress-text" style="margin-top: 20px; font-size: 1.1em; font-weight: 500;">
                ${currentProgress}
              </div>
            </div>
          `,
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => {
            // Atualiza o texto periodicamente para mostrar atividade
            const progressElement = document.getElementById('progress-text');
            if (progressElement) {
              setInterval(() => {
                progressElement.textContent = currentProgress;
              }, 100);
            }
          }
        });

        // Função para atualizar o progresso
        function updateProgress(message) {
          currentProgress = message;
        }

        try {
          // CHAMADA DA FUNÇÃO GLOBAL COM ATUALIZAÇÃO DE PROGRESSO
          const resultado = await confirmarAgendamentoReal(
            idAtendimento, 
            chaveBeneficiario, 
            appointments, 
            updateProgress
          );

          // Finaliza com mensagem de conclusão
          updateProgress("Finalizando processo...");
          await new Promise(resolve => setTimeout(resolve, 500));

          Swal.close();

          if (!resultado.sucesso) {
            await Swal.fire({
              icon: "error",
              title: "Erro na Confirmação",
              text: resultado.msg || "Não foi possível confirmar o agendamento.",
              confirmButtonText: "Entendi"
            });
            confirmBtn.disabled = false;
            confirmBtn.textContent = "Confirmar Agendamento";
            return;
          }

          // Atualiza interface
          const targetCard = document.querySelector(`.appointment-card[data-id="${idAtendimento}"]`);
          if (targetCard) {
            const statusBadge = targetCard.querySelector(".appointment-status");
            if (statusBadge) {
              statusBadge.innerHTML = `
                <span style="background:#4CAF50; color:white; padding:2px 8px; border-radius:12px; font-size:0.8em; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                  Confirmado
                </span>
              `;
            }

            const btn = targetCard.querySelector(".confirm-appointment-btn");
            if (btn) btn.remove();
          }

          // Modal de sucesso final
          await Swal.fire({
            icon: "success",
            title: "Check-in Realizado!",
            html: `
              <div style="text-align: center;">
                <h3 style="color: #4CAF50; margin-bottom: 1rem;">Presença Confirmada com Sucesso</h3>
                <p style="font-size:1.1rem; color:#666; margin-bottom: 1.5rem;">
                  Sua presença foi registrada e você já está na fila para o atendimento.
                </p>
                <p style="font-size:1rem; color:#888;">
                  Por favor, aguarde ser chamado(a) no local indicado.
                </p>
              </div>
            `,
            confirmButtonText: "Entendi",
            allowOutsideClick: false,
            allowEscapeKey: false
          });

          // Atualiza tela de confirmação e navega
          selectedAppointment = appointment;
          updateConfirmationScreen();
          showScreen('confirmation');

        } catch (error) {
          Swal.close();
          await Swal.fire({
            icon: "error",
            title: "Erro no Processamento",
            text: "Ocorreu um erro durante a confirmação. Tente novamente.",
            confirmButtonText: "Entendi"
          });
          confirmBtn.disabled = false;
          confirmBtn.textContent = "Confirmar Agendamento";
          return;
        }

        confirmBtn.disabled = false;
        confirmBtn.textContent = "Confirmar Agendamento";
      });
    }
  });

  document.addEventListener('slideChanged', clearAllSelections);
}

document.addEventListener("DOMContentLoaded", enableAppointmentSelection);

//===========================================

// === Controle Global de Inatividade (10s sem interação) ===
const INACTIVITY_TIME = 50000; // 50 segundos
const welcomeScreen = document.getElementById("welcome-screen");
// ⚠️ NÃO redeclare o inactivityTimer — já existe na linha 37
let isModalOpen = false;

// Função para voltar à tela inicial
function goToWelcomeScreen() {
  // Impede fechamento se modal (SweetAlert2) estiver aberto
  if (isModalOpen) {
    console.log("🧠Modal ativo — aguardando fechamento para voltar.");
    return;
  }

  // Fecha SweetAlert2 se ainda estiver visível
  if (typeof Swal !== "undefined" && Swal.isVisible()) {
    Swal.close();
    console.log("⚠️ Modal SweetAlert fechado automaticamente por inatividade.");
  }

  // Volta para tela inicial
  const activeScreen = document.querySelector(".screen.active");
  if (activeScreen && activeScreen.id !== "welcome-screen") {
    activeScreen.classList.remove("active");
    welcomeScreen.classList.add("active");

    // Esconde elementos extras
    document.querySelectorAll(".side-card.visible").forEach(card => card.classList.remove("visible"));
    document.querySelector(".footer")?.removeAttribute("style");

    console.log("⏱ Retornou automaticamente para a tela inicial.");
  }
}

// Cria/zera o temporizador global
function resetInactivityTimer() {
  clearTimeout(inactivityTimer);

  // Só reinicia se não houver modal aberto
  if (!isModalOpen) {
    inactivityTimer = setTimeout(goToWelcomeScreen, INACTIVITY_TIME);
  }
}

// Detecta interações
["click", "mousemove", "touchstart", "keydown"].forEach(evt => {
  document.addEventListener(evt, resetInactivityTimer, { passive: true });
});

// Inicializa
resetInactivityTimer();

// === Integração automática com SweetAlert2 ===
if (typeof Swal !== "undefined") {
  const originalFire = Swal.fire;
  Swal.fire = async function (...args) {
    isModalOpen = true; // pausa o temporizador enquanto modal estiver aberto
    clearTimeout(inactivityTimer);
    console.log("📸 SweetAlert aberto — pausa do temporizador de inatividade.");

    const result = await originalFire.apply(this, args);

    // Ao fechar o modal, retoma o temporizador
    isModalOpen = false;
    console.log("✅ SweetAlert fechado — retomando controle de inatividade.");
    resetInactivityTimer();

    return result;
  };
}

function carregarFila() {
  fetch('ajax/atendimento_ajax.php?action=carregar_fila')
    .then(response => response.json())
    .then(data => {
      const filaAtual = document.getElementById('fila-atual');
      if (filaAtual) {
        filaAtual.innerHTML = data.html || '<p>Nenhum atendimento na fila no momento.</p>';
      }
    })
    .catch(error => console.error('Erro ao carregar fila:', error));
}

// ====================== CARROSSEL DE AGENDAMENTOS ======================
// Funções do carrossel de agendamentos
function initCarousel() {
  const wrapper = document.querySelector('.appointments-wrapper');
  const slides = document.querySelectorAll('.appointment-slide');
  const dotsContainer = document.querySelector('.carousel-dots');

  if (!wrapper || !slides.length) return;

  totalSlides = slides.length;
  currentSlide = 0;

  // Limpa os dots existentes
  if (dotsContainer) {
    dotsContainer.innerHTML = '';

    // Cria os dots de navegação
    for (let i = 0; i < totalSlides; i++) {
      const dot = document.createElement('div');
      dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', () => goToSlide(i));
      dotsContainer.appendChild(dot);
    }
  }

  updateCarousel();
}

function updateCarousel() {
  const wrapper = document.querySelector('.appointments-wrapper');
  const dots = document.querySelectorAll('.carousel-dot');

  if (!wrapper) return;

  // Atualiza a posição do wrapper
  wrapper.style.transform = `translateX(-${currentSlide * 100}%)`;

  // Dispara evento de mudança de slide
  const slideChangedEvent = new Event('slideChanged');
  document.dispatchEvent(slideChangedEvent);

  // Atualiza os dots ativos
  dots.forEach((dot, index) => {
    if (index === currentSlide) {
      dot.classList.add('active');
    } else {
      dot.classList.remove('active');
    }
  });

  // Atualiza a visibilidade dos botões de navegação
  const prevBtn = document.querySelector('.carousel-btn.prev');
  const nextBtn = document.querySelector('.carousel-btn.next');

  if (prevBtn) prevBtn.style.display = currentSlide === 0 ? 'none' : 'flex';
  if (nextBtn) nextBtn.style.display = currentSlide >= totalSlides - 1 ? 'none' : 'flex';
}

function nextSlide() {
  if (currentSlide < totalSlides - 1) {
    currentSlide++;
    updateCarousel();
  }
}

function prevSlide() {
  if (currentSlide > 0) {
    currentSlide--;
    updateCarousel();
  }
}

function goToSlide(slideIndex) {
  if (slideIndex >= 0 && slideIndex < totalSlides) {
    currentSlide = slideIndex;
    updateCarousel();
  }
}

// Adiciona event listeners para os botões de navegação
document.addEventListener('DOMContentLoaded', () => {
  // Botões de navegação do carrossel
  document.addEventListener('click', (e) => {
    if (e.target.closest('.carousel-btn.next')) {
      nextSlide();
    } else if (e.target.closest('.carousel-btn.prev')) {
      prevSlide();
    }
  });

  // Inicializa o carrossel quando a tela de agendamentos for mostrada
  document.addEventListener('screenChanged', (e) => {
    if (e.detail.screen === 'appointments') {
      setTimeout(initCarousel, 100); // Pequeno delay para garantir que o DOM foi atualizado
    }
  });
});

document.addEventListener("click", function (e) {
  if (e.target.closest("#btn-sair")) {
    window.location.href = "entrada.php";
  }
});
