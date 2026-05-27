<template>
  <div class="export-page">
    <header class="top">
      <h2>Export Medical Record</h2>
      <div style="height: 1rem;"></div>
    </header>

    <section class="status" v-if="busy">
      Preparing data… please wait.
    </section>
    <section class="status error" v-else-if="loadError">
      Failed to load: {{ loadError }}
    </section>

    <section v-else class="ready">
      <!-- You can show a summary or counts here if you like -->
      <p>Data is ready. Click "Export" button below to download.</p>
    </section>

    <div class="meta">
      <div>Session: <strong>{{ sessionId || '—' }}</strong></div>
      <div>Staff: <strong>{{ staffEmail || '—' }}</strong></div>
    </div>
    <div class="actions">
      <button :disabled="busy" @click="reload">{{ busy ? 'Loading…' : 'Reload' }}</button>
      <button @click="$router.back()">Back</button>
    </div>

    <!-- Exporter: will open automatically after fetch -->
    <OneSheetExport
      ref="oneSheet"
      :sections="sections"
      :filename="`medical-record-${staffEmail}.xlsx`"
      title="Export medical record (one sheet)"
    />
  </div>
</template>

<script>
import OneSheetExport from '@/components/OneSheetExport.vue';
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

// tiny formatters
const yesNo  = v => (v === true ? 'Yes' : v === false ? 'No' : (v ?? ''));
const fmtDate = v => (v ?? '');

export default {
  name: 'ExportMedicalRecord',
  components: { OneSheetExport },

  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      busy: false,
      loadError: '',
      // query
      sessionId: null,
      staffEmail: null,
      // datasets
      personalInfo: null,
      occupationalRows: [],
      familyRows: [],
      famDisease: null,
      medicalHistory: null,
      medicalFields: [
        { key: 'abnormal_heartbeat', label: 'Abnormal Heartbeat' },
        { key: 'bladder_trouble', label: 'Bladder Trouble' },
        { key: 'dermatitis_eczema', label: 'Dermatitis / Eczema' },
        { key: 'depression', label: 'Depression' },
        { key: 'heart_murmur', label: 'Heart Murmur' },
        { key: 'hernia', label: 'Hernia' },
        { key: 'jaundice', label: 'Jaundice' },
        { key: 'kidney_disease', label: 'Kidney Disease' },
        { key: 'peptic_ulcer', label: 'Peptic Ulcer' },
        { key: 'persistent_night_sweats', label: 'Persistent Night Sweats' },
        { key: 'rectal_bleeding', label: 'Rectal Bleeding' },
        { key: 'unintentional_weight_loss', label: 'Unintentional Weight Loss' },
        { key: 'asthma_bronchitis', label: 'Asthma / Bronchitis' },
        { key: 'bowel_disorder', label: 'Bowel Disorder' },
        { key: 'diabetes', label: 'Diabetes' },
        { key: 'frequent_indigestion', label: 'Frequent Indigestion' },
        { key: 'high_blood_pressure', label: 'High Blood Pressure' },
        { key: 'hospitalisation_surgery', label: 'Hospitalisation / Surgery' },
        { key: 'migraine_headache', label: 'Migraine / Headache' },
        { key: 'psoriasis_skin_disease', label: 'Psoriasis / Skin Disease' },
        { key: 'persistent_diarrhoea', label: 'Persistent Diarrhoea' },
        { key: 'renal_colic_stone', label: 'Renal Colic / Stone' },
        { key: 'swollen_lymph_glands', label: 'Swollen Lymph Glands' },
        { key: 'anxiety', label: 'Anxiety' },
        { key: 'blood_in_urine', label: 'Blood in Urine' },
        { key: 'dizziness_giddiness', label: 'Dizziness / Giddiness' },
        { key: 'faints_blackouts', label: 'Faints / Blackouts' },
        { key: 'hay_fever', label: 'Hay Fever' },
        { key: 'joint_disorder', label: 'Joint Disorder' },
        { key: 'liver_gall_bladder', label: 'Liver / Gall / Bladder' },
        { key: 'piles_haemorrhoids', label: 'Piles / Haemorrhoids' },
        { key: 'rheumatic_fever', label: 'Rheumatic Fever' },
        { key: 'std', label: 'STD' },
        { key: 'tuberculosis', label: 'Tuberculosis' },
        { key: 'none_of_the_above', label: 'None of the Above' },
      ],
      lifestyle: null,
      pe1: null,
      pe2: null,
      inv: null,
      lab: null,
    };
  },

  computed: {
    sections() {
      // Define columns (same as we used in Dashboard option B)
      const personalInfoCols = [
        { key:'staff_name',  label:'Name' },
        { key:'staff_email', label:'Email' },
        { key:'staff_no',    label:'Staff No' },
        { key:'job_title',   label:'Job Title / Position' },
        { key:'department', label: 'Department' },
        { key:'marital_status', label:'Marital Status' },
        { key:'sex', label:'Sex' },
        { key:'date_of_birth', label:'Date of Birth', format: fmtDate },
        { key:'ic_passport', label:'IC/Passport' },
        { key:'nationality', label:'Nationality' },
        { key:'phone_no', label:'Phone' },
        { key:'address', label:'Address' },
        { key:'personal_doctor_email', label:'Personal Doctor Email' },
        { key:'doctor_phone_no', label:'Doctor Phone' },
        { key:'reason_for_examination', label:'Reason' },
        { key:'date_of_this_assessment', label:'Date of This Assessment', format: fmtDate },
        { key:'date_of_last_assessment', label:'Date of Last Assessment', format: fmtDate },
        { key:'created_at',  label:'Created At', format: fmtDate },
        { key:'updated_at',  label:'Updated At', format: fmtDate },
      ];

      const occupationalCols = [
        { key:'year',           label:'Year' },
        { key:'company',       label:'Company' },
        { key:'location',          label:'Location' },
        { key:'job_title',      label:'Job Title' },
        { key:'nature_of_work', label:'Nature of Work' },
      ];

      const familyCols = [
        { key:'relationship',               label:'Relationship' },
        { key:'relative_name',              label:'Name' },
        { key:'sex',                        label:'Sex' },
        { key:'year_of_born',               label:'Year of Birth' },
        { key:'age_now',                    label:'Age Now' },
        { key:'age_at_death',               label:'Age at Death' },
        { key:'state_health_death_cause',   label:'State of Health / Cause of Death' },
        { key:'updated_at',                 label:'Updated At', format: fmtDate },
      ];

      const famDiseaseCols = [
        { key:'heart_disease',      label:'Heart Disease', format: yesNo },
        { key:'high_blood_pressure',label:'High Blood Pressure', format: yesNo },
        { key:'stroke',             label:'Stroke', format: yesNo },
        { key:'cancer',             label:'Cancer', format: yesNo },
        { key:'diabetes',           label:'Diabetes', format: yesNo },
        { key:'kidney_disease',     label:'Kidney Disease', format: yesNo },
        { key:'allergy',            label:'Allergy', format: yesNo },
        { key:'asthma',             label:'Asthma', format: yesNo },
        { key:'eczema',             label:'Eczema', format: yesNo },
        { key:'tuberculosis',       label:'Tuberculosis', format: yesNo },
        { key:'epilepsy',           label:'Epilepsy', format: yesNo },
        { key:'mental_disorder',    label:'Mental Disorder', format: yesNo },
        { key:'alcohol_dependence', label:'Alcohol Dependence', format: yesNo },
        { key:'drug_abuse',         label:'Drug Abuse', format: yesNo },
        { key:'birth_abnormality',  label:'Birth Abnormality', format: yesNo },
        { key:'details',            label:'Details' },
        { key:'created_at',         label:'Created At', format: fmtDate },
        { key:'updated_at',         label:'Updated At', format: fmtDate },
      ];

      const medicalCols = [
        { key:'abnormal_heartbeat', label:'Abnormal heartbeat', format: yesNo },
        { key:'abnormal_heartbeat', label:'Abnormal heartbeat', format: yesNo },
        { key:'bladder_trouble', label:'Bladder trouble', format: yesNo },
        { key:'dermatitis_eczema', label:'Dermatitis/Eczema', format: yesNo },
        { key:'depression', label:'Depression', format: yesNo },
        { key:'heart_murmur', label:'Heart Murmur', format: yesNo },
        { key:'hernia', label:'Hernia', format: yesNo },
        { key:'jaundice', label:'Jaundice', format: yesNo },
        { key:'kidney_disease', label:'Kidney Disease', format: yesNo },
        { key:'peptic_ulcer', label:'Peptic Ulcer', format: yesNo },
        { key:'persistent_night_sweats', label:'Persistent Night Sweats', format: yesNo },
        { key:'rectal_bleeding', label:'Rectal Bleeding', format: yesNo },
        { key:'unintentional_weight_loss', label:'Unintentional Weight Loss', format: yesNo },
        { key:'asthma_bronchitis', label:'Asthma/Bronchitis', format: yesNo },
        { key:'bowel_disorder', label:'Bowel Disorder', format: yesNo },
        { key:'diabetes', label:'Diabetes', format: yesNo },
        { key:'frequent_indigestion', label:'Frequent Indigestion', format: yesNo },
        { key:'high_blood_pressure', label:'High Blood Pressure', format: yesNo },
        { key:'hospitalisation_surgery', label:'Hospitalisation/Surgery', format: yesNo },
        { key:'migraine_headache', label:'Migraine Headache', format: yesNo },
        { key:'psoriasis_skin_disease', label:'Psoriasis Skin Disease', format: yesNo },
        { key:'persistent_diarrhoea', label:'Persistent Diarrhoea', format: yesNo },
        { key:'renal_colic_stone', label:'Renal Colic Stone', format: yesNo },
        { key:'swollen_lymph_glands', label:'Swollen Lymph Glands', format: yesNo },
        { key:'anxiety', label:'Anxiety', format: yesNo },
        { key:'blood_in_urine', label:'Blood in Urine', format: yesNo },
        { key:'dizziness_giddiness', label:'Dizziness/Giddiness', format: yesNo },
        { key:'faints_blackouts', label:'Faints/Blackouts', format: yesNo },
        { key:'hay_fever', label:'Hay Fever', format: yesNo },
        { key:'joint_disorder', label:'Joint Disorder', format: yesNo },
        { key:'liver_gall_bladder', label:'Liver Gall Bladder', format: yesNo },
        { key:'piles_haemorrhoids', label:'Piles/Haemorrhoids', format: yesNo },
        { key:'rheumatic_fever', label:'Rheumatic Fever', format: yesNo },
        { key:'std', label:'STD', format: yesNo },
        { key:'tuberculosis', label:'Tuberculosis', format: yesNo },
        { key:'none_of_the_above', label:'None of the above', format: yesNo },
        { key:'comment_by_examine_doctor', label:'Doctor Comment' },
        { key:'created_at', label:'Created At', format: fmtDate },
      ];

      const lifestyleCols = [
        { key:'smoking_habit',       label:'Smoking Habit' },
        { key:'years_smoked',        label:'Years Smoked' },
        { key:'amount_smoke_day',    label:'Amount/Day' },
        { key:'date_stopped',        label:'Date Stopped', format: fmtDate },
        { key:'alcohol_drink',       label:'Alcohol Drink' },
        { key:'drink_per_week',      label:'Drinks/Week' },
        { key:'taking_prescribed_drugs', label:'Taking Prescribed Drugs' },
        { key:'drug_detail',         label:'Drug Detail' },
        { key:'declaration_consent', label:'Consent', format: yesNo },
        { key:'consent_signer_name', label:'Consent Signer' },
        { key:'consent_signer_date', label:'Consent Date', format: fmtDate },
        { key:'created_at',          label:'Created At', format: fmtDate },
        { key:'updated_at',          label:'Updated At', format: fmtDate },
      ];

      const pe1Cols = [
        { key:'pe_id',           label:'PE ID' },
        { key:'staff_email',    label:'Staff Email' },
        { key:'session_id',     label:'Session ID' },
        { key:'weight_kg',      label:'Weight (kg)' },
        { key:'height_m',       label:'Height (m)' },
        { key:'bmi',             label:'BMI' },
        { key:'bp_sys',          label:'BP Systolic' },
        { key:'bp_dia',          label:'BP Diastolic' },
        { key:'pulse_bpm',       label:'Pulse (bpm)' },
        { key:'blood_group',     label:'Blood Group' },
        { key:'dist_uncorr_r',    label:'Distance Uncorrected Right Eye' },
        { key:'dist_uncorr_l',   label:'Distance Uncorrected Left Eye' },
        { key:'dist_uncorr_b',    label:'Distance Uncorrected Both Eyes' },
        { key:'dist_corr_r',      label:'Distance Corrected Right Eye' },
        { key:'dist_corr_l',      label:'Distance Corrected Left Eye' },
        { key:'dist_corr_b',      label:'Distance Corrected Both Eyes' },
        { key:'near_uncorr_r',    label:'Near Uncorrected Right Eye' },
        { key:'near_uncorr_l',   label:'Near Uncorrected Left Eye' },
        { key:'near_uncorr_b',    label:'Near Uncorrected Both Eyes' },
        { key:'near_corr_r',      label:'Near Corrected Right Eye' },
        { key:'near_corr_l',      label:'Near Corrected Left Eye' },
        { key:'near_corr_b',      label:'Near Corrected Both Eyes' },
        { key:'colour_vision',    label:'Colour Vision' },
        { key:'created_at',     label:'Created At', format: fmtDate },
        { key:'updated_at',     label:'Updated At', format: fmtDate },
      ];

      const pe2Cols = [
        { key:'pe2_id',            label:'PE2 ID' },
        { key:'staff_email',        label:'Staff Email' },
        { key:'session_id',        label:'Session ID' },
        { key:'head',            label:'Head' },
        { key:'head_details_abnormality', label:'Head Details' },
        { key:'eyes',            label:'Eyes' },
        { key:'eyes_details_abnormality', label:'Eyes Details' },
        { key:'ears_and_drums', label:'Ears and Drums' },
        { key:'ears_and_drums_details_abnormality', label:'Ears and Drums Details' },
        { key:'hearing',            label:'Hearing' },
        { key:'hearing_details_abnormality', label:'Hearing Details' },
        { key:'nose_and_sinuses', label:'Nose and Sinuses' },
        { key:'nose_and_sinuses_details_abnormality', label:'Nose and Sinuses Details' },
        { key:'mouth_teeth_throat', label:'Mouth, Teeth, Throat' },
        { key:'mouth_teeth_throat_details_abnormality', label:'Mouth, Teeth, Throat Details' },
        { key:'neck_and_thyroid', label:'Neck and Thyroid' },
        { key:'neck_and_thyroid_details_abnormality', label:'Neck and Thyroid Details' },
        { key:'chest_and_lungs', label:'Chest and Lungs' },
        { key:'chest_and_lungs_details_abnormality', label:'Chest and Lungs Details' },
        { key:'breasts',            label:'Breasts' },
        { key:'breasts_details_abnormality', label:'Breasts Details' },
        { key:'heart',            label:'Heart' },
        { key:'heart_details_abnormality', label:'Heart Details' },
        { key:'peripheral_arteries', label:'Peripheral Arteries' },
        { key:'peripheral_arteries_details_abnormality', label:'Peripheral Arteries Details' },
        { key:'peripheral_veins', label:'Peripheral Veins' },
        { key:'peripheral_veins_details_abnormality', label:'Peripheral Veins Details' },
        { key:'abdomen',            label:'Abdomen' },
        { key:'abdomen_details_abnormality', label:'Abdomen Details' },
        { key:'hernia_orifices', label:'Hernia Orifices' },
        { key:'hernia_orifices_details_abnormality', label:'Hernia Orifices Details' },
        { key:'genitalia',        label:'Genitalia' },
        { key:'genitalia_details_abnormality', label:'Genitalia Details' },
        { key:'rectal_examination', label:'Rectal Examination' },
        { key:'rectal_examination_details_abnormality', label:'Rectal Examination Details' },
        { key:'upper_limbs',        label:'Upper Limbs' },
        { key:'upper_limbs_details_abnormality', label:'Upper Limbs Details' },
        { key:'lower_limbs',        label:'Lower Limbs' },
        { key:'lower_limbs_details_abnormality', label:'Lower Limbs Details' },
        { key:'spine',            label:'Spine' },
        { key:'spine_details_abnormality', label:'Spine Details' },
        { key:'skin',            label:'Skin' },
        { key:'skin_details_abnormality', label:'Skin Details' },
        { key:'lymph_nodes',        label:'Lymph Nodes' },
        { key:'lymph_nodes_details_abnormality', label:'Lymph Nodes Details' },
        { key:'neurological',        label:'Neurological' },
        { key:'neurological_details_abnormality', label:'Neurological Details' },
        { key:'psychiatric',        label:'Psychiatric' },
        { key:'psychiatric_details_abnormality', label:'Psychiatric Details' },
        { key:'created_at',        label:'Created At', format: fmtDate },
        { key:'updated_at',        label:'Updated At', format: fmtDate },
      ];

      const invCols = [
        { key:'inv_id',            label:'Inv ID' },
        { key:'staff_email',      label:'Staff Email' },
        { key:'session_id',     label:'Session ID' },
        { key:'spirometry_status', label:'Spirometry Status' },
        { key:'spirometry_details',label:'Spirometry Details' },
        { key:'audiometry_status',  label:'Audiometry Status' },
        { key:'audiometry_details', label:'Audiometry Details' },
        { key:'chest_xray_status', label:'Chest Xray Status' },
        { key:'chest_xray_details', label:'Chest Xray Details' },
        { key:'electrocardiograph_status', label:'Electrocardiograph Status' },
        { key:'electrocardiograph_details', label:'Electrocardiograph Details' },
        // urine drug tests:
        { key:'opiates_result',       label:'Opiates Result' },
        { key:'opiates_remark',     label:'Opiates Remark' },
        { key:'cannabinoids_result', label:'Cannabinoids Result' },
        { key:'cannabinoids_remark',   label:'Cannabinoids Remark' },
        { key:'amphetamine_result', label:'Amphetamine Result' },
        { key:'amphetamine_remark', label:'Amphetamine Remark' },
        { key:'mdma_result',         label:'MDMA Result' },
        { key:'mdma_remark',       label:'MDMA Remark' },
        { key:'benzodiazepine_result', label:'Benzodiazepine Result' },
        { key:'benzodiazepine_remark', label:'Benzodiazepine Remark' },
        { key:'remarks_ohd',       label:'Remarks by Examine Doctor' },
        { key:'created_at',        label:'Created At', format: fmtDate },
        { key:'updated_at',        label:'Updated At', format: fmtDate },
      ];

      const labCols = [
        { key:'ilab_id',       label:'ILAB ID' },
        { key:'staff_email',     label:'Staff Email' },
        { key:'session_id',     label:'Session ID' },
        { key:'hb_result',      label:'Hb Result' },
        { key:'hb_remark',      label:'Hb Remark' },
        { key:'rbc_result',      label:'RBC Result' },
        { key:'rbc_remark',      label:'RBC Remark' },
        { key:'pcv_result',      label:'PCV Result' },
        { key:'pcv_remark',      label:'PCV Remark' },
        { key:'mcv_result',      label:'MCV Result' },
        { key:'mcv_remark',      label:'MCV Remark' },
        { key:'mch_result',      label:'MCH Result' },
        { key:'mch_remark',      label:'MCH Remark' },
        { key:'mchc_result',     label:'MCHC Result' },
        { key:'mchc_remark',     label:'MCHC Remark' },
        { key:'rdw_result',      label:'RDW Result' },
        { key:'rdw_remark',      label:'RDW Remark' },
        { key:'wbc_result',      label:'WBC Result' },
        { key:'wbc_remark',      label:'WBC Remark' },
        { key:'neut_result',     label:'Neut Result' },
        { key:'neut_remark',     label:'Neut Remark' },
        { key:'lym_result',      label:'Lym Result' },
        { key:'lym_remark',      label:'Lym Remark' },
        { key:'mon_result',      label:'Mon Result' },
        { key:'mon_remark',      label:'Mon Remark' },
        { key:'eon_result',      label:'Eon Result' },
        { key:'eon_remark',      label:'Eon Remark' },
        { key:'bas_result',      label:'Bas Result' },
        { key:'bas_remark',      label:'Bas Remark' },
        { key:'plet_result',     label:'Plet Result' },
        { key:'plet_remark',     label:'Plet Remark' },
        { key:'esr_result',      label:'ESR Result' },
        { key:'esr_remark',      label:'ESR Remark' },
        { key:'fbp_result',      label:'FBP Result' },
        { key:'fbp_remark',      label:'FBP Remark' },
        { key:'fbs_result',      label:'FBS Result' },
        { key:'fbs_remark',      label:'FBS Remark' },
        { key:'rbs_result',      label:'RBS Result' },
        { key:'rbs_remark',      label:'RBS Remark' },
        { key:'tchol_result',     label:'Tchol Result' },
        { key:'tchol_remark',     label:'Tchol Remark' },
        { key:'tg_result',      label:'TG Result' },
        { key:'tg_remark',      label:'TG Remark' },
        { key:'hdl_result',      label:'HDL Result' },
        { key:'hdl_remark',      label:'HDL Remark' },
        { key:'ldl_result',      label:'LDL Result' },
        { key:'ldl_remark',      label:'LDL Remark' },
        { key:'na_result',      label:'Na Result' },
        { key:'na_remark',      label:'Na Remark' },
        { key:'k_result',       label:'K Result' },
        { key:'k_remark',       label:'K Remark' },
        { key:'cl_result',      label:'Cl Result' },
        { key:'cl_remark',      label:'Cl Remark' },
        { key:'bu_result',      label:'BU Result' },
        { key:'bu_remark',      label:'BU Remark' },
        { key:'creat_result',     label:'Creat Result' },
        { key:'creat_remark',     label:'Creat Remark' },
        { key:'ua_result',      label:'UA Result' },
        { key:'ua_remark',      label:'UA Remark' },
        { key:'ca_result',      label:'Ca Result' },
        { key:'ca_remark',      label:'Ca Remark' },
        { key:'cca_result',     label:'Cca Result' },
        { key:'cca_remark',     label:'Cca Remark' },
        { key:'po4_result',      label:'PO4 Result' },
        { key:'po4_remark',      label:'PO4 Remark' },
        { key:'tprot_result',     label:'Tprot Result' },
        { key:'tprot_remark',     label:'Tprot Remark' },
        { key:'alb_result',      label:'Alb Result' },
        { key:'alb_remark',      label:'Alb Remark' },
        { key:'glo_result',      label:'Glo Result' },
        { key:'glo_remark',      label:'Glo Remark' },
        { key:'agr_result',      label:'AGR Result' },
        { key:'agr_remark',      label:'AGR Remark' },
        { key:'alkp_result',     label:'AlkP Result' },
        { key:'alkp_remark',     label:'AlkP Remark' },
        { key:'tbil_result',     label:'Tbil Result' },
        { key:'tbil_remark',     label:'Tbil Remark' },
        { key:'ggt_result',      label:'GGT Result' },
        { key:'ggt_remark',      label:'GGT Remark' },
        { key:'ast_result',      label:'AST Result' },
        { key:'ast_remark',      label:'AST Remark' },
        { key:'alt_result',      label:'ALT Result' },
        { key:'alt_remark',      label:'ALT Remark' },
        { key:'uprot_result',     label:'Uprot Result' },
        { key:'uprot_remark',     label:'Uprot Remark' },
        { key:'uph_result',      label:'UpH Result' },
        { key:'uph_remark',      label:'UpH Remark' },
        { key:'uglu_result',     label:'Uglu Result' },
        { key:'uglu_remark',     label:'Uglu Remark' },
        { key:'uket_result',     label:'Uket Result' },
        { key:'uket_remark',     label:'Uket Remark' },
        { key:'usg_result',      label:'USG Result' },
        { key:'usg_remark',      label:'USG Remark' },
        { key:'ubld_result',     label:'Ubld Result' },
        { key:'ubld_remark',     label:'Ubld Remark' },
        { key:'uleu_result',     label:'Uleu Result' },
        { key:'uleu_remark',     label:'Uleu Remark' },
        { key:'uery_result',     label:'Uery Result' },
        { key:'uery_remark',     label:'Uery Remark' },
        { key:'uecell_result',     label:'UEcell Result' },
        { key:'uecell_remark',     label:'UEcell Remark' },
        { key:'ucc_result',      label:'UCC Result' },
        { key:'ucc_remark',      label:'UCC Remark' },
        { key:'vdrl_result',     label:'VDRL Result' },
        { key:'vdrl_remark',     label:'VDRL Remark' },
        { key:'hbsag_result',     label:'HBsAG Result' },
        { key:'hbsag_remark',     label:'HBsAG Remark' },
        { key:'hbsab_result',     label:'HBsAB Result' },
        { key:'hbsab_remark',     label:'HBsAB Remark' },
        { key:'hcs_result',      label:'Hep C Result' },
        { key:'hcs_remark',      label:'Hep C Remark' },
      ];

      return [
        { id:'personal', title:'Personal Info',         kind:'kv',    data: this.personalInfo,     columns: personalInfoCols },
        { id:'occ',      title:'Occupational History',  kind:'table', data: this.occupationalRows, columns: occupationalCols },
        { id:'fam',      title:'Family History',        kind:'table', data: this.familyRows,       columns: familyCols },
        { id:'fhd',      title:'Family History Disease',kind:'kv',    data: this.famDisease,       columns: famDiseaseCols },
        { id:'mh',       title:'Medical History',       kind:'kv',    data: this.medicalHistory,   columns: medicalCols },
        { id:'lifestyle',title:'Lifestyle',             kind:'kv',    data: this.lifestyle,        columns: lifestyleCols },
        { id:'pe1',      title:'Physical Exams',        kind:'kv',    data: this.pe1,              columns: pe1Cols },
        { id:'pe2',      title:'Physical Exams 2',      kind:'kv',    data: this.pe2,              columns: pe2Cols },
        { id:'inv',      title:'Investigations',        kind:'kv',    data: this.inv,              columns: invCols },
        { id:'lab',      title:'Investigations (Lab)',    kind:'kv',    data: this.lab,              columns: labCols },
      ];
    },
  },

  created() {
    // read query params
    const q = this.$route.query || {};
    this.sessionId = q.session_id || null;
    this.staffEmail = q.staff_email || null;
  },

  async mounted() {
    await this.reload();
  },

  methods: {
    async reload() {
      this.busy = true;
      this.loadError = '';

      try {
        // Fetch all datasets for this (session_id, staff_email)
        const params = { session_id: this.sessionId, staff_email: this.staffEmail };

        this.personalInfo = await this.apiFetchPersonal(params);
        this.occupationalRows = await this.apiFetchOccHistory(params);
        this.familyRows = await this.apiFetchFamily(params);
        this.famDisease = await this.apiFetchFamDisease(params);
        this.medicalHistory = await this.apiFetchMedicalHistory(params);
        this.lifestyle = await this.apiFetchLifestyle(params);
        this.pe1 = await this.apiFetchPE1(params);
        this.pe2 = await this.apiFetchPE2(params);
        this.inv = await this.apiFetchInvestigations(params);
        this.lab = await this.apiFetchLab(params);

        // Open the export dialog automatically after data is ready
        this.$nextTick(() => this.$refs.oneSheet && this.$refs.oneSheet.open());
      } catch (e) {
        console.error(e);
        this.loadError = e?.message || 'Unknown error';
      } finally {
        this.busy = false;
      }
    },

    // ====== API calls ======
    async apiFetchPersonal({ staff_email }) {
        // let staffData = {};
        const staffEmailXYZ = staff_email.replace(/\./g, 'XYZ');
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/staff/info/${encodeURIComponent(staffEmailXYZ)}`, {
            method: 'GET',
            headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
            'Content-Type': 'application/json'
            }
        })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
            this.personalInfo = data || {};
        })
        .catch(err => {
            console.error('Error fetching staff info:', err);
            alert('Failed to fetch staff info.');
        });
        return this.personalInfo;
    },
    async apiFetchOccHistory({ staff_email }) { 
      // let occupationalHistory = [];
      const staffEmailXYZ = staff_email.replace(/\./g, 'XYZ');
      // const baseUrl = cfg.API_BASE_URL;

      fetch(`${this.baseUrl}/occupational-history/${staffEmailXYZ}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(res => res.json())
        .then(data => {
          this.occupationalRows = data;
        })
        .catch(err => {
          console.error(err);
          this.occupationalRows = [];
        });
        return this.occupationalRows;
    },
    async apiFetchFamily({ staff_email }) {
        // let familyHistory = [];
        const staffEmailXYZ = staff_email.replace(/\./g, 'XYZ');
        // const baseUrl = cfg.API_BASE_URL;

      fetch(`${this.baseUrl}/family-history/${staffEmailXYZ}`, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}` 
        }
      })
        .then(res => res.json())
        .then(data => {
          this.familyRows = data.family_history;
        })
        .catch(err => console.error(err));
        return this.familyRows;
    },
    async apiFetchFamDisease({ staff_email }) { 
        // let familyHistoryDisease = [];
        const staffEmailXYZ = staff_email.replace(/\./g, 'XYZ');
        // const baseUrl = cfg.API_BASE_URL;
        
        fetch(`${this.baseUrl}/family-history-disease/${staffEmailXYZ}`, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}` 
        }
      })
        .then(res => res.json())
        .then(data => {

          // if data is an array, take the first element
          this.famDisease = Array.isArray(data) ? data[0] || {} : data;

          // convert numbers to booleans
          Object.keys(this.famDisease).forEach(key => {
            if (key !== 'staff_email' && key !== 'created_at' && key !== 'updated_at' && key !== 'details') {
              this.famDisease[key] = !!this.famDisease[key]; // 0 -> false, 1 -> true
            }
          });
        })
        .catch(err => console.error(err));
        return this.famDisease;
    },
    async apiFetchMedicalHistory({ session_id }) {
        // let medicalHistory = {};
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/medical-history/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(res => res.json())
        .then(raw => {
          const data = Array.isArray(raw) ? (raw[0] || {}) : raw

          // start from defaults so every key exists
          const normalized = (() => {
            const base = {}
            this.medicalFields.forEach(f => { base[f.key] = false })
            base.comment_by_examine_doctor = ''
            return base
          })()

          // convert Y/N to boolean where present
          this.medicalFields.forEach(f => {
            const v = data[f.key]
            if (v === 'Y') normalized[f.key] = true
            else if (v === 'N') normalized[f.key] = false
            // if API already returns booleans, this also works:
            else if (typeof v === 'boolean') normalized[f.key] = v
          })

          normalized.comment_by_examine_doctor = data.comment_by_examine_doctor || ''

          this.medicalHistory = normalized;
        })
        .catch(err => console.error(err))
        return this.medicalHistory;
    },
    async apiFetchLifestyle({ session_id }) { 
        // let lifestyle = {};
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/lifestyle/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(res => res.json())
        .then(data => {
            this.lifestyle = Array.isArray(data) ? data[0] || {} : data;
        })
        .catch(err => console.error(err));
        return this.lifestyle;
    },
    async apiFetchPE1({ session_id })           { 
        // let physicalExam = {};
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/physical-exams/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(res => res.json())
        .then(data => {
            this.pe1 = Array.isArray(data) ? data[0] || {} : data;
        })
        .catch(err => console.error(err));
        return this.pe1;
    },
    async apiFetchPE2({ session_id }) {
        // let physicalExam2 = {};
        // const baseUrl = cfg.API_BASE_URL;
        fetch(`${this.baseUrl}/physical-exams-2/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(res => res.json())
        .then(data => {
            this.pe2 = Array.isArray(data) ? data[0] || {} : data;
        })
        .catch(err => console.error(err));
        return this.pe2;
    },
    async apiFetchLab({ session_id }) {
        // let investigationsLab = {};
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/investigations-lab/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(r => r.json())
        .then(d => { 
        this.lab = Array.isArray(d) ? (d[0] || {}) : d;
        })
        .catch(err => console.error(err));
        return this.lab;
    },
    async apiFetchInvestigations({ session_id }) { 
        // let investigations = {};
        // const baseUrl = cfg.API_BASE_URL;

        fetch(`${this.baseUrl}/investigations/${session_id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        })
        .then(r => r.json())
        .then(d => { 
            this.inv = Array.isArray(d) ? (d[0] || {}) : d; 
        })
        .catch(err => console.error(err));
        return this.inv;
    },
  },
};
</script>

<style scoped>
.top { display:flex; align-items:flex-start; gap:16px; justify-content:space-between; margin-bottom:12px; }
.meta { display:flex; gap:20px; opacity:.9; }
.actions { display:flex; gap:8px; }
.status { padding:10px; }
.status.error { color:#b00020; }
</style>
