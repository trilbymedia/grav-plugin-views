const tag = window.__GRAV_REPORT_TAG;

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;');

class ViewsReportElement extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.data = null;
  }

  set report(value) {
    this.data = value;
    this.render();
  }

  get report() {
    return this.data;
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const items = Array.isArray(this.data?.items) ? this.data.items : [];
    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          color: var(--foreground, inherit);
          font: inherit;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          font-size: 0.875rem;
        }
        th,
        td {
          padding: 0.625rem 0.75rem;
          border-bottom: 1px solid var(--border, rgba(148, 163, 184, 0.25));
          text-align: left;
          vertical-align: middle;
        }
        th {
          color: var(--muted-foreground, #64748b);
          font-size: 0.75rem;
          font-weight: 600;
          text-transform: uppercase;
        }
        td:last-child,
        th:last-child {
          text-align: right;
        }
        a {
          color: var(--primary, #2563eb);
          text-decoration: none;
        }
        a:hover {
          text-decoration: underline;
        }
        .empty {
          border: 1px dashed var(--border, rgba(148, 163, 184, 0.35));
          border-radius: 0.5rem;
          color: var(--muted-foreground, #64748b);
          padding: 2rem;
          text-align: center;
        }
        .count {
          font-variant-numeric: tabular-nums;
          font-weight: 600;
        }
        .type {
          color: var(--muted-foreground, #64748b);
        }
      </style>
      ${items.length === 0 ? `
        <div class="empty">No views tracked yet.</div>
      ` : `
        <table>
          <thead>
            <tr>
              <th>Page</th>
              <th>Type</th>
              <th>Views</th>
            </tr>
          </thead>
          <tbody>
            ${items.map((item) => {
              const id = escapeHtml(item.id);
              const href = String(item.id ?? '').startsWith('/') ? escapeHtml(item.id) : '';
              return `
                <tr>
                  <td>${href ? `<a href="${href}" target="_blank" rel="noopener noreferrer">${id}</a>` : id}</td>
                  <td class="type">${escapeHtml(item.type)}</td>
                  <td class="count">${escapeHtml(item.count)}</td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      `}
    `;
  }
}

if (tag && !customElements.get(tag)) {
  customElements.define(tag, ViewsReportElement);
}
