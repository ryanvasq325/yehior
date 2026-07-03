{% extends 'layouts/main.html' %}

{% block css %}
{ { vite('resources/css/pages/accompany.css') } }
{% endblock %}

{% block main %}

<div class="col-12">
  <div class="row justify-content-center">
    <div class="col-lg-9 col-md-11">

      <div id="accompany-root" data-report-id="{{ report.id }}">

        {# ---------- Cabeçalho ---------- #}
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <p class="text-secondary small mb-1">Reporte #{{ report.id }}</p>
            <h2 class="fw-bold mb-0">{{ report.tipoProblema }} — Poste {{ report.poste }}</h2>
          </div>
          <span id="status-badge" class="badge rounded-pill px-3 py-2 status-badge-{{ report.status }}">
            {{ report.statusLabel }}
          </span>
        </div>

        {# ---------- Linha do tempo ---------- #}
        <div class="report-card p-4 mb-3">
          <ol id="status-timeline" class="timeline d-flex justify-content-between list-unstyled m-0">
            {% for step in statusSteps %}
            <li class="timeline-step flex-fill text-center position-relative
                          {% if step.done %}is-done{% elseif step.current %}is-current{% endif %}">
              <span class="timeline-dot">
                {% if step.done %}
                <i class="fa-solid fa-check"></i>
                {% elseif step.current %}
                <i class="fa-solid fa-screwdriver-wrench"></i>
                {% else %}
                <i class="fa-solid fa-flag"></i>
                {% endif %}
              </span>
              <p class="small mb-0 mt-2">{{ step.label }}</p>
              <p class="text-secondary" style="font-size: 11px;">{{ step.date ?: '—' }}</p>
            </li>
            {% endfor %}
          </ol>
        </div>

        {# ---------- Detalhes rápidos ---------- #}
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="report-card p-3 h-100">
              <p class="text-secondary small mb-1"><i class="fa-solid fa-map-pin me-1 text-amarelo"></i>Endereço</p>
              <p class="mb-0">{{ report.logradouro }}, {{ report.numero }} — {{ report.bairro }}</p>
            </div>
          </div>
          <div class="col-6">
            <div class="report-card p-3 h-100">
              <p class="text-secondary small mb-1"><i class="fa-solid fa-tenge-sign me-1 text-amarelo"></i>Poste</p>
              <p class="mb-0">{{ report.poste }}</p>
            </div>
          </div>
        </div>

        {# ---------- Descrição enviada ---------- #}
        {% if report.descricao %}
        <div class="report-card p-3 mb-3">
          <p class="text-secondary small mb-1">Descrição enviada</p>
          <p class="mb-0">{{ report.descricao }}</p>
        </div>
        {% endif %}

        {# ---------- Histórico de atualizações ---------- #}
        <div class="report-card p-3">
          <p class="text-secondary small mb-3">Histórico de atualizações</p>
          <ul id="status-history" class="history-list list-unstyled mb-0">
            {% for item in report.history %}
            <li class="history-item">
              <span class="history-dot"></span>
              <p class="mb-0">{{ item.comment }}</p>
              <p class="text-secondary" style="font-size: 11px;">{{ item.createdAt }}</p>
            </li>
            {% endfor %}
          </ul>
        </div>

      </div>

    </div>
  </div>
</div>

{% endblock %}

{% block script %}
{ { vite('resources/js/pages/accompany.js') } }
{% endblock %}