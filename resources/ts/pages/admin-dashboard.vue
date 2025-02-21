<route lang="yaml">
meta:
  title: Admin Dashboard
  action: administrate
  subject: administrate
</route>

<script lang="ts">
import AdminList from "@/views/admin_dashboard/admin-list.vue";
import CommandLogs from "@/views/admin_dashboard/command-logs.vue";
import OnlineAdmins from "@/views/admin_dashboard/online-admins.vue";

export default defineComponent({
  components: {
    OnlineAdmins,
    CommandLogs,
    AdminList
  }
});

</script>

<template>
  <div>
    <VRow>
      <VCol
        cols="12"
        lg="6"
        md="6"
        sm="6"
      >
        <OnlineAdmins />
      </VCol>
      
      <VCol
        cols="12"
        lg="6"
        md="6"
        sm="6"
      >
        <CommandLogs />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12">
        <AdminList />
      </VCol>
    </VRow>
  </div>
</template>
