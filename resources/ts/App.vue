<script lang="ts">
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore, useConfigStore } from '@core/stores/config'
import { hexToRgb } from '@layouts/utils'
import { useTheme } from 'vuetify'

const toastOpen = ref<boolean>(false);
const toastType = ref<string>('success');
const toastMessage = ref<string>('');

export default defineComponent({
  components: {
    ScrollToTop
  },

  data() {
    initCore()
    initConfigStore()

    const configStore = useConfigStore()

    const { global } = useTheme()

    return {
      configStore,
      global,

      toastOpen,
      toastType,
      toastMessage
    }
  },

  methods: {
    hexToRgb,

    showToast(type: string, message: string) {
      toastType.value = type || "success";
      toastMessage.value = message;
      toastOpen.value = true;
    }
  }
});

</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.colors.primary)}`">
      <VSnackbar
        v-model="toastOpen"
        location="bottom end"
        variant="flat"
        :color="toastType"
      >
        {{ toastMessage }}
      </VSnackbar>

      <RouterView />

      <ScrollToTop />
    </VApp>
  </VLocaleProvider>
</template>
