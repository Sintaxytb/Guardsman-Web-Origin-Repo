<route lang="yaml">
meta:
  title: "Dashboard"
</route>

<script lang="ts">
import { baseUrl } from "@axios";
import ChangePassword from "@/views/dashboard/password.vue";
import Shout from "@/views/dashboard/shout.vue";
import Statistics from "@/views/dashboard/statistics.vue";
import { themeConfig } from "@themeConfig";

export default defineComponent({
  components: {
    Shout,
    Statistics,
    ChangePassword,
  },

  data() {
    const route = this.$route;
    const query = route.query;
    
    switch (query.status) {
      case "LOGIN":
        this.$showToast("success", `You have successfully logged in. Welcome to ${themeConfig.app.title}.`);
        break;
      case "ENOACCESS":
        this.$showToast("error", `You do not have access to that page.`);
        break;
    }

    // const inStaging = ref<boolean>(true);
    const inStaging = ref<boolean>(baseUrl.includes("staging.guardsman"));

    return {
      inStaging,
      stagingDialogOpen: ref<boolean>(true),
    }
  }
});
    
</script>

<template>
  <div>
    <VDialog v-if="inStaging" v-model="stagingDialogOpen">
      <DialogCloseBtn @click="stagingDialogOpen = false" />
      <VCard title="Guardsman Staging">
        <VCardText>Welcome to the Guardsman <strong>Staging environment</strong>. This site was made so developers and project leads can test features before general deployment. If you notice a bug on staging, report it on the GitLab!</VCardText>
        
        <VRow class="pa-3">
          <VCol cols="12" class="d-flex gap-4">
            <VBtn @click="stagingDialogOpen = false">Got it.</VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VDialog>

    <Shout />
    <Statistics />
    <ChangePassword />
  </div>
</template>
