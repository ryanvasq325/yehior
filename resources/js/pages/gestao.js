import Charts from '../components/charts.js';

new Charts().setId('chartsale').getData('/admin/status/getsalesdata').BAR().render();
new Charts().setId('chartabc').getData('/admin/status/getabcranking').PIE().render();

document.addEventListener('DOMContentLoaded', () => {

  // ===========================================================================
  // Aviso de reportes pendentes — Yehi Or
  // O total de pendentes já vem renderizado pelo PHP/Twig (controller Admin::gestao)
  // num atributo data-total, sem precisar de requisição AJAX extra.
  // O toast só aparece quando o total SOBE em relação à última vez que o admin
  // abriu essa tela (evita ficar repetindo o mesmo aviso a cada recarregamento).
  // ===========================================================================
  const pendentesEl = document.getElementById('pendentes-data');

  if (pendentesEl) {
    const total = parseInt(pendentesEl.dataset.total ?? '0', 10);

    if (total > 0) {
      const ultimoVisto = parseInt(localStorage.getItem('yehior_pendentes_vistos') ?? '0', 10);

      if (total > ultimoVisto) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: `${total} reporte${total > 1 ? 's' : ''} pendente${total > 1 ? 's' : ''} de análise`,
          showConfirmButton: false,
          timer: 5000,
          timerProgressBar: true,
        });
      }

      localStorage.setItem('yehior_pendentes_vistos', String(total));
    } else {
      localStorage.removeItem('yehior_pendentes_vistos');
    }
  }

  // ===========================================================================
  // Mapa de reportes do painel admin — Yehi Or
  // Exibe todos os reportes que possuem latitude/longitude salvos pelo cidadão.
  // Os dados vêm do PHP via Twig, lidos do data-attribute #reports-map-data
  // (em vez de var inline no HTML).
  // Pin vermelho = pendente | Pin verde = resolvido
  // Clique no pin abre popup com detalhes e link para a Ordem de Serviço.
  // ===========================================================================
  const mapDataEl = document.getElementById('reports-map-data');
  const mapDiv    = document.getElementById('admin-map');

  if (!mapDataEl || !mapDiv) return;

  // Exemplo de cada item: { id, cidadao, tipo, cep, endereco, numero, bairro, poste, resolvido, lat, lng }
  let reports = [];
  try {
    reports = JSON.parse(mapDataEl.dataset.reports || '[]');
  } catch {
    reports = [];
  }

  // Filtra apenas reportes que têm coordenadas válidas
  const reportesComGps = reports.filter((r) => r.lat && r.lng);

  if (reportesComGps.length === 0) {
    // Se não há reportes com GPS, exibe mensagem no lugar do mapa
    mapDiv.style.height = 'auto';
    mapDiv.innerHTML = '<div class="text-center text-secondary py-5">'
      + '<i class="fa-solid fa-map-location-dot fa-2x mb-2 d-block opacity-50"></i>'
      + 'Nenhum reporte com localização GPS cadastrado ainda.'
      + '</div>';
    return;
  }

  // Inicializa o mapa centrado na média das coordenadas dos reportes
  const lats = reportesComGps.map((r) => parseFloat(r.lat));
  const lngs = reportesComGps.map((r) => parseFloat(r.lng));
  const centerLat = lats.reduce((a, b) => a + b, 0) / lats.length;
  const centerLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;

  const adminMap = L.map('admin-map').setView([centerLat, centerLng], 14);

  // Camadas: satélite + ruas alternáveis
  const satelite = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { maxZoom: 19, attribution: '© Esri' }
  );
  const ruas = L.tileLayer(
    'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    { maxZoom: 19, attribution: '© OpenStreetMap' }
  );

  // Começa com ruas (mais legível no painel admin)
  ruas.addTo(adminMap);
  L.control.layers(
    { '🛰️ Satélite': satelite, '🗺️ Ruas': ruas },
    {},
    { position: 'topleft', collapsed: false }
  ).addTo(adminMap);

  // Função que gera o ícone SVG colorido conforme o status do reporte
  function makeIcon(resolvido) {
    const cor = resolvido ? '#198754' : '#dc3545'; // verde = resolvido, vermelho = pendente
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">
      <path d="M14 0C6.27 0 0 6.27 0 14c0 9.625 14 22 14 22S28 23.625 28 14C28 6.27 21.73 0 14 0z" fill="${cor}"/>
      <circle cx="14" cy="14" r="6" fill="#fff"/>
    </svg>`;
    return L.divIcon({
      html: svg,
      className: '',
      iconSize: [28, 36],
      iconAnchor: [14, 36],
      popupAnchor: [0, -38],
    });
  }

  reportesComGps.forEach((r) => {
    const statusBadge = r.resolvido
      ? '<span style="color:#198754;font-weight:600;">✔ Resolvido</span>'
      : '<span style="color:#dc3545;font-weight:600;">⏳ Pendente</span>';

    const enderecoCompleto = [r.endereco, r.numero].filter(Boolean).join(', ') || r.cep || '—';

    const popupHtml = `<div style="min-width:200px;line-height:1.6">
      <strong>OS #${r.id}</strong><br>
      <span style="color:#aaa;font-size:.8rem;">${r.tipo || '—'}</span><br>
      <span style="font-size:.82rem;">${enderecoCompleto}${r.bairro ? ' — ' + r.bairro : ''}</span><br>
      ${r.poste ? `<span style="font-size:.82rem;">Poste: ${r.poste}</span><br>` : ''}
      <span style="font-size:.82rem;">Cidadão: ${r.cidadao || '—'}</span><br>
      ${statusBadge}<br>
      <a href="/admin/listreport" style="font-size:.8rem;color:#F5C518;">Ver Ordem de Serviço →</a>
    </div>`;

    L.marker([parseFloat(r.lat), parseFloat(r.lng)], { icon: makeIcon(r.resolvido) })
      .addTo(adminMap)
      .bindPopup(popupHtml);
  });

  const bounds = L.latLngBounds(reportesComGps.map((r) => [parseFloat(r.lat), parseFloat(r.lng)]));
  adminMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });

});