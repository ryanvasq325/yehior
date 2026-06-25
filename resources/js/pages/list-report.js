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

window.ShowModal = ShowModal;