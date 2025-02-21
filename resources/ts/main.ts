import { createApp } from 'vue'

import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import useAbility from "@/plugins/casl/ability"
import { abilitiesPlugin } from '@casl/vue'
import '@core-scss/template/index.scss'
import * as Sentry from "@sentry/vue"
import '@styles/styles.scss'
import guardsman from './plugins/guardsman'

// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)

app.use(abilitiesPlugin, useAbility(), {
  useGlobalProperties: true
});
app.use(guardsman)
                                                                                                                                                                              
Sentry.init({
  app,                                                                                                                                                                
  dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,                                                                                                                                
});

app.mixin({
  methods: {
    $showToast: App.methods?.showToast
  }
})

// Mount vue app
app.mount('#app')
