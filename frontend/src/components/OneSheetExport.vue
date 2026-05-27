<template>
  <!-- Trigger -->
  <slot name="trigger" :open="open" :disabled="!hasAnyRows">
    <button :disabled="!hasAnyRows" @click="open">Export Excel (One Sheet)</button>
  </slot>

  <!-- Dialog -->
  <div v-if="show" class="export-backdrop" @click.self="close">
    <div class="export-dialog">
      <h3>{{ title || 'Export to one Excel sheet' }}</h3>

      <div class="sections">
        <div
          v-for="(sec, i) in normalizedSections"
          :key="sec._id"
          class="sec-card"
        >
          <div class="sec-head">
            <label class="include">
              <input type="checkbox" v-model="state[i].included" />
              <strong>{{ sec.title }}</strong>
              <span class="badge">{{ sec.kind.toUpperCase() }}</span>
            </label>
          </div>

          <!-- Column chooser -->
          <details class="cols-wrap" :open="normalizedSections.length <= 3">
            <summary>Columns</summary>

            <!-- For vertical (key-value) sections, show fields as a single column list -->
            <div v-if="sec.kind === 'kv'" class="cols kv">
              <label
                v-for="(col, cIdx) in sec.columns"
                :key="col.key ?? cIdx"
                class="col-item"
              >
                <input
                  type="checkbox"
                  :value="col.key"
                  v-model="state[i].selectedKeys"
                />
                {{ col.label }}
              </label>
            </div>

            <!-- For table sections, same selector but just a different visual hint -->
            <div v-else class="cols table">
              <label
                v-for="(col, cIdx) in sec.columns"
                :key="col.key ?? cIdx"
                class="col-item"
              >
                <input
                  type="checkbox"
                  :value="col.key"
                  v-model="state[i].selectedKeys"
                />
                {{ col.label }}
              </label>
            </div>

            <div class="row-actions">
              <button type="button" @click="selectAllCols(i)">Select all</button>
              <button type="button" @click="clearCols(i)">Clear</button>
            </div>
          </details>
        </div>
      </div>

      <div class="dialog-actions">
        <button type="button" @click="includeAll">Include all</button>
        <button type="button" @click="excludeAll">Exclude all</button>
        <div class="spacer"></div>
        <button type="button" @click="close">Cancel</button>
        <button type="button" :disabled="!canExport" @click="exportNow">Export</button>
      </div>
    </div>
  </div>
</template>

<script>
import * as XLSX from 'xlsx';

/**
 * OneSheetExport.vue
 *
 * Props:
 *  - sections: Array<{
 *        id?: string,
 *        title: string,
 *        kind: 'kv' | 'table',
 *        data: object | any[],                     // object for 'kv', array for 'table'
 *        columns: { key, label, getter?, format? }[],
 *        defaultSelectedKeys?: string[],           // optional; if omitted, we auto-select all EXCEPT *_at
 *    }>
 *  - filename?: string
 *  - title?: string
 *
 * Renders all selected sections into a SINGLE Excel sheet.
 * 'kv' sections are rendered vertically: Label in col A, Value in col B.
 * 'table' sections are rendered as a normal header row followed by rows.
 */
export default {
  name: 'OneSheetExport',
  props: {
    sections: { type: Array, default: () => [] },
    filename: { type: String, default: '' },
    title: { type: String, default: '' },
  },
  data() {
    return {
      show: false,
      state: [], // mirrors sections: [{ included, selectedKeys }]
    };
  },
  computed: {
    normalizedSections() {
      return (Array.isArray(this.sections) ? this.sections : []).map((s, i) => ({
        _id: s.id || `sec-${i}`,
        title: s.title || `Section ${i + 1}`,
        kind: s.kind === 'table' ? 'table' : 'kv',
        data: s.data,
        columns: Array.isArray(s.columns) ? s.columns : [],
        defaultSelectedKeys: Array.isArray(s.defaultSelectedKeys) ? s.defaultSelectedKeys : null,
      }));
    },
    hasAnyRows() {
      return this.normalizedSections.some(s => {
        if (s.kind === 'kv') return !!s.data && typeof s.data === 'object';
        if (s.kind === 'table') return Array.isArray(s.data) && s.data.length > 0;
        return false;
      });
    },
    canExport() {
      return this.normalizedSections.some((s, i) => {
        const st = this.state[i];
        return st?.included && (st.selectedKeys?.length > 0);
      });
    },
  },
  watch: {
    normalizedSections: {
      immediate: true,
      handler(newSecs) {
        this.state = newSecs.map((s) => {
          // Default: include section, select all non-timestamp columns
          const defaultKeys = s.defaultSelectedKeys && s.defaultSelectedKeys.length
            ? [...s.defaultSelectedKeys]
            : s.columns
                .map(c => c.key)
                .filter(k => !(k?.endsWith('created_at') || k?.endsWith('updated_at') || /(^|_)created_at$/.test(k) || /(^|_)updated_at$/.test(k)));
          return {
            included: true,
            selectedKeys: defaultKeys,
          };
        });
      },
    },
  },
  methods: {
    open() { this.show = true; },
    close() { this.show = false; },

    includeAll() { this.state.forEach(s => (s.included = true)); },
    excludeAll() { this.state.forEach(s => (s.included = false)); },
    selectAllCols(i) {
      const cols = this.normalizedSections[i]?.columns || [];
      this.state[i].selectedKeys = cols.map(c => c.key);
    },
    clearCols(i) { this.state[i].selectedKeys = []; },

    _valueFor(col, rowOrObj) {
      const raw = col.getter ? col.getter(rowOrObj) : rowOrObj?.[col.key];
      return col.format ? col.format(raw, rowOrObj) : (raw ?? '');
    },

    exportNow() {
      try {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([]); // start empty; we’ll add sections

        let cursor = 0; // current row pointer

        const addEmptyRow = (n = 1) => { cursor += n; };

        const addTitle = (text) => {
          XLSX.utils.sheet_add_aoa(ws, [[text]], { origin: { r: cursor, c: 0 } });
          // Bold the title
          const cellAddr = XLSX.utils.encode_cell({ r: cursor, c: 0 });
          if (!ws[cellAddr]) ws[cellAddr] = { t: 's', v: text };
          ws[cellAddr].s = { font: { bold: true } }; // style support varies by writer
          cursor += 1;
        };

        const addKVSection = (sec, selectedCols) => {
          // Write title
          addTitle(sec.title);
          // Label | Value rows
          const rows = [];
          selectedCols.forEach(col => {
            rows.push([col.label, this._valueFor(col, sec.data)]);
          });
          XLSX.utils.sheet_add_aoa(ws, rows, { origin: { r: cursor, c: 0 } });
          cursor += rows.length;
          addEmptyRow(1);
        };

        const addTableSection = (sec, selectedCols) => {
          addTitle(sec.title);
          // Header
          const header = selectedCols.map(c => c.label);
          XLSX.utils.sheet_add_aoa(ws, [header], { origin: { r: cursor, c: 0 } });
          cursor += 1;
          // Rows
          const rows = (sec.data || []).map(row => {
            return selectedCols.map(col => this._valueFor(col, row));
          });
          if (rows.length) {
            XLSX.utils.sheet_add_aoa(ws, rows, { origin: { r: cursor, c: 0 } });
            cursor += rows.length;
          }
          addEmptyRow(1);
        };

        // Render each included section
        this.normalizedSections.forEach((sec, i) => {
          const st = this.state[i];
          if (!st?.included) return;
          const selectedCols = sec.columns.filter(c => st.selectedKeys.includes(c.key));
          if (!selectedCols.length) return;

          if (sec.kind === 'kv') addKVSection(sec, selectedCols);
          else addTableSection(sec, selectedCols);
        });

        // (Best-effort) autosize: determine max width per column from worksheet cells
        const ref = ws['!ref'];
        if (ref) {
          const range = XLSX.utils.decode_range(ref);
          const widths = Array.from({ length: range.e.c - range.s.c + 1 }, () => ({ wch: 10 }));
          for (let C = range.s.c; C <= range.e.c; C++) {
            let max = 10;
            for (let R = range.s.r; R <= range.e.r; R++) {
              const cell = ws[XLSX.utils.encode_cell({ r: R, c: C })];
              if (cell && cell.v != null) {
                const len = String(cell.v).length;
                if (len + 2 > max) max = Math.min(len + 2, 60);
              }
            }
            widths[C] = { wch: max };
          }
          ws['!cols'] = widths;
        }

        XLSX.utils.book_append_sheet(wb, ws, 'Report');
        const name = this.filename || `report-${new Date().toISOString().slice(0,10)}.xlsx`;
        XLSX.writeFile(wb, name);
        this.close();
      } catch (e) {
        console.error('OneSheet export failed:', e);
        alert('Export failed. Check console for details.');
      }
    },
  },
};
</script>

<style scoped>
.export-backdrop { position: fixed; inset: 0; display: grid; place-items: center; background: rgba(0,0,0,.35); z-index: 9999; }
.export-dialog { background: #fff; width: min(920px, 96vw); max-height: 86vh; overflow: auto; border-radius: 14px; padding: 16px 18px; box-shadow: 0 10px 35px rgba(0,0,0,.25); }
.sections { display: grid; gap: 12px; margin: 10px 0 14px; }
.sec-card { border: 1px solid #e9e9e9; border-radius: 10px; padding: 12px; }
.sec-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.include { display: flex; align-items: center; gap: 8px; }
.badge { background: #f2f2f2; border-radius: 999px; padding: 2px 8px; font-size: .75rem; }
.cols-wrap summary { cursor: pointer; margin-bottom: 6px; }
.cols { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px 16px; margin: 6px 0 10px; }
.cols.kv { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
.col-item { display: flex; align-items: center; gap: 8px; }
.row-actions { display: flex; gap: 8px; }
.dialog-actions { display: flex; align-items: center; gap: 8px; }
.dialog-actions .spacer { flex: 1; }
</style>
