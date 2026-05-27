// main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './styles/main.css'
// import { formatDate } from './shared/dateFormat.js'

createApp(App)
    .use(router)
    .mount('#app')
    // .config.globalProperties.$formatDate = formatDate
