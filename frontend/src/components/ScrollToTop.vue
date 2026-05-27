<template>
  <button
    v-show="visible"
    class="scroll-top"
    @click="toTop"
    aria-label="Scroll to top"
  >
    ↑
  </button>
</template>

<script>
export default {
  name: 'ScrollToTop',
  data() {
    return { visible: false, rafId: null };
  },
  mounted() {
    // Use rAF to avoid running too often while scrolling
    const onScroll = () => {
      if (this.rafId) return;
      this.rafId = requestAnimationFrame(() => {
        this.visible = window.scrollY > 150; // show after this amount of px
        this.rafId = null;
      });
    };
    this._onScroll = onScroll;
    window.addEventListener('scroll', onScroll, { passive: true });
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this._onScroll);
    if (this.rafId) cancelAnimationFrame(this.rafId);
  },
  methods: {
    toTop() {
      const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: prefersReduced ? 'auto' : 'smooth' });
    }
  }
};
</script>

<style scoped>
.scroll-top {
  position: fixed;
  right: 22px;
  bottom: 22px;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 9999px;
  background: #1f2937; /* slate-800 */
  color: #fff;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(0,0,0,.2);
  opacity: 40%;
  transform: translateY(10px);
  transition: opacity .2s ease, transform .2s ease, background .15s ease;
  z-index: 3000; 
}

.scroll-top:hover { background: #111827; }  /* slate-900 */
.scroll-top:focus { outline: 2px solid #60a5fa; outline-offset: 2px; }

.scroll-top[v-cloak], .scroll-top:not([style]) { display: none; } /* safety */

/* v-show toggles display; we want a fade—so rely on opacity/transform */
.scroll-top[style*="display: none"] { display: none !important; }
</style>
