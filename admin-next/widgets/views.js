const tag = window.__GRAV_WIDGET_TAG;

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;');

class ViewsWidgetElement extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.items = [];
    this.error = '';
    this.loading = true;
  }

  connectedCallback() {
    this.render();
    this.load();
  }

  async load() {
    try {
      const serverUrl = window.__GRAV_API_SERVER_URL || '';
      const prefix = window.__GRAV_API_PREFIX || '/api/v1';
      const token = window.__GRAV_API_TOKEN || '';
      const response = await fetch(`${serverUrl}${prefix}/reports`, {
        headers: token ? { 'X-API-Token': token } : {},
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const payload = await response.json();
      const reports = Array.isArray(payload.data) ? payload.data : [];
      const report = reports.find((item) => item.id === 'views');
      this.items = Array.isArray(report?.items) ? report.items.slice(0, 6) : [];
    } catch (error) {
      this.error = error instanceof Error ? error.message : 'Unable to load views.';
    } finally {
      this.loading = false;
      this.render();
    }
  }

  render() {
    const max = Math.max(...this.items.map((item) => Number(item.count) || 0), 1);
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          height: 100%;
          color: var(--foreground, inherit);
          font: inherit;
        }
        .wrap {
          height: 100%;
          border: 1px solid var(--border, rgba(148, 163, 184, 0.25));
          border-radius: 0.5rem;
          background: var(--card, transparent);
          padding: 1rem;
        }
        h2 {
          margin: 0 0 0.75rem;
          font-size: 0.875rem;
          font-weight: 600;
        }
        .empty,
        .error {
          color: var(--muted-foreground, #64748b);
          font-size: 0.8125rem;
          padding: 1rem 0;
          text-align: center;
        }
        .error {
          color: var(--destructive, #dc2626);
        }
        .items {
          display: grid;
          gap: 0.625rem;
        }
        .row {
          display: grid;
          gap: 0.25rem;
        }
        .line {
          align-items: center;
          display: flex;
          gap: 0.75rem;
          justify-content: space-between;
          min-width: 0;
        }
        .route {
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        .count {
          color: var(--muted-foreground, #64748b);
          flex: 0 0 auto;
          font-size: 0.75rem;
          font-variant-numeric: tabular-nums;
          font-weight: 600;
        }
        .bar {
          height: 0.25rem;
          overflow: hidden;
          border-radius: 999px;
          background: var(--secondary, rgba(148, 163, 184, 0.2));
        }
        .fill {
          height: 100%;
          border-radius: inherit;
          background: var(--primary, #2563eb);
        }
      </style>
      <div class="wrap">
        <h2>Grav Views</h2>
        ${this.loading ? `
          <div class="empty">Loading views...</div>
        ` : this.error ? `
          <div class="error">${escapeHtml(this.error)}</div>
        ` : this.items.length === 0 ? `
          <div class="empty">No views tracked yet.</div>
        ` : `
          <div class="items">
            ${this.items.map((item) => {
              const count = Number(item.count) || 0;
              const width = Math.max(4, Math.round((count / max) * 100));
              return `
                <div class="row">
                  <div class="line">
                    <span class="route">${escapeHtml(item.id)}</span>
                    <span class="count">${escapeHtml(count)}</span>
                  </div>
                  <div class="bar"><div class="fill" style="width: ${width}%"></div></div>
                </div>
              `;
            }).join('')}
          </div>
        `}
      </div>
    `;
  }
}

if (tag && !customElements.get(tag)) {
  customElements.define(tag, ViewsWidgetElement);
}
