<template>
<!-- Trigger -->
<slot name="trigger" :open="open" :disabled="!hasAnyRows">
  <button :disabled="!hasAnyRows" @click="open">Export Excel</button>
</slot>

<!-- Dialog -->
<div v-if="show" class="export-backdrop" @click.self="close">
  <div class="export-dialog">
    <h3>{{ title || 'Export columns' }}</h3>

    <!-- Data scope (only shown if allRows is provided) -->
    <div v-if="hasAllRows" class="scope">
      <strong>Data scope:</strong>
      <label class="scope-item">
        <input type="radio" value="displayed" v-model="exportScope" />
        Displayed only (filtered/paged)
      </label>
      <label class="scope-item">
        <input type="radio" value="all" v-model="exportScope" />
        All rows
      </label>
      <span class="scope-count"><b>Rows:</b> {{ effectiveRows.length }}</span>
    </div>

    <div class="cols">
      <label v-for="(col, i) in normalizedColumns" :key="col.key ?? i" class="col-item">
        <input type="checkbox" v-model="selected" :value="col.key" />
        {{ col.label }}
      </label>
    </div>

    <div class="dialog-actions">
      <button @click="selectAll">Select all</button>
      <button @click="clearAll">Clear</button>
      <div class="spacer"></div>
      <button @click="close">Cancel</button>
      <button :disabled="!effectiveRows.length || !selected.length" @click="exportNow" style="background-color: #2563eb; color: white;">
        Export
      </button>
    </div>
  </div>
</div>
</template>

<script>
// Reusable Excel exporter with checkbox dialog
import * as XLSX from 'xlsx';

export default {
  name: 'ExportExcel',
  props: {
    rows: { type: Array, default: () => [] },
    allRows: { type: Array, default: () => null },
    columns: { type: [Array, Object], required: true }, // also allow object
    filename: { type: String, default: '' },
    sheetName: { type: String, default: 'Sheet1' },
    title: { type: String, default: '' },
    defaultSelectedKeys: { type: Array, default: null },
  },
  data() {
    return {
      show: false,
      selected: [],
      exportScope: 'displayed',
    };
  },
  computed: {
    normalizedColumns() {
      // accept array, or object map -> array of values
      if (Array.isArray(this.columns)) return this.columns;
    if (this.columns && typeof this.columns === 'object') return Object.values(this.columns);
    console.warn('[ExportExcel] "columns" not array/object:', this.columns);
    return [];
    },
    hasAllRows() {
        return Array.isArray(this.allRows) && this.allRows.length > 0;
    },
    hasAnyRows() {
        return (this.rows?.length || 0) > 0 || (this.allRows?.length || 0) > 0;
    },
    effectiveRows() {
        return (this.exportScope === 'all' && this.hasAllRows) ? this.allRows : (this.rows || []);
    },
  },
  watch: {
  normalizedColumns(newCols) {
    if (newCols?.length && !this.selected.length) {
      this.selected = newCols.map(c => c.key);
    }
  }
},
  mounted() {
    if (!this.normalizedColumns.length) {
      console.warn('[ExportExcel] No columns provided or failed to normalize.');
    }
    this.selected = (Array.isArray(this.defaultSelectedKeys) && this.defaultSelectedKeys.length)
      ? [...this.defaultSelectedKeys]
      : this.normalizedColumns.map(c => c.key);
  },
  methods: {
    open() { this.show = true; },
    close() { this.show = false; },
    selectAll() { this.selected = this.normalizedColumns.map(c => c.key); },
    clearAll() { this.selected = []; },

    _getXLSX() {
      if (typeof XLSX !== 'undefined') return XLSX;
      if (typeof window !== 'undefined' && window.XLSX) return window.XLSX;
      throw new Error('XLSX not found. Install "xlsx" or include the CDN script.');
    },

    _valueFor(col, row) {
      const raw = col.getter ? col.getter(row) : row?.[col.key];
      return col.format ? col.format(raw, row) : (raw ?? '');
    },

    _autosize(ws, selectedCols, exportedRows) {
      const widths = selectedCols.map(c => {
        const headerLen = (c.label || '').toString().length;
        const maxCell = exportedRows.reduce((m, r) => {
          const v = r[c.label];
          const len = (v == null ? '' : String(v)).length;
          return Math.max(m, len);
        }, 0);
        return { wch: Math.min(Math.max(10, Math.max(headerLen, maxCell) + 2), 60) };
      });
      ws['!cols'] = widths;
    },
    exportNow() {
        try {
        const XLSX = this._getXLSX();

        const selectedCols = this.normalizedColumns.filter(c => this.selected.includes(c.key));
        if (!selectedCols.length) return;

        const data = this.effectiveRows.map(row => {
            const out = {};
            for (const col of selectedCols) out[col.label] = this._valueFor(col, row);
            return out;
        });

        const ws = XLSX.utils.json_to_sheet(data, { skipHeader: false });
        this._autosize(ws, selectedCols, data);

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, this.sheetName || 'Sheet1');

        const suffix = this.exportScope === 'all' ? 'all' : 'displayed';
        const name = this.filename || `export-${suffix}-${new Date().toISOString().slice(0,10)}.xlsx`;
        XLSX.writeFile(wb, name);

        this.close();
        } catch (e) {
        console.error(e);
        alert('Export failed. Check console for details.');
        }
    },
  },
};
</script>


<style scoped>
.export-backdrop {
  position: fixed; inset: 0; display: grid; place-items: center;
  background: rgba(0,0,0,.35); z-index: 9999;
}
.export-dialog {
  background: #fff; width: min(720px, 92vw); max-height: 80vh; overflow: auto;
  border-radius: 12px; padding: 16px 18px; box-shadow: 0 10px 35px rgba(0,0,0,.25);
}
.cols { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px 16px; margin: 12px 0 14px; }
.col-item { display: flex; align-items: center; gap: 8px; }
.dialog-actions { display: flex; align-items: center; gap: 8px; }
.dialog-actions .spacer { flex: 1; }
.scope { display:flex; align-items:center; gap:12px; margin: 6px 0 12px; }
.scope-item { display:flex; align-items:center; gap:6px; }
.scope-count { opacity:.8; font-size:.9em; }
</style>
