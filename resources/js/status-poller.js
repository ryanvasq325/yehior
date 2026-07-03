// status-poller.js — consulta periódica do status de um reporte
export class StatusPoller {
  constructor(reportId, { interval = 30000, onUpdate } = {}) {
    this.reportId = reportId;
    this.interval = interval;
    this.onUpdate = onUpdate;
    this.lastUpdatedAt = null;
    this.timer = null;
  }

  async fetchStatus() {
    try {
      const res = await fetch(`/api/reports/${this.reportId}/status`);
      if (!res.ok) return;
      const data = await res.json();

      if (data.updated_at !== this.lastUpdatedAt) {
        this.lastUpdatedAt = data.updated_at;
        this.onUpdate?.(data);
      }
    } catch (e) {
      console.warn('Falha ao atualizar status:', e);
    }
  }

  start() {
    this.fetchStatus();
    this._schedule();

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(this.timer);
      } else {
        this.fetchStatus();
        this._schedule();
      }
    });
  }

  _schedule() {
    this.timer = setInterval(() => this.fetchStatus(), this.interval);
  }

  stop() {
    clearInterval(this.timer);
  }
}