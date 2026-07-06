import Requests from "../components/requests.js";
import Validate from "../components/validate.js";
import DataTables from '../components/data-tables.js';

const table   = DataTables.SetId('table-product').setRequestVariables([]).post('/produto/listingdata');
const Action  = document.getElementById('action');
const Id      = document.getElementById('id');
const Insert  = document.getElementById('buttonRegister');
const FormProduct = document.getElementById('formProduct');

// ── Ajuste de estoque ────────────────────────────────────────────
const IdEstoque     = document.getElementById('idEstoque');
const ButtonEstoque = document.getElementById('buttonEstoque');

const inputmaskConfig = {
    radixPoint: ",",
    inputtype: "text",
    prefix: "R$ ",
    autoGroup: true,
    groupSeparator: ".",
    rightAlign: false,
    onBeforeMask: function (value) {
        return String(value).replace(".", ",");
    },
};

Inputmask("currency", inputmaskConfig).mask("#preco_compra");

function limparInputsParaEnvio() {
    ["#preco_compra"].forEach(seletor => {
        const campo = document.querySelector(seletor);
        if (campo && campo.inputmask) {
            let valorPuro = campo.inputmask.unmaskedvalue();
            valorPuro = valorPuro.replace(",", ".");

            campo.inputmask.remove();
            campo.value = valorPuro;
        }
    });
}

function restaurarMascaras() {
    Inputmask("currency", inputmaskConfig).mask("#preco_compra");
}

// ── Alterna o modal entre modo "cadastro" e modo "edição" ─────────
function setModoCadastro() {
    Action.value = 'c';
    Id.value     = '';
    document.getElementById('modalProductsLabel').textContent = 'Cadastro de Produto';
}

function setModoEdicao() {
    Action.value = 'e';
    document.getElementById('modalProductsLabel').textContent = 'Editar Produto';
}

mdProduct.addEventListener('click', () => {
    FormProduct.reset();
    restaurarMascaras();
    setModoCadastro();
    $('#modalProducts').modal('show');
});

mdBack.addEventListener('click', () => {
    window.location.href = '/admin/gestao';
});

// ── Busca os dados do produto e abre o modal preenchido ───────────
async function EditProduct(id) {
    const requests = new Requests();
    try {
        const response = await requests.get(`/produto/detalhes/${id}`);

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.msg,
                timer: 3000,
                timerProgressBar: true,
            });
            return;
        }

        const product = response.data;

        FormProduct.reset();
        document.getElementById('nome').value         = product.nome ?? '';
        document.getElementById('codigo_barra').value = product.codigo_barra ?? '';
        document.getElementById('unidade').value      = product.unidade ?? '';
        document.getElementById('descricao').value    = product.descricao ?? '';
        document.getElementById('checkAtivo').checked = !!product.ativo;

        // Preenche o preço já mascarado como moeda
        const precoInput = document.getElementById('preco_compra');
        precoInput.value = product.preco_compra ?? '';
        if (precoInput.inputmask) {
            precoInput.inputmask.setValue(product.preco_compra ?? '');
        }

        setModoEdicao();
        Id.value = product.id;

        $('#modalProducts').modal('show');
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

// ── Cadastro / atualização de produto ─────────────────────────────
async function applyChanges() {
    const IsValid = Validate.SetForm('formProduct').Validate();
    if (!IsValid) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Por favor, corrija os erros no formulário antes de salvar.`,
            timer: 3000,
            timerProgressBar: true,
        });
        return;
    }

    $('button').prop('disabled', true);

    const requests = new Requests();
    try {
        limparInputsParaEnvio();
        const response = (Action.value === 'e')
            ? await requests.setForm('formProduct').post('/produto/update')
            : await requests.setForm('formProduct').post('/produto/insert');

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.msg || 'Ocorreu um erro ao salvar os dados do produto.',
                timer: 3000,
                timerProgressBar: true,
            });
            restaurarMascaras();
            return;
        }

        $('#modalProducts').modal('hide');
        FormProduct.reset();
        setModoCadastro();

        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: response.msg || 'Produto salvo com sucesso!',
            timer: 2000,
            timerProgressBar: true,
        }).then(() => {
            table.ajax.reload();
        });
    } catch (error) {
        restaurarMascaras();
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error.message}`,
            timer: 3000,
            timerProgressBar: true,
        });
    } finally {
        $('button, input, checkbox').prop('disabled', false);
        restaurarMascaras();
    }
}

Insert.addEventListener('click', async () => {
    await applyChanges();
});

// ── Exclusão de produto ─────────────────────────────────────────────
async function deleteProduct() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formProduct').post('/produto/delete');
        return response;
    } catch (error) {
        return { status: false, msg: `Restrição: ${error}` };
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
            const response = await deleteProduct();
            if (!response.status) {
                Swal.fire({
                    title: "Erro!",
                    text: response.msg,
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

window.ShowModal    = ShowModal;
window.EditProduct  = EditProduct;

// ── Ajuste de estoque ────────────────────────────────────────────
async function AjustarEstoque(id) {
    try {
        IdEstoque.value = id;
        document.getElementById('nova_quantidade').value = '';
        document.getElementById('quantidade_atual').value = 'Carregando...';

        const requests = new Requests();
        const response = await requests.setForm('formEstoque').post('/produto/selecionarestoque');

        if (response && response.status) {
            document.getElementById('quantidade_atual').value = response.estoque_atual;
            $('#modalstock').modal('show');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response?.msg || 'Produto não encontrado ou sem saldo.',
                timer: 3000,
                timerProgressBar: true,
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error.message || error}`,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}

async function NovaQuantidade() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formEstoque').post('/produto/selecionarestoque');
        if (response.status) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: response.msg,
                timer: 2000,
                timerProgressBar: true,
            });
            $('#modalstock').modal('hide');
            table.ajax.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: response.msg, timer: 3000, timerProgressBar: true });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error.message || error}`,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}

ButtonEstoque.addEventListener('click', async () => {
    await NovaQuantidade();
});

window.AjustarEstoque = AjustarEstoque;