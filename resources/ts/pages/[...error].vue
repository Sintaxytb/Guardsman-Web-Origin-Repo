<script setup lang="ts">

definePage({
  alias: '/pages/misc/not-found/:error(.*)',
  meta: {
    layout: 'blank',
    public: true,
  },
})

</script>

<template>
  <div class="misc-wrapper">
    <ErrorHeader
      status-code="404"
      title="Page Not Found ⚠️"
      description="We couldn't find the page you are looking for."
    />

    <VBtn
      to="/"
      class="mb-12"
    >
      Back to Home
    </VBtn>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/misc.scss";
</style>
