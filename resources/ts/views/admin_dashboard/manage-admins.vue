<script lang="ts">
import AddAdminTab from "@/views/admin_dashboard/tabs/add-admin.vue";
import RemoveAdminTab from "@/views/admin_dashboard/tabs/remove-admin.vue";

export default defineComponent({
  components: {
    AddAdminTab,
    RemoveAdminTab
  },

  data() {
    const ability: any = this.$ability;

    return {
      ability,
      currentTab: ref<number>(0),
    }
  }
});

</script>

<template>
  <AppCardActions 
    title="Manage Administrators" 
    icon="tabler-user-cog" 
    v-if="ability.can('administrate', 'make-user')"
    action-collapsed
  >
    <VRow class="pa-6">
      <VCol class="12">
        <VCard variant="flat">
          <VRow>
            <VCol cols="5" sm="4">
              <VTabs v-model="currentTab" direction="vertical">
                <VTab>
                  <VIcon start icon="tabler-user-plus" />
                  Add Admin
                </VTab>

                <VTab>
                  <VIcon start icon="tabler-user-minus" />
                  Remove Admin
                </VTab>
              </VTabs>
            </VCol>

            <VDivider vertical />

            <VCol cols="7" sm="8">
              <VWindow v-model="currentTab" class="ms-3 pa-3">
                <AddAdminTab />
                <RemoveAdminTab />
              </VWindow>
            </VCol>
          </VRow>
        </VCard>
      </VCol>
    </VRow>
  </AppCardActions>
</template>
