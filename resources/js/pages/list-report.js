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