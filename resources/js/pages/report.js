// report.js — Yehi Or
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// ── Máscara de CEP via Inputmask ───────────────────────────────
const cepInput = document.getElementById('cep');

if (cepInput) {
  Inputmask('99999-999').mask(cepInput);

  cepInput.addEventListener('blur', async () => {
    const cep  = cepInput.value.replace(/\D/g, '');
    const info = document.getElementById('cep-info');

    if (cep.length !== 8) return;

    info.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Buscando CEP...';

    try {
      const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const data = await res.json();

      if (data.erro) {
        info.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>CEP não encontrado.</span>';
      } else {
        info.innerHTML = `<i class="fa-solid fa-circle-check me-1" style="color:var(--amarelo)"></i>
          ${data.logradouro ? data.logradouro + ', ' : ''}${data.bairro}, ${data.localidade} — ${data.uf}`;
      }
    } catch {
      info.innerHTML = '<span class="text-warning"><i class="fa-solid fa-wifi me-1"></i>Não foi possível verificar o CEP.</span>';
    }
  });
}

// ── Contador de caracteres da descrição ────────────────────────
const descricao = document.getElementById('descricao');
const charCount = document.getElementById('char-count');

if (descricao && charCount) {
  const update = () => {
    const len = descricao.value.length;
    charCount.textContent = `${len} / 255`;
    charCount.classList.toggle('text-danger',   len >= 240);
    charCount.classList.toggle('text-secondary', len < 240);
  };
  descricao.addEventListener('input', update);
  update();
}

// ── Validação do tipo de problema antes do submit ──────────────
const form = document.querySelector('form');

if (form) {
  form.addEventListener('submit', (e) => {
    const selecionado = form.querySelector('input[name="id_tipo_problema"]:checked');
    const errorEl     = document.getElementById('problema-error');

    if (!selecionado) {
      e.preventDefault();
      errorEl.textContent = 'Selecione o tipo do problema.';
      errorEl.style.removeProperty('display');
      errorEl.closest('.mb-4').scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      errorEl.style.display = 'none';
    }
  });
}

// ── Mapa Leaflet para marcar localização ───────────────────────
const btnAbrirMapa    = document.getElementById('btn-abrir-mapa');
const btnConfirmar    = document.getElementById('btn-confirmar-local');
const btnClear        = document.getElementById('btn-clear-map');
const inputLat        = document.getElementById('latitude');
const inputLng        = document.getElementById('longitude');
const mapPreview      = document.getElementById('map-preview');
const mapPreviewText  = document.getElementById('map-preview-text');
const modalCoordsInfo = document.getElementById('modal-coords-info');
const modalEl         = document.getElementById('modalMapa');

if (modalEl && btnAbrirMapa) {
  let map        = null;
  let marker     = null;
  let pendingLat = null;
  let pendingLng = null;

  // Ícone pin amarelo
  const pinIcon = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
      <path d="M16 0C7.16 0 0 7.16 0 16c0 11 16 26 16 26S32 27 32 16C32 7.16 24.84 0 16 0z" fill="#F5C518"/>
      <circle cx="16" cy="16" r="7" fill="#0D0F1A"/>
    </svg>`,
    className: '',
    iconSize: [32, 42],
    iconAnchor: [16, 42],
    popupAnchor: [0, -44],
  });

  function atualizarInfoModal() {
    if (pendingLat && pendingLng) {
      modalCoordsInfo.innerHTML =
        `<i class="fa-solid fa-location-dot me-1" style="color:#F5C518"></i>
        Lat: <strong>${pendingLat}</strong> &nbsp; Lng: <strong>${pendingLng}</strong>
        &nbsp;·&nbsp; <span class="text-warning">Arraste o pin para ajustar.</span>`;
      btnConfirmar.disabled = false;
    }
  }

  // Inicializa o mapa quando o modal abre
  modalEl.addEventListener('shown.bs.modal', () => {
    if (!map) {
      let defaultLat  = -11.4370;
      let defaultLng  = -61.4470;
      let defaultZoom = 14;

      if (inputLat.value && inputLng.value) {
        defaultLat  = parseFloat(inputLat.value);
        defaultLng  = parseFloat(inputLng.value);
        defaultZoom = 17;
      }

      map = L.map('report-map').setView([defaultLat, defaultLng], defaultZoom);

      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      }).addTo(map);

      map.on('click', (e) => {
        pendingLat = e.latlng.lat.toFixed(7);
        pendingLng = e.latlng.lng.toFixed(7);

        if (marker) {
          marker.setLatLng(e.latlng);
        } else {
          marker = L.marker(e.latlng, { icon: pinIcon, draggable: true }).addTo(map);
          marker.on('dragend', () => {
            const pos = marker.getLatLng();
            pendingLat = pos.lat.toFixed(7);
            pendingLng = pos.lng.toFixed(7);
            atualizarInfoModal();
          });
        }

        atualizarInfoModal();
      });

      // Restaura pin se já tinha coords salvas
      if (inputLat.value && inputLng.value) {
        const pos = L.latLng(parseFloat(inputLat.value), parseFloat(inputLng.value));
        pendingLat = inputLat.value;
        pendingLng = inputLng.value;
        marker = L.marker(pos, { icon: pinIcon, draggable: true }).addTo(map);
        marker.on('dragend', () => {
          const p = marker.getLatLng();
          pendingLat = p.lat.toFixed(7);
          pendingLng = p.lng.toFixed(7);
          atualizarInfoModal();
        });
        atualizarInfoModal();
      }
    } else {
      setTimeout(() => map.invalidateSize(), 50);
    }
  });

  // Confirmar localização
  btnConfirmar.addEventListener('click', () => {
    if (!pendingLat || !pendingLng) return;

    inputLat.value = pendingLat;
    inputLng.value = pendingLng;

    mapPreviewText.textContent = `Localização marcada: ${pendingLat}, ${pendingLng}`;
    mapPreview.style.display = 'flex';
    mapPreview.style.alignItems = 'center';

    bootstrap.Modal.getInstance(modalEl).hide();
  });

  // Abrir modal
  btnAbrirMapa.addEventListener('click', () => {
    new bootstrap.Modal(modalEl).show();
  });

  // Remover localização
  btnClear.addEventListener('click', () => {
    inputLat.value = '';
    inputLng.value = '';
    pendingLat = null;
    pendingLng = null;
    mapPreview.style.display = 'none';

    if (marker && map) {
      map.removeLayer(marker);
      marker = null;
    }

    btnConfirmar.disabled = true;
    modalCoordsInfo.textContent = 'Nenhuma localização marcada ainda.';
  });
}