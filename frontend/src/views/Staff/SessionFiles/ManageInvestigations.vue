<template>
  <div>
    <h2>Investigations</h2>

    <!-- <button @click="$router.push('/dashboard')" style="margin-top: 1rem;">Back</button> -->

    <!-- ========================== A) GENERAL INVESTIGATIONS (TABLE) ========================== -->
    <h3 class="table-header">
      General Investigations
      <button :disabled="!accessCheck('edit')" class="action-btn" @click="openGenModal">Edit General Investigations</button>
    </h3>
    <table class="std-table">
      <thead>
        <tr>
          <th style="width: 30%">Test</th>
          <th style="width: 20%">Status</th>
          <th>Details of any abnormality</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in generalRows" :key="row.key">
          <td>{{ row.label }}</td>
          <td>
            <span :style="{color: 
              investigations[row.statusKey] === 'Abnormal' ? 'red' : 
              investigations[row.statusKey] === 'Normal' ? 'green' : 'grey'}"
            >
              {{ investigations[row.statusKey] ?? '-' }}
            </span>
          </td>
          <td>{{ investigations[row.detailsKey] ?? '-' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- ============================ B) LABORATORY RESULTS (HYBRID) ============================ -->
    <h2 class="table-header" style="margin-top: 24px;">
      Laboratory Results
      <button :disabled="!accessCheck('edit')" class="action-btn" @click="openLabModal">Edit All Lab Results</button>
    </h2>

    <div class="info-container">
      <span class="info-icon" style="align-items: right;" @click="showInfo = !this.showInfo">ℹ️</span>

      <div v-if="showInfo" class="info-overlay" @click.self="showInfo = false">
        <div class="info-box">
          <h4 style="color:black">Hybrid Edit Mode</h4>
          <p style="color: black">
            You can opt to edit Lab Results <b>all at once</b> (Edit All Lab Results) or <b>each individual lines</b> (Action > Edit).<br>
            <b>Tip:</b> You can edit multiple lines using Edit button, then either 
            click the <b>'Save All'</b> or <b>'Cancel'</b> button in the bottom row to update/cancel all changes.
          </p>
          
          <button style="color: white; background-color: blue;" @click="showInfo = false">Got it</button>
        </div>
      </div>

    </div>
    
    <!-- :disabled="!accessCheck(s, 'edit')" -->

    <!-- Sticky top bar (only when there are pending changes) -->
    <!-- <div class="sticky-bar top" v-if="dirtyKeys.size">
      <button class="primary" @click="saveAllLab">Save All</button>
      <button @click="cancelAllLab">Cancel</button>
      <span class="muted">{{ dirtyKeys.size }} change(s) pending</span>
    </div> -->

    <div v-for="grp in labGroupOrder" :key="grp">
      <h3 style="margin-top: 16px;">{{ groupLabel(grp) }}</h3>
      <table class="std-table">
        <thead>
          <tr>
            <th style="width: 28%">Test</th>
            <th style="width: 10%">Abbrv.</th>
            <th style="width: 24%">Normal Range</th>
            <th style="width: 18%">Result</th>
            <th style="width: 18%">Value</th>
            <th>Remark</th>
            <th style="width: 110px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in labGroups[grp]" :key="item.key">
            <td>{{ item.test }}</td>
            <td>{{ item.abbr }}</td>
            <td v-html="item.range || '-'"></td>

            <!-- READ-ONLY VIEW -->
            <template v-if="editingRow !== item.key">
              <td>
                <span :style="{ color: 
                  (investigationsLab[`${item.key}_result`] === 'Abnormal' ? 'red' : 
                  investigationsLab[`${item.key}_result`] === 'Normal' ? 'green' : 'grey') 
                }">
                  {{ investigationsLab[`${item.key}_result`] ?? 'Not done' }}
                </span>
              </td>
              <td :style="{ opacity: investigationsLab[`${item.key}_value`] === null ? 0.5 : 1, color: investigationsLab[`${item.key}_value`] === null ? '#666' : '' }">
                {{ investigationsLab[`${item.key}_value`] ?? 'N/A' }}
              </td>
              <td>{{ investigationsLab[`${item.key}_remark`] ?? '-' }}</td>
              <td>
                <button :disabled="!accessCheck('edit')" class="mini-btn" @click="startRowEdit(item.key)">Edit</button>
              </td>
            </template>

            <!-- INLINE EDIT VIEW -->
            <template v-else>
              <td>
                <select v-model="editableLab[`${item.key}_result`]" @change="markDirty(item.key)">
                  <option>Not done</option>
                  <option>Normal</option>
                  <option>Abnormal</option>
                </select>
              </td>
              <td>
                <input type="text" v-model="editableLab[`${item.key}_value`]" @input="markDirty(item.key)" />
              </td>
              <td>
                <textarea
                  v-model="editableLab[`${item.key}_remark`]"
                  @input="markDirty(item.key)"
                  placeholder="Remark (optional)"
                ></textarea>
              </td>
              <td class="row-actions">
                <button class="mini-btn primary" :disabled="!isRowDirty(item.key)" @click="saveLabRow(item.key)">Save</button>
                <button class="mini-btn" @click="cancelRowEdit(item.key)">Cancel</button>
              </td>
            </template>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Sticky bottom bar -->
    <div class="sticky-bar bottom" v-if="dirtyKeys.size">
      <button class="primary" @click="saveAllLab">Save All</button>
      <button @click="cancelAllLab">Cancel</button>
      <span class="muted">{{ dirtyKeys.size }} change(s) pending</span>
    </div>

    <!-- ============================ C) URINE DRUG TESTS (TABLE) ============================= -->
    <h2 class="table-header" style="margin-top: 24px;">
      Urine Drug Test
      <button :disabled="!accessCheck('edit')" class="action-btn" @click="openDrugModal">Edit Drug Tests</button>
    </h2>
    <table class="std-table">
      <thead>
        <tr>
          <th style="width: 40%">Test</th>
          <th style="width: 25%">Result</th>
          <th>Remark</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in drugTests" :key="t.key">
          <td>{{ t.label }}</td>
          <td>
            <span 
            :style="{ color: 
              investigations[`${t.key}_result`] === 'Negative' ? 'green' : 
              investigations[`${t.key}_result`] === 'Non-negative' ? 'red' : 
              investigations[`${t.key}_result`] === 'Not done' ? 'grey' : '' }"
              >
              {{ investigations[`${t.key}_result`] ?? 'Not done' }}
            </span>
          </td>
          <td>{{ investigations[`${t.key}_remark`] ?? '-' }}</td>
        </tr>
      </tbody>
    </table>

    <div class="inv-box">
      <strong>Remarks by Examine Doctor : </strong> <small><i>(to be filled by OHD)</i></small>
      <div>{{ (investigations.remarks_ohd && investigations.remarks_ohd.trim()) ? investigations.remarks_ohd : '-' }}</div>
    </div>

    <!-- <button @click="$router.push('/dashboard')" style="margin-top: 1rem;">Back</button> -->

    <!-- ================================ MODAL: GENERAL ====================================== -->
    <div v-if="isGenModalOpen" class="modal-overlay" @click="closeGenModal">
      <div class="modal-content" @click.stop style="overflow-y:auto; max-height:90vh;">
        <h3>Edit General Investigations</h3>
        <form @submit.prevent="submitGeneral">
          <div class="two-col">
            <div v-for="row in generalRows" :key="row.key" class="card">
              <label class="lbl">{{ row.label }}</label>
              <select v-model="modalGeneral[row.statusKey]">
                <option :value="null">-</option>
                <option>Normal</option>
                <option>Abnormal</option>
              </select>
              <textarea v-model="modalGeneral[row.detailsKey]" placeholder="Details of any abnormality"></textarea>
            </div>
          </div>

          <div style="margin-top: 8px;">
            <button type="submit">Save Changes</button>
            <button type="button" @click="closeGenModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================================ MODAL: LAB (EDIT ALL) =============================== -->
    <div v-if="isLabModalOpen" class="modal-overlay" @click="closeLabModal">
      <div class="modal-content" @click.stop style="overflow-y:auto; max-height:90vh;">
        <h3>Edit All Laboratory Results</h3>
        <form @submit.prevent="submitLab">
          <div style="margin-top:10px;">
            <button type="submit" class="action-btn">Save Changes</button>
            <button type="button" @click="resetLab">Reset</button>
            <button type="button" @click="setNormalLab">Set All Normal</button>
            <button type="button" @click="setAbnormalLab">Set All Abnormal</button>
            <button type="button" @click="closeLabModal">Cancel</button>
          </div>
          <div v-for="grp in labGroupOrder" :key="grp">
            <h4 style="margin-top:10px;">{{ groupLabel(grp) }}</h4>
            <div class="inv-grid">
              <div class="inv-item" v-for="item in labGroups[grp]" :key="item.key">
                <div class="lbl">{{ item.test }} ({{ item.abbr }})</div>
                <div class="muted" v-html="item.range || '&nbsp;'"></div>
                <select v-model="modalLab[`${item.key}_result`]">
                  <option>Not done</option>
                  <option>Normal</option>
                  <option>Abnormal</option>
                </select>
                <input v-if="modalLab[`${item.key}_value`] !== undefined" type="text" v-model="modalLab[`${item.key}_value`]" placeholder="Value (Abnormal)">
                <textarea v-model="modalLab[`${item.key}_remark`]" placeholder="Remark (optional)"></textarea>
              </div>
            </div>
          </div>
          <div style="margin-top:10px;">
            <button type="submit" class="action-btn">Save Changes</button>
            <button type="button" @click="closeLabModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================================ MODAL: DRUG ========================================= -->
    <div v-if="isDrugModalOpen" class="modal-overlay" @click="closeDrugModal">
      <div class="modal-content" @click.stop style="overflow-y:auto; max-height:90vh;">
        <h3>Edit Urine Drug Test & OHD Remarks</h3>
        <form @submit.prevent="submitDrug">
          <div class="two-col">
            <div v-for="t in drugTests" :key="t.key" class="card">
              <label class="lbl">{{ t.label }} Result</label>
              <select v-model="modalDrug[`${t.key}_result`]">
                <option>Not done</option>
                <option>Negative</option>
                <option>Non-negative</option>
              </select>
              <textarea v-model="modalDrug[`${t.key}_remark`]" placeholder="Remark"></textarea>
            </div>
          </div>

          <div class="card">
            <label class="lbl">Remarks by Examine Doctor :</label>
            <textarea v-model="modalDrug.remarks_ohd" placeholder="Summary / conclusion"></textarea>
          </div>

          <div style="margin-top: 8px;">
            <button type="submit">Save Changes</button>
            <button type="button" @click="closeDrugModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import { canSession } from '@/shared/sessionAcl';
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

export default {
  inject: {
    currentSessionHeader: { from: 'currentSessionHeader', default: () => () => null },
    refreshSessionHeader: { from: 'refreshSessionHeader', default: () => async () => {} },
  },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      showInfo: false,

      investigations: {},
      investigationsLab: {},

      // Modals (General + LabAll + Drug)
      isGenModalOpen: false,
      isLabModalOpen: false,
      isDrugModalOpen: false,

      // Modal models
      modalGeneral: {},
      modalLab: {},
      modalDrug: {},

      // Inline Lab editing
      editableLab: {},
      originalLab: {},
      dirtyKeys: new Set(),
      editingRow: null, // row key currently inline editing, e.g. 'hb'

      // Table A config (General)
      generalRows: [
        { key: 'spirometry', label: 'Spirometry', statusKey: 'spirometry_status', detailsKey: 'spirometry_details' },
        { key: 'audiometry', label: 'Audiometry', statusKey: 'audiometry_status', detailsKey: 'audiometry_details' },
        { key: 'chest_xray', label: 'Chest X-ray', statusKey: 'chest_xray_status', detailsKey: 'chest_xray_details' },
        { key: 'electrocardiograph', label: 'Electrocardiograph', statusKey: 'electrocardiograph_status', detailsKey: 'electrocardiograph_details' },
      ],

      // Table B config (Lab)
      labGroupOrder: ['haematology','glucose','lipids','electrolytes','renal','liver','urineChem','urineMicro','serology'],
      labGroups: {
        haematology: [
          { key: 'hb',     test: 'Haemoglobin',          abbr: 'Hb',    range: '13 - 18 g/dl' },
          { key: 'rbc',    test: 'RBC',                  abbr: 'RBC',   range: '4.50 - 6.50 x 10^12/L' },
          { key: 'pcv',    test: 'PCV',                  abbr: 'PCV',   range: '0.40 - 0.55' },
          { key: 'mcv',    test: 'MCV',                  abbr: 'MCV',   range: '78 - 99 fl' },
          { key: 'mch',    test: 'MCH',                  abbr: 'MCH',   range: '27 - 32 pg' },
          { key: 'mchc',   test: 'MCHC',                 abbr: 'MCHC',  range: '300 - 600 g/L' },
          { key: 'rdw',    test: 'RDW',                  abbr: 'RDW',   range: '11.15%' },
          { key: 'wbc',    test: 'White Cell Count',     abbr: 'WBC',   range: '4 - 11 x 10^9/L' },
          { key: 'neut',   test: 'Neutrophils',          abbr: 'Neut',  range: '2.0 - 8.0 x 10^9/L' },
          { key: 'lym',    test: 'Lymphocytes',          abbr: 'Lym',   range: '1.0 - 4.0 x 10^9/L' },
          { key: 'mon',    test: 'Monocytes',            abbr: 'Mon',   range: '&lt;1.1 x 10^9/L' },
          { key: 'eon',    test: 'Eosinophils',          abbr: 'Eon',   range: '&lt;0.6 x 10^9/L' },
          { key: 'bas',    test: 'Basophils',            abbr: 'Bas',   range: '&lt;0.2 x 10^9/L' },
          { key: 'plet',   test: 'Platelets',            abbr: 'Plet',  range: '150 - 450 x 10^9/L' },
          { key: 'esr',    test: 'ESR',                  abbr: 'ESR',   range: '&lt;21 mm/hr' },
          { key: 'fbp',    test: 'Full Blood Picture',   abbr: 'FBP',   range: '' },
        ],
        glucose: [
          { key: 'fbs',    test: 'Fasting',              abbr: 'FBS',   range: '(3.9 - 6.0 mmol/L)' },
          { key: 'rbs',    test: 'Random',               abbr: 'RBS',   range: '(3.9 - 10.0 mmol/L)' },
        ],
        lipids: [
          { key: 'tchol',  test: 'Total Cholesterol',    abbr: 'Tchol', range: '&lt;5.2 mmol/L' },
          { key: 'tg',     test: 'Triglyceride',         abbr: 'TG',    range: '&lt;1.68 mmol/L' },
          { key: 'hdl',    test: 'HDL Cholesterol',      abbr: 'HDL',   range: '&gt;1.03 mmol/L' },
          { key: 'ldl',    test: 'LDL Cholesterol',      abbr: 'LDL',   range: '&lt;3.9 mmol/L' },
        ],
        electrolytes: [
          { key: 'na',     test: 'Sodium',               abbr: 'Na',    range: '135 - 145 mmol/L' },
          { key: 'k',      test: 'Potassium',            abbr: 'K',     range: '3.5 - 5.1 mmol/L' },
          { key: 'cl',     test: 'Chloride',             abbr: 'Cl',    range: '95 - 110 mmol/L' },
        ],
        renal: [
          { key: 'bu',     test: 'Urea',                 abbr: 'BU',    range: '2.5 - 8.0 mmol/L' },
          { key: 'creat',  test: 'Creatinine',           abbr: 'Creat', range: '53 - 115 µmol/L' },
          { key: 'ua',     test: 'Uric acid',            abbr: 'UA',    range: '153 - 425 µmol/L' },
          { key: 'ca',     test: 'Calcium',              abbr: 'Ca',    range: '2.10 - 2.60 mmol/L' },
          { key: 'cca',    test: 'Corrected Calcium',    abbr: 'Cca',   range: '2.10 - 2.60 mmol/L' },
          { key: 'po4',    test: 'Phosphate',            abbr: 'PO4',   range: '0.65 - 1.45 mmol/L' },
        ],
        liver: [
          { key: 'tprot',  test: 'Total Protein',        abbr: 'Tprot', range: '60 - 82 g/dl' },
          { key: 'alb',    test: 'Albumin',              abbr: 'Alb',   range: '35 - 50 g/dl' },
          { key: 'glo',    test: 'Globulin',             abbr: 'Glo',   range: '20 - 39 g/dl' },
          { key: 'agr',    test: 'Albumin/Globulin Ratio', abbr: 'AGR', range: '1.0 - 2.5' },
          { key: 'alkp',   test: 'Alkaline Phosphatase', abbr: 'AlkP',  range: '30 - 120 U/L' },
          { key: 'tbil',   test: 'Total Bilirubin',      abbr: 'Tbil',  range: '&lt;21 µmol/L' },
          { key: 'ggt',    test: 'Gamma Glutamyl Transferase', abbr: 'GGT', range: '&lt;51 U/L' },
          { key: 'ast',    test: 'Aspartate Transaminase', abbr: 'AST', range: '&lt;41 U/L' },
          { key: 'alt',    test: 'Alanine Transaminase', abbr: 'ALT',   range: '&lt;51 U/L' },
        ],
        urineChem: [
          { key: 'uprot',  test: 'Protein',              abbr: 'Uprot', range: 'Negative' },
          { key: 'uph',    test: 'pH',                   abbr: 'UpH',   range: 'Acidic' },
          { key: 'uglu',   test: 'Glucose',              abbr: 'Uglu',  range: 'Negative' },
          { key: 'uket',   test: 'Ketones',              abbr: 'Uket',  range: 'Negative' },
          { key: 'usg',    test: 'Specific Gravity',     abbr: 'USG',   range: '1.005 - 1.030' },
          { key: 'ubld',   test: 'Blood',                abbr: 'Ubld',  range: 'Negative' },
        ],
        urineMicro: [
          { key: 'uleu',   test: 'Leucocytes',           abbr: 'Uleu',  range: '&lt;10 x 10^6/L' },
          { key: 'uery',   test: 'Erythrocytes',         abbr: 'Uery',  range: '&lt;10 x 10^6/L' },
          { key: 'uecell', test: 'Epithelial Cells',     abbr: 'UEcell',range: 'Negative' },
          { key: 'ucc',    test: 'Cast and Crystal',     abbr: 'UCC',   range: 'Negative' },
        ],
        serology: [
          { key: 'vdrl',   test: 'VDRL',                 abbr: 'VDRL',  range: 'non-reactive' },
          { key: 'hbsag',  test: 'Hep B surface antigen',abbr: 'HbsAg', range: 'non-detected' },
          { key: 'hbsab',  test: 'Hep B surface antibody', abbr: 'HbsAb', range: '0 IU/L' },
          { key: 'hcs',    test: 'Hep C Antigen/Antibody', abbr: 'Hcs', range: '' },
        ]
      },

      // Table C config (Urine Drugs)
      drugTests: [
        { key: 'opiates', label: 'Opiates' },
        { key: 'cannabinoids', label: 'Cannabinoids' },
        { key: 'amphetamine', label: 'Amphetamine' },
        { key: 'mdma', label: 'MDMA' },
        { key: 'benzodiazepine', label: 'Benzodiazepine' },
      ],
    };
  },
  computed: {
    // step-by-step: call the injected function to get the header
    sessionHeader() {
      return this.currentSessionHeader ? this.currentSessionHeader() : null;
    },
    sessionStatus() {
      return this.sessionHeader?.status || 'draft';
    },
  },
  mounted() {
    this.fetchInvestigations();
    this.fetchInvestigationsLab().then(() => {
      this.seedLabEditors();
    });
  },

  methods: {
    // Access control wrapper
    accessCheck(action) {
      let role = null;
      try {
        role = JSON.parse(localStorage.getItem('user_info'))?.role || 'staff';
      } catch (e) { /* ignore */ }
      return canSession(role, this.sessionStatus, action);
    },
    groupLabel(k) {
      const L = {
        haematology: 'Haematology', glucose: 'Glucose', lipids: 'Lipids',
        electrolytes: 'Electrolytes', renal: 'Renal Function', liver: 'Liver Function',
        urineChem: 'Urinalysis (Chemistry)', urineMicro: 'Urinalysis (Microscopy)', serology: 'Serology'
      };
      return L[k] || k;
    },

    // ------------------------- fetchers -------------------------
    async fetchInvestigations() {
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      return fetch(`${this.baseUrl}/investigations/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;

        return res.json();
      })
      .then(d => { 
        this.investigations = Array.isArray(d) ? (d[0] || {}) : d; 
        // console.log("investigations: ",this.investigations); 
      })
      .catch(err => console.error(err));
    },
    async fetchInvestigationsLab() {
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      return fetch(`${this.baseUrl}/investigations-lab/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(d => { 
          this.investigationsLab = Array.isArray(d) ? (d[0] || {}) : d;
          // console.log("investigationsLab: ",this.investigationsLab);
        })
        .catch(err => console.error(err));
    },
    // -------------------- Seed / Dirty tracking for Lab --------------------
    seedLabEditors() {
      const seed = {};
      Object.values(this.labGroups).flat().forEach(item => {
        seed[`${item.key}_result`] = this.investigationsLab?.[`${item.key}_result`] ?? 'Not done';
        seed[`${item.key}_value`] = this.investigationsLab?.[`${item.key}_value`] ?? null;
        seed[`${item.key}_remark`] = this.investigationsLab?.[`${item.key}_remark`] ?? null;
      });
      this.editableLab = { ...seed };
      this.originalLab = JSON.parse(JSON.stringify(seed));
      this.dirtyKeys.clear();
      this.editingRow = null;
    },
    markDirty(itemKey) {
      const resKey = `${itemKey}_result`;
      const valKey = `${itemKey}_value`;
      const remKey = `${itemKey}_remark`;
      const dirty =
        this.editableLab[resKey] !== this.originalLab[resKey] ||
        (this.editableLab[valKey]) !== (this.originalLab[valKey]) ||
        (this.editableLab[remKey] ?? '') !== (this.originalLab[remKey] ?? '');
      if (dirty) this.dirtyKeys.add(itemKey);
      else this.dirtyKeys.delete(itemKey);
    },
    isRowDirty(itemKey) {
      return this.dirtyKeys.has(itemKey);
    },
    startRowEdit(itemKey) {
      // ensure editableLab is synced with latest investigationsLab before editing
      this.editableLab[`${itemKey}_result`] = this.investigationsLab?.[`${itemKey}_result`] ?? 'Not done';
      this.editableLab[`${itemKey}_value`] = this.investigationsLab?.[`${itemKey}_value`] ?? null;
      this.editableLab[`${itemKey}_remark`] = this.investigationsLab?.[`${itemKey}_remark`] ?? null;
      // reset dirty for clean comparison
      this.originalLab[`${itemKey}_result`] = this.editableLab[`${itemKey}_result`];
      this.originalLab[`${itemKey}_remark`] = this.editableLab[`${itemKey}_remark`];
      this.dirtyKeys.delete(itemKey);
      this.editingRow = itemKey;
    },
    cancelRowEdit(itemKey) {
      // revert this row from original
      this.editableLab[`${itemKey}_result`] = this.originalLab[`${itemKey}_result`];
      this.editableLab[`${itemKey}_value`] = this.originalLab[`${itemKey}_value`];
      this.editableLab[`${itemKey}_remark`] = this.originalLab[`${itemKey}_remark`];
      this.dirtyKeys.delete(itemKey);
      if (this.editingRow === itemKey) this.editingRow = null;
    },

    // ------------------------- Save Lab (inline / all) -------------------------
    saveLabRow(itemKey) {
      const payload = { ...this.investigationsLab };
      
      payload[`${itemKey}_result`] = this.editableLab[`${itemKey}_result`];
      payload[`${itemKey}_value`] = this.editableLab[`${itemKey}_value`];
      payload[`${itemKey}_remark`] = this.editableLab[`${itemKey}_remark`];
      payload.updated_by = JSON.parse(localStorage.getItem('user_info')).email;

      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g,'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/investigations-lab/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(() => {
        this.investigationsLab = payload;
        // sync originals for this row
        this.originalLab[`${itemKey}_result`] = this.editableLab[`${itemKey}_result`];
        this.originalLab[`${itemKey}_value`] = this.editableLab[`${itemKey}_value`];
        this.originalLab[`${itemKey}_remark`] = this.editableLab[`${itemKey}_remark`];
        this.dirtyKeys.delete(itemKey);
        if (this.editingRow === itemKey) this.editingRow = null;
        this.fetchInvestigationsLab();
      })
      .catch(err => console.error(err));
    },
    saveAllLab() {
      const payload = { ...this.investigationsLab, ...this.editableLab };
      payload.updated_by = JSON.parse(localStorage.getItem('user_info')).email;
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g,'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/investigations-lab/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(() => {
        this.investigationsLab = payload;
        this.seedLabEditors();
      })
      .catch(err => console.error(err));
    },
    cancelAllLab() {
      this.editableLab = JSON.parse(JSON.stringify(this.originalLab));
      this.dirtyKeys.clear();
      this.editingRow = null;
    },

    // ------------------------- Modals open/close + submit -------------------------
    openGenModal() {
      const cur = this.investigations || {};
      const seed = {};
      this.generalRows.forEach(row => {
        seed[row.statusKey]  = cur[row.statusKey]  ?? null;
        seed[row.detailsKey] = cur[row.detailsKey] ?? null;
      });
      // keep remarks_ohd untouched here (edited in drug modal)
      this.modalGeneral = seed;
      this.isGenModalOpen = true;
    },
    closeGenModal() { this.isGenModalOpen = false; this.fetchInvestigations(); },
    submitGeneral() {
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g,'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      const merged = { ...(this.investigations || {}), ...this.modalGeneral };
      merged.updated_by = JSON.parse(localStorage.getItem('user_info')).email;
      fetch(`${this.baseUrl}/investigations/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}`, 'Content-Type': 'application/json' },
        body: JSON.stringify(merged)
      })
        .then(r => r.json())
        .then(() => { this.fetchInvestigations(); this.closeGenModal(); })
        .catch(err => console.error(err));
    },

    openLabModal() {
      const defaults = {};
      Object.values(this.labGroups).flat().forEach(item => {
        defaults[`${item.key}_result`] = 'Not done';
        // defaults[`${item.key}_value`] = null; // commented, as this would force *_value to all tests
        defaults[`${item.key}_remark`] = null;
      });
      this.modalLab = { ...defaults, ...(this.investigationsLab || {}) };
      this.modalLab.updated_by = JSON.parse(localStorage.getItem('user_info')).email;
      this.isLabModalOpen = true;
    },
    setNormalLab() {
      // Iterate through all groups (Hematology, Urine, etc.)
      Object.keys(this.labGroups).forEach(groupKey => {
        // Iterate through all tests in that group
        this.labGroups[groupKey].forEach(test => {
          // Set the result field (e.g., 'hb_result') to 'Normal'
          this.modalLab[`${test.key}_result`] = 'Normal';
          this.modalLab[`${test.key}_value`] = null;
          
          // Optional: Clear any existing remarks if setting to Normal?
          // this.modalLab[`${test.key}_remark`] = ''; 
        });
      });
    },

    setAbnormalLab() {
      Object.keys(this.labGroups).forEach(groupKey => {
        this.labGroups[groupKey].forEach(test => {
          // Set the result field to 'Abnormal'
          this.modalLab[`${test.key}_result`] = 'Abnormal';
        });
      });
    },

    resetLab() {
      // Reset all dropdowns to 'Not done' (or empty string if preferred)
      Object.keys(this.labGroups).forEach(groupKey => {
        this.labGroups[groupKey].forEach(test => {
          this.modalLab[`${test.key}_result`] = 'Not done';
          this.modalLab[`${test.key}_value`] = null;
          this.modalLab[`${test.key}_remark`] = ''; 
        });
      });
    },
    closeLabModal() { this.isLabModalOpen = false; this.fetchInvestigationsLab(); },
    submitLab() {
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g,'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/investigations-lab/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}`, 'Content-Type': 'application/json' },
        body: JSON.stringify(this.modalLab)
      })
        .then(r => r.json())
        .then(() => {
          this.fetchInvestigationsLab().then(() => this.seedLabEditors());
          this.closeLabModal();
        })
        .catch(err => console.error(err));
    },

    openDrugModal() {
      const cur = this.investigations || {};
      const seed = {};
      this.drugTests.forEach(t => {
        seed[`${t.key}_result`] = cur[`${t.key}_result`] ?? 'Not done';
        seed[`${t.key}_remark`] = cur[`${t.key}_remark`] ?? null;
      });
      seed.remarks_ohd = cur.remarks_ohd ?? null;
      this.modalDrug = seed;
      this.isDrugModalOpen = true;
    },
    closeDrugModal() { this.isDrugModalOpen = false; this.fetchInvestigations(); },
    submitDrug() {
      // const staffEmailXYZ = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g,'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      const merged = { ...(this.investigations || {}), ...this.modalDrug };
      merged.updated_by = JSON.parse(localStorage.getItem('user_info')).email;

      fetch(`${this.baseUrl}/investigations/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}`, 'Content-Type': 'application/json' },
        body: JSON.stringify(merged)
      })
        .then(r => r.json())
        .then(() => { this.fetchInvestigations(); this.closeDrugModal(); })
        .catch(err => console.error(err));
    },
  }
};
</script>

<style>

/* Inline row actions */
.row-actions {
  display: flex;
  gap: 6px;
  align-items: center;
}

/* Cards / grids for modals */
.two-col {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
  gap: 12px;
}

.lbl { display:block; font-weight:600; margin-bottom:6px; }
.muted { font-size: 12px; color: #666; margin-bottom: 6px; }
.inv-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
  gap: 12px;
  margin-bottom: 8px;
}
.inv-item {
  background: #f8f8f8;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #ddd;
}

/* Controls */
select, textarea {
  width: 100%;
  margin-top: 6px;
}
textarea {
  min-height: 60px;
  resize: vertical;
}
button {
  margin-top: 10px;
  margin-right: 8px;
  padding: 6px 12px;
  font-size: 13px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #edf2f7;
  color: #2d3748;
  transition: background 0.2s ease;
}
button:hover { background: #e2e8f0; }
button.primary {
  background: #2f855a;
  color: #fff;
}
button.primary:hover { background: #276749; }

.mini-btn {
  background: #4299e1;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 12px;
  cursor: pointer;
}
.mini-btn:disabled {
  background: #cbd5e0;
  color: #2d3748a8;
  cursor: not-allowed;
  opacity: 1;
}
.mini-btn.primary {
  background: #2f855a;
}
.mini-btn.primary:hover { background: #276749; }

/* Sticky bars for long sections */
.sticky-bar {
  position: sticky;
  z-index: 5;
  background: #fff;
  border: 1px solid #e2e8f0;
  padding: 8px 10px;
  display: flex;
  gap: 8px;
  align-items: center;
}
.sticky-bar.top { top: 0; }
.sticky-bar.bottom { bottom: 0; margin-top: 8px; }
.muted { color: #718096; font-size: 12px; }
</style>
