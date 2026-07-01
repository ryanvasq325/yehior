// =============================================================================
// report.js — Yehi Or
// Responsável por toda a interatividade da página de novo reporte:
//   1. Máscara e busca de CEP via ViaCEP
//   2. Contador de caracteres da descrição
//   3. Validação do formulário antes do envio
//   4. Mapa Leaflet (satélite Esri) para marcar a localização do poste
//   5. GPS do dispositivo via navigator.geolocation
//   6. Reverse geocoding via Nominatim (coordenadas → endereço)
// =============================================================================

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// =============================================================================
// 1. MÁSCARA E BUSCA DE CEP
// Aplica a máscara "00000-000" no campo de CEP usando a biblioteca Inputmask.
// Quando o usuário sai do campo (blur), busca o endereço na API ViaCEP e
// preenche automaticamente os campos de logradouro e bairro.
// =============================================================================
const cepInput = document.getElementById('cep');

if (cepInput) {
  // Aplica máscara de CEP (biblioteca Inputmask já carregada globalmente)
  Inputmask('99999-999').mask(cepInput);

  cepInput.addEventListener('blur', async () => {
    // Remove tudo que não for número para validar e enviar à API
    const cep  = cepInput.value.replace(/\D/g, '');
    const info = document.getElementById('cep-info');

    // Ignora se o CEP estiver incompleto
    if (cep.length !== 8) return;

    info.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Buscando CEP...';

    try {
      // Consulta a API pública ViaCEP (gratuita, sem chave)
      const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);

      // Verifica se a API respondeu com sucesso antes de tentar interpretar o JSON
      if (!res.ok) throw new Error('Falha na resposta da API ViaCEP');

      const data = await res.json();

      if (data.erro) {
        // CEP válido no formato mas inexistente na base dos Correios
        info.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>CEP não encontrado.</span>';
      } else {
        // Preenche os campos de endereço com os dados retornados
        document.getElementById('address').value  = data.logradouro ?? '';
        document.getElementById('district').value = data.bairro     ?? '';

        info.innerHTML = `<i class="fa-solid fa-circle-check me-1" style="color:var(--amarelo)"></i>
          ${data.logradouro ? data.logradouro + ', ' : ''}${data.bairro}, ${data.localidade} — ${data.uf}`;
      }
    } catch {
      // Falha de rede, API indisponível ou resposta com erro HTTP
      info.innerHTML = '<span class="text-warning"><i class="fa-solid fa-wifi me-1"></i>Não foi possível verificar o CEP.</span>';
    }
  });
}

// =============================================================================
// 2. CONTADOR DE CARACTERES DA DESCRIÇÃO
// Atualiza em tempo real o contador "X / 255" abaixo do textarea.
// Muda para vermelho quando o texto se aproxima do limite.
// =============================================================================
const descricao = document.getElementById('descricao');
const charCount = document.getElementById('char-count');

if (descricao && charCount) {
  const update = () => {
    const len = descricao.value.length;
    charCount.textContent = `${len} / 255`;
    // Fica vermelho a partir de 240 caracteres para alertar o usuário
    charCount.classList.toggle('text-danger',    len >= 240);
    charCount.classList.toggle('text-secondary', len < 240);
  };
  descricao.addEventListener('input', update);
  update(); // Inicializa o contador ao carregar a página
}

// =============================================================================
// 3. VALIDAÇÃO DO FORMULÁRIO
// Impede o envio se nenhum tipo de problema estiver selecionado.
// Também evita duplo envio (clique duplo, conexão lenta) desabilitando
// o botão assim que o formulário é validado com sucesso.
// =============================================================================
const formEl = document.querySelector('form'); // "formEl" para evitar conflito de nome

if (formEl) {
  formEl.addEventListener('submit', (e) => {
    const selecionado = formEl.querySelector('input[name="id_tipo_problema"]:checked');
    const errorEl     = document.getElementById('problema-error');

    if (!selecionado) {
      // Bloqueia o envio e exibe o erro
      e.preventDefault();
      errorEl.textContent = 'Selecione o tipo do problema.';
      errorEl.style.removeProperty('display');
      // Rola suavemente até o campo para o usuário ver o erro
      errorEl.closest('.mb-4').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    errorEl.style.display = 'none';

    // Desabilita o botão de envio para evitar reportes duplicados
    // caso o usuário clique mais de uma vez ou a conexão esteja lenta
    const btnSubmit = formEl.querySelector('button[type="submit"]');
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Enviando...';
    }
  });
}

// =============================================================================
// 6. REVERSE GEOCODING — Nominatim (OpenStreetMap)
// Converte coordenadas (lat, lng) em endereço legível.
// Chamado automaticamente após o usuário confirmar o pin no mapa.
// API gratuita e sem chave — respeita o limite de 1 req/seg.
// =============================================================================
async function preencherEnderecoDoMapa(lat, lng) {
  const cepInfo = document.getElementById('cep-info');

  cepInfo.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Buscando endereço...';

  try {
    // Nominatim reverse geocoding — retorna JSON com dados de endereço
    // Accept-Language: pt-BR para nomes em português
    const res = await fetch(
      `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`,
      { headers: { 'Accept-Language': 'pt-BR' } }
    );

    // Nominatim retorna 429 quando o limite de 1 req/seg é excedido —
    // sem essa checagem o código tentaria interpretar a resposta de erro como sucesso
    if (!res.ok) throw new Error('Falha na resposta da API Nominatim');

    const data = await res.json();
    const addr = data.address ?? {};

    // O Nominatim retorna diferentes campos dependendo do tipo de via.
    // Tentamos do mais específico para o mais genérico.
    const logradouro = addr.road ?? addr.pedestrian ?? addr.path ?? '';
    const bairro     = addr.suburb ?? addr.neighbourhood ?? addr.quarter ?? addr.village ?? '';
    const cep        = (addr.postcode ?? '').replace(/\D/g, '');

    // Preenche os campos do formulário somente se o valor existir
    if (logradouro) document.getElementById('address').value  = logradouro.toUpperCase();
    if (bairro)     document.getElementById('district').value = bairro.toUpperCase();
    if (cep.length === 8) {
      // Formata o CEP no padrão 00000-000
      document.getElementById('cep').value = cep.replace(/(\d{5})(\d{3})/, '$1-$2');
    }

    cepInfo.innerHTML = `<i class="fa-solid fa-circle-check me-1" style="color:var(--amarelo)"></i>
      Endereço preenchido pelo mapa. Confira e ajuste se necessário.`;

  } catch {
    cepInfo.innerHTML = '<span class="text-warning"><i class="fa-solid fa-wifi me-1"></i>Não foi possível buscar o endereço.</span>';
  }
}

// =============================================================================
// 4 + 5. MAPA LEAFLET + GPS DO DISPOSITIVO
// Abre um modal com mapa interativo em modo SATÉLITE (Esri World Imagery).
// O cidadão pode:
//   A) Clicar no mapa para marcar o local do poste manualmente
//   B) Clicar no botão GPS para centralizar automaticamente na localização atual
// O pin amarelo pode ser arrastado para ajuste fino.
// Ao confirmar, lat/lng são salvos em inputs hidden e o endereço é preenchido.
// =============================================================================
const btnAbrirMapa    = document.getElementById('btn-abrir-mapa');     // Botão que abre o modal
const btnConfirmar    = document.getElementById('btn-confirmar-local'); // Botão de confirmar dentro do modal
const btnClear        = document.getElementById('btn-clear-map');       // Botão para remover a localização
const btnGps          = document.getElementById('btn-gps');             // Botão GPS flutuante no mapa
const inputLat        = document.getElementById('latitude');            // Input hidden — enviado com o form
const inputLng        = document.getElementById('longitude');           // Input hidden — enviado com o form
const mapPreview      = document.getElementById('map-preview');         // Caixa de preview exibida após confirmar
const mapPreviewText  = document.getElementById('map-preview-text');    // Texto com as coords dentro do preview
const modalCoordsInfo = document.getElementById('modal-coords-info');  // Info de coords no rodapé do modal
const modalEl         = document.getElementById('modalMapa');           // Elemento do modal Bootstrap

if (modalEl && btnAbrirMapa) {
  let mapaLeaflet = null;  // Instância do mapa Leaflet (criada uma única vez)
  let marker      = null;  // Marcador do poste no mapa
  let circleGps   = null;  // Círculo de precisão do GPS
  let pendingLat  = null;  // Latitude pendente (ainda não confirmada)
  let pendingLng  = null;  // Longitude pendente (ainda não confirmada)

  // Pin amarelo com círculo escuro no centro — identidade visual do Yehi Or
  const pinIcon = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
      <path d="M16 0C7.16 0 0 7.16 0 16c0 11 16 26 16 26S32 27 32 16C32 7.16 24.84 0 16 0z" fill="#F5C518"/>
      <circle cx="16" cy="16" r="7" fill="#0D0F1A"/>
    </svg>`,
    className: '',
    iconSize:    [32, 42],  // Tamanho visual do ícone
    iconAnchor:  [16, 42],  // Ponto de ancoragem: base do pin
    popupAnchor: [0, -44],  // Posição do popup acima do pin
  });

  // Atualiza as informações de coordenadas no rodapé do modal
  function atualizarInfoModal() {
    if (pendingLat && pendingLng) {
      modalCoordsInfo.innerHTML =
        `<i class="fa-solid fa-location-dot me-1" style="color:#F5C518"></i>
        Lat: <strong>${pendingLat}</strong> &nbsp; Lng: <strong>${pendingLng}</strong>
        &nbsp;·&nbsp; <span class="text-warning">Arraste o pin para ajustar.</span>`;
      // Habilita o botão de confirmar somente quando há coords pendentes
      btnConfirmar.disabled = false;
    }
  }

  // Coloca ou move o marcador no mapa e atualiza coords pendentes
  function colocarMarcador(latlng) {
    pendingLat = latlng.lat.toFixed(7); // 7 casas decimais ≈ precisão de ~1cm
    pendingLng = latlng.lng.toFixed(7);

    if (marker) {
      // Move o marcador existente em vez de criar um novo
      marker.setLatLng(latlng);
    } else {
      // Cria o marcador pela primeira vez, com suporte a arrastar
      marker = L.marker(latlng, { icon: pinIcon, draggable: true }).addTo(mapaLeaflet);

      // Atualiza as coords ao final do arrasto
      marker.on('dragend', () => {
        const pos  = marker.getLatLng();
        pendingLat = pos.lat.toFixed(7);
        pendingLng = pos.lng.toFixed(7);
        atualizarInfoModal();
      });
    }

    atualizarInfoModal();
  }

  // ── Inicialização do mapa ──────────────────────────────────────
  // O mapa só é criado quando o modal é aberto pela primeira vez.
  // Isso evita renderizar o mapa enquanto ele está oculto (causa bugs de tamanho).
  modalEl.addEventListener('shown.bs.modal', () => {
    if (!mapaLeaflet) {
      // Centro padrão: Cacoal - RO. Altere para a cidade do seu município.
      let defaultLat  = -11.4370;
      let defaultLng  = -61.4470;
      let defaultZoom = 14;

      // Se o usuário já tinha marcado uma localização antes, centraliza nela
      if (inputLat.value && inputLng.value) {
        defaultLat  = parseFloat(inputLat.value);
        defaultLng  = parseFloat(inputLng.value);
        defaultZoom = 17; // Zoom mais próximo para facilitar o ajuste
      }

      // Inicializa o mapa Leaflet no div #report-map
      mapaLeaflet = L.map('report-map').setView([defaultLat, defaultLng], defaultZoom);

      // Camada satélite Esri World Imagery — gratuita, sem chave de API
      // maxNativeZoom: 17 — o Esri não tem tiles de altíssima resolução em
      // todas as regiões; a partir do zoom 18+ o Leaflet passa a fazer
      // upscale do último tile real em vez de mostrar o placeholder cinza
      // "Map data not yet available". Ajuste esse valor (16-18) conforme
      // a cobertura disponível na sua cidade.
      const satelite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
          maxZoom: 19,
          maxNativeZoom: 17,
          attribution: '© <a href="https://www.esri.com">Esri</a> — Esri, Maxar, Earthstar Geographics'
        }
      );

      // Camada de ruas OpenStreetMap — fallback quando o satélite não tiver cobertura
      const ruas = L.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        { maxZoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }
      );

      // Começa com satélite ativo
      satelite.addTo(mapaLeaflet);

      // Controle de camadas: permite alternar entre satélite e ruas
      L.control.layers(
        { '🛰️ Satélite': satelite, '🗺️ Ruas': ruas },
        {},
        { position: 'topleft', collapsed: false }
      ).addTo(mapaLeaflet);

      // Clique no mapa → coloca/move o pin
      mapaLeaflet.on('click', (e) => colocarMarcador(e.latlng));

      // Restaura o pin caso o usuário já tivesse confirmado antes e reabriu o modal
      if (inputLat.value && inputLng.value) {
        colocarMarcador(L.latLng(parseFloat(inputLat.value), parseFloat(inputLng.value)));
      }

      // A animação CSS do modal pode renderizar os tiles com tamanho errado
      // mesmo na primeira abertura (o container ainda está em transição), não só nas seguintes
      setTimeout(() => mapaLeaflet.invalidateSize(), 100);
    } else {
      // Mapa já existe: recalcula o tamanho pois o modal tem animação CSS.
      // Sem isso o mapa fica "quebrado" visualmente ao reabrir.
      setTimeout(() => mapaLeaflet.invalidateSize(), 50);
    }
  });

  // ── Botão GPS ──────────────────────────────────────────────────
  // Usa navigator.geolocation (API nativa do browser) para obter
  // a posição atual do dispositivo e centralizar o mapa nela.
  if (btnGps) {
    btnGps.addEventListener('click', () => {
      if (!navigator.geolocation) {
        Swal.fire({
          icon: 'error',
          title: 'Não disponível',
          text: 'Seu navegador não suporta geolocalização.',
          confirmButtonColor: '#F5C518',
        });
        return;
      }

      // Feedback visual: ícone girando enquanto busca o GPS
      btnGps.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      btnGps.disabled  = true;

      navigator.geolocation.getCurrentPosition(
        // Sucesso: posição obtida
        (position) => {
          const lat      = position.coords.latitude;
          const lng      = position.coords.longitude;
          const accuracy = position.coords.accuracy; // Precisão em metros

          // Centraliza o mapa na posição atual com zoom próximo
          mapaLeaflet.setView([lat, lng], 18);

          // Remove o círculo de precisão anterior se existir
          if (circleGps) mapaLeaflet.removeLayer(circleGps);

          // Desenha círculo azul mostrando a margem de precisão do GPS
          circleGps = L.circle([lat, lng], {
            radius:      accuracy,
            color:       '#4A90D9',
            fillColor:   '#4A90D9',
            fillOpacity: 0.15,
            weight:      1,
          }).addTo(mapaLeaflet);

          // Coloca o pin na posição GPS automaticamente
          colocarMarcador(L.latLng(lat, lng));

          // Restaura o ícone do botão
          btnGps.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
          btnGps.disabled  = false;
        },

        // Erro: permissão negada ou GPS indisponível
        (error) => {
          btnGps.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
          btnGps.disabled  = false;

          // Mensagens amigáveis para cada tipo de erro
          const mensagens = {
            1: 'Permissão de localização negada. Habilite o GPS nas configurações do navegador.',
            2: 'Não foi possível determinar sua localização. Verifique o GPS.',
            3: 'Tempo esgotado ao buscar localização. Tente novamente.',
          };

          Swal.fire({
            icon: 'warning',
            title: 'Ops!',
            text: mensagens[error.code] ?? 'Erro ao obter localização.',
            confirmButtonColor: '#F5C518',
          });
        },

        // Opções: alta precisão, timeout de 10s, sem cache
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  // ── Confirmar localização ──────────────────────────────────────
  // Salva as coords nos inputs hidden, fecha o modal e
  // dispara o reverse geocoding para preencher os campos de endereço.
  btnConfirmar.addEventListener('click', async () => {
    if (!pendingLat || !pendingLng) return;

    // Persiste as coordenadas que serão enviadas com o formulário
    inputLat.value = pendingLat;
    inputLng.value = pendingLng;

    // Exibe o preview da localização confirmada abaixo do botão
    mapPreviewText.textContent  = `Localização marcada: ${pendingLat}, ${pendingLng}`;
    mapPreview.style.display    = 'flex';
    mapPreview.style.alignItems = 'center';

    // Fecha o modal
    bootstrap.Modal.getInstance(modalEl).hide();

    // Busca e preenche o endereço automaticamente pelas coordenadas
    await preencherEnderecoDoMapa(pendingLat, pendingLng);
  });

  // ── Abrir modal ────────────────────────────────────────────────
  btnAbrirMapa.addEventListener('click', () => {
    new bootstrap.Modal(modalEl).show();
  });

  // ── Remover localização ────────────────────────────────────────
  // Limpa tudo: inputs hidden, marcador no mapa, círculo GPS e preview.
  btnClear.addEventListener('click', () => {
    inputLat.value = '';
    inputLng.value = '';
    pendingLat     = null;
    pendingLng     = null;

    mapPreview.style.display = 'none';

    // Remove o marcador e o círculo GPS do mapa
    if (marker    && mapaLeaflet) { mapaLeaflet.removeLayer(marker);    marker    = null; }
    if (circleGps && mapaLeaflet) { mapaLeaflet.removeLayer(circleGps); circleGps = null; }

    // Desabilita o botão de confirmar
    btnConfirmar.disabled = true;
    modalCoordsInfo.textContent = 'Nenhuma localização marcada ainda.';

    // Restaura o texto informativo do campo CEP
    const cepInfo = document.getElementById('cep-info');
    if (cepInfo) cepInfo.textContent = 'Digite o CEP do local com o problema.';
  });
}