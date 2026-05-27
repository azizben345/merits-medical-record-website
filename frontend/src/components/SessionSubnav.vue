<template>
  <nav class="subnav">
    <router-link :to="p('overview')"        exact-active-class="active">Overview</router-link>
    <router-link :to="p('lifestyle')"        exact-active-class="active">Lifestyle</router-link>
    <router-link :to="p('medical-history')"  exact-active-class="active">Medical History</router-link>
    <router-link :to="p('physical')"         exact-active-class="active">Physical Exams</router-link>
    <router-link :to="p('investigations')"   exact-active-class="active">Investigations</router-link>
  </nav>
</template>

<script>
export default {
  props:{ sessionId:[String,Number], canEdit:Boolean },
  methods:{
    p(child){ return `/staff/sessions/${this.sessionId}/${child}`; }
  }
}
</script>

<style scoped>
.subnav{position:sticky;top:60px;z-index:1;background:#111827;color:#fff;border-radius:10px;padding:10px 12px;display:flex;gap:14px;flex-wrap:wrap}
.subnav a{color:#e5e7eb;text-decoration:none;padding:6px 10px;border-radius:8px}
.subnav a.active{background:#374151;color:#fff}
</style>