<script lang="ts">
import { VDataTable } from 'vuetify/lib/labs/components.mjs';

export default defineComponent({
  props: [
    "roles"
  ],
  emits: ["openRoleDialog", "openPermissionsDialog", "openMembersDialog", "deleteRole", "openGuildWhitelistDialog"],
  components: { VDataTable },

  data() {
    return {
      roleHeaders: [
        {
          title: "ID",
          key: "id"
        },

        {
          title: "ROLE NAME",
          key: "role_name"
        },

        {
          title: "POSITION",
          key: "position"
        },

        {
          title: "PERMISSIONS",
          key: "permissions"
        },

        {
          title: "DELETE",
          key: "delete"
        }
      ],

      whitelistedGuildsHeaders: [
        {
          title: 'Reference Id',
          sortable: true,
          key: 'id',
        },

        {
          title: 'Guild Id',
          sortable: true,
          key: 'guild_id',
        },

        {
          title: 'Delete',
          sortable: true,
          key: 'delete',
        },
      ],
    }
  }
})

</script>

<template>
  <VRow>
    <VCol cols="12" lg="7" md="7">
      <VCard title="Roles" prepend-icon="tabler-users">
        <template #append>
          <VBtn
            block
            class="justify-center"
            @click="$emit('openRoleDialog')"
          >
            <VIcon
              icon="tabler-plus"
              class="mr-2"
            />
            Add Role
          </VBtn>
        </template>
        <VDataTable
          :headers="roleHeaders"
          :items="roles"
          class="elevation-1 pb-6"
        >
          <template #item.permissions="{ item }">
            <VBtn @click="$emit('openPermissionsDialog', item, true)"> List </VBtn>
          </template>
          
          <template #item.delete="{ item }">
            <VBtn color="error" variant="tonal" @click="$emit('deleteRole', item)"><VIcon icon="tabler-trash" /></VBtn>
          </template>
        </VDataTable>
      </VCard>
    </VCol>

    <VCol cols="12" lg="5" md="5">
      <VCard title="Whitelisted Guilds" prepend-icon="tabler-clipboard-text">
        <template #append>
          <VBtn
            block
            class="justify-center"
            @click="$emit('openGuildWhitelistDialog')"
          >
            <VIcon
              icon="tabler-plus"
              class="mr-2"
            />
            Add Guild
          </VBtn>
        </template>

        <VDataTable
          class="pb-6"
          :headers="whitelistedGuildsHeaders"
        />
      </VCard>
    </VCol>
  </VRow>
</template>
