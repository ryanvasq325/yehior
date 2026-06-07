// stock-list.js — Yehi Or

// ── Popular modal de detalhes ──────────────────────────────────
const modalEl = document.getElementById('modalStock');

if (modalEl) {
  modalEl.addEventListener('show.bs.modal', (e) => {
    const btn = e.relatedTarget;

    const ativo    = btn.dataset.ativo    === '1';
    const excluido = btn.dataset.excluido === '1';

    // Formata preço
    const preco = parseFloat(btn.dataset.preco).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    });

    document.getElementById('ms-id').textContent         = `#${btn.dataset.id}`;
    document.getElementById('ms-nome').textContent       = btn.dataset.nome;
    document.getElementById('ms-codigo').textContent     = btn.dataset.codigo;
    document.getElementById('ms-fornecedor').textContent = btn.dataset.fornecedor;
    document.getElementById('ms-unidade').textContent    = btn.dataset.unidade;
    document.getElementById('ms-preco').textContent      = preco;
    document.getElementById('ms-criado').textContent     = btn.dataset.criado;
    document.getElementById('ms-atualizado').textContent = btn.dataset.atualizado;
    document.getElementById('ms-descricao').textContent  = btn.dataset.descricao;

    const statusEl = document.getElementById('ms-status');
    if (excluido) {
      statusEl.innerHTML = '<span class="badge text-bg-danger"><i class="fa-solid fa-trash me-1"></i>Excluído</span>';
    } else if (ativo) {
      statusEl.innerHTML = '<span class="badge text-bg-success"><i class="fa-solid fa-circle-check me-1"></i>Ativo</span>';
    } else {
      statusEl.innerHTML = '<span class="badge text-bg-secondary"><i class="fa-solid fa-circle-xmark me-1"></i>Inativo</span>';
    }
  });
}