import Requests from "../components/requests.js";
import Validate from "../components/validate.js";
import DataTables from '../components/data-tables.js';


const table = DataTables.SetId('table-reports').setRequestVariables([]).post('/report/listingdata');
// Mascara de CEP no filtro
const cepFiltro = document.querySelector('input[name="cep"]');
if (cepFiltro) {
  Inputmask('99999-999').mask(cepFiltro);
}

// Popular modal de detalhes
const modalEl = document.getElementById('modalReport');
if (modalEl) {
  modalEl.addEventListener('show.bs.modal', (e) => {
    const btn      = e.relatedTarget;
    const resolvido = btn.dataset.resolvido === '1';

    document.getElementById('m-id').textContent      = '#' + btn.dataset.id;
    document.getElementById('m-cidadao').textContent = btn.dataset.cidadao;
    document.getElementById('m-tipo').textContent    = btn.dataset.tipo;
    document.getElementById('m-cep').textContent     = btn.dataset.cep;
    document.getElementById('m-criado').textContent  = btn.dataset.criado;
    document.getElementById('m-descricao').textContent = btn.dataset.descricao;

    const statusEl = document.getElementById('m-status');
    statusEl.innerHTML = resolvido
      ? '<span class="badge text-bg-success"><i class="fa-solid fa-circle-check me-1"></i>Resolvido</span>'
      : '<span class="badge text-bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Pendente</span>';
  });
}
async function deleteReport() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formReport').post('/admin/delete');
        return response;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error}`,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}

async function ShowModal(id) {
    Id.value = id;
    Swal.fire({
        title: "Atenção!",
        text: "Deseja realmente excluir este registro?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Excluir"
    }).then(async (result) => {
        if (result.isConfirmed) {
            const response = await deleteReport();
            if (!response.status) {
                Swal.fire({
                    title: "Erro!",
                    text: response.mesg,
                    icon: "error",
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }
            Swal.fire({
                title: "Removido!",
                text: "Registro excluído com sucesso.",
                icon: "success",
                timer: 2000,
                timerProgressBar: true
            }).then(async () => {
                table.ajax.reload();
            });
        }
    });
}
// =============================================================================
// Ordem de Serviço — Mapa Leaflet + Rota OSRM
// =============================================================================
(function () {

  // ── Referências DOM ──────────────────────────────────────────
  const modalOS  = document.getElementById('modalOS');
  const btnOsGps = document.getElementById('btn-os-gps');
  if (!modalOS) return;

  let osMap       = null;   // Instância do mapa Leaflet
  let pinPoste    = null;   // Marcador do poste
  let pinEquipe   = null;   // Marcador da equipe/técnico
  let rotaLayer   = null;   // Polyline da rota OSRM
  let posteLat    = null;   // Lat do poste (vem do data-* do botão)
  let posteLng    = null;   // Lng do poste

  // ── Ícones ──────────────────────────────────────────────────
  const iconePoste = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
      <path d="M16 0C7.16 0 0 7.16 0 16c0 11 16 26 16 26S32 27 32 16C32 7.16 24.84 0 16 0z" fill="#F5C518"/>
      <circle cx="16" cy="16" r="7" fill="#0D0F1A"/>
    </svg>`,
    className: '', iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -44],
  });

  const iconeEquipe = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
      <path d="M16 0C7.16 0 0 7.16 0 16c0 11 16 26 16 26S32 27 32 16C32 7.16 24.84 0 16 0z" fill="#4A90D9"/>
      <circle cx="16" cy="16" r="7" fill="#fff"/>
    </svg>`,
    className: '', iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -44],
  });

  // ── Popula modal da OS ao abrir ──────────────────────────────
  modalOS.addEventListener('show.bs.modal', (e) => {
    const btn = e.relatedTarget;
    if (!btn) return;

    posteLat = parseFloat(btn.dataset.lat);
    posteLng = parseFloat(btn.dataset.lng);

    document.getElementById('os-id-titulo').textContent  = '— OS #' + btn.dataset.id;
    document.getElementById('os-cidadao').textContent    = btn.dataset.cidadao;
    document.getElementById('os-tipo').textContent       = btn.dataset.tipo;
    document.getElementById('os-cep').textContent        = btn.dataset.cep;
    document.getElementById('os-criado').textContent     = btn.dataset.criado;
    document.getElementById('os-descricao').textContent  = btn.dataset.descricao;

    const end = btn.dataset.endereco;
    document.getElementById('os-endereco').textContent   = end || '—';

    const resolvido = btn.dataset.resolvido === '1';
    document.getElementById('os-status').innerHTML = resolvido
      ? '<span class="badge text-bg-success"><i class="fa-solid fa-circle-check me-1"></i>Resolvido</span>'
      : '<span class="badge text-bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Pendente</span>';

    document.getElementById('os-rota-loading').style.display = 'none';
    document.getElementById('os-rota-info').style.display    = 'none';
    document.getElementById('os-rota-erro').style.display    = 'none';
  });

  // ── Inicializa o mapa ao abrir ───────────────────────────────
  modalOS.addEventListener('shown.bs.modal', () => {
    if (!osMap) {
      osMap = L.map('os-map').setView([posteLat, posteLng], 16);

      const satelite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 19, attribution: '© Esri' }
      );
      const ruas = L.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        { maxZoom: 19, attribution: '© OpenStreetMap' }
      );
      ruas.addTo(osMap);
      L.control.layers({ '🛰️ Satélite': satelite, '🗺️ Ruas': ruas }, {}, { position: 'topleft', collapsed: false }).addTo(osMap);
    } else {
      osMap.setView([posteLat, posteLng], 16);
      osMap.invalidateSize();
    }

    if (pinPoste)  { osMap.removeLayer(pinPoste);  pinPoste  = null; }
    if (pinEquipe) { osMap.removeLayer(pinEquipe); pinEquipe = null; }
    if (rotaLayer) { osMap.removeLayer(rotaLayer); rotaLayer = null; }

    pinPoste = L.marker([posteLat, posteLng], { icon: iconePoste })
      .addTo(osMap)
      .bindPopup('<strong>📍 Poste com defeito</strong>')
      .openPopup();
  });

  // ── Botão GPS: calcular rota da posição atual até o poste ────
  if (btnOsGps) {
    btnOsGps.addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert('Seu navegador não suporta geolocalização.');
        return;
      }

      btnOsGps.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Buscando localização...';
      btnOsGps.disabled  = true;

      document.getElementById('os-rota-loading').style.display = 'block';
      document.getElementById('os-rota-info').style.display    = 'none';
      document.getElementById('os-rota-erro').style.display    = 'none';

      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const equLat = position.coords.latitude;
          const equLng = position.coords.longitude;

          btnOsGps.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Calcular rota da minha posição';
          btnOsGps.disabled  = false;

          if (pinEquipe) osMap.removeLayer(pinEquipe);

          pinEquipe = L.marker([equLat, equLng], { icon: iconeEquipe })
            .addTo(osMap)
            .bindPopup('<strong>🔵 Sua posição</strong>');

          const bounds = L.latLngBounds([[equLat, equLng], [posteLat, posteLng]]);
          osMap.fitBounds(bounds, { padding: [50, 50] });

          try {
            const url = `https://router.project-osrm.org/route/v1/driving/${equLng},${equLat};${posteLng},${posteLat}?overview=full&geometries=geojson`;
            const res  = await fetch(url);
            const data = await res.json();

            if (data.code !== 'Ok' || !data.routes.length) {
              throw new Error('Rota não encontrada.');
            }

            const rota     = data.routes[0];
            const distM    = rota.distance;
            const duracaoS = rota.duration;
            const coords   = rota.geometry.coordinates;

            if (rotaLayer) osMap.removeLayer(rotaLayer);

            rotaLayer = L.polyline(
              coords.map(([lng, lat]) => [lat, lng]),
              { color: '#4A90D9', weight: 5, opacity: .85, lineJoin: 'round' }
            ).addTo(osMap);

            const distancia = distM >= 1000
              ? (distM / 1000).toFixed(1) + ' km'
              : Math.round(distM) + ' m';

            const minutos = Math.round(duracaoS / 60);
            const tempo   = minutos >= 60
              ? Math.floor(minutos / 60) + 'h ' + (minutos % 60) + 'min'
              : minutos + ' min';

            document.getElementById('os-distancia').textContent   = distancia;
            document.getElementById('os-tempo').textContent       = tempo;
            document.getElementById('os-rota-loading').style.display = 'none';
            document.getElementById('os-rota-info').style.display    = 'block';

          } catch (err) {
            document.getElementById('os-rota-loading').style.display  = 'none';
            document.getElementById('os-rota-erro').style.display      = 'block';
            document.getElementById('os-rota-erro-msg').textContent    = err.message || 'Não foi possível calcular a rota.';
          }
        },

        (error) => {
          btnOsGps.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Calcular rota da minha posição';
          btnOsGps.disabled  = false;
          document.getElementById('os-rota-loading').style.display = 'none';

          const msgs = {
            1: 'Permissão de localização negada.',
            2: 'Não foi possível obter sua localização.',
            3: 'Tempo esgotado ao buscar localização.',
          };
          document.getElementById('os-rota-erro').style.display     = 'block';
          document.getElementById('os-rota-erro-msg').textContent   = msgs[error.code] ?? 'Erro de GPS.';
        },

        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

})();

window.ShowModal = ShowModal;