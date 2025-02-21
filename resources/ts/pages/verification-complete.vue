<route lang="yaml">
  meta:
    layout: blank
    title: Verification Complete
    auth: none
</route>

<script lang="ts">
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { requiredValidator } from "@validators";

export default defineComponent({
  components: {
    VNodeRenderer
  },

  data() {
    return {
      themeConfig,
      requiredValidator,
    }
  }
});

</script>

<template>
  <VRow
    class="auth-wrapper"
    style="block-size: 101.25vh; inline-size: 100vw;"
  >

    <VCol 
      cols="12" 
      lg="12" 
      class="d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="800"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText style="text-align: center;">
          <VNodeRenderer 
            :nodes="themeConfig.app.logo"
            class="mb-6"
            style="inline-size: 10rem;"
          />

          <h5 class="text-h5 font-weight-semibold mb-1">
            Welcome to {{ themeConfig.app.title }}!
          </h5>
          <p class="mb-0">
            Your verification has been successful. You may now continue in to the guild.
          </p>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>


<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
