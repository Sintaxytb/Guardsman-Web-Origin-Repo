<script lang="ts">
import axios from "@axios"
import { VDataTable } from "vuetify/lib/labs/components.mjs"

interface IGroupRole {
  id: number
  group_id: number
  role_name: string
  position: number
}

interface IUser {
  id: number
  username: string
  roblox_id: string
  discord_id: string
  roles: string[]
  group_roles: { [groupId: string]: IGroupRole[] }
  game_data: string
  created_at: string
  updated_at: string
}

export default defineComponent({
  props: [
    "group",
    "groupUsers",
    "groupRoleRefs",
    "groupGames",
  ],
  emits: ["openUserDialog", "openGameWhitelistDialog", "openUserRoleDialog", "openRemoveUserDialog"],

  components: {
    VDataTable
  },

  data() {
    const loadingButtons: { [id: string]: boolean } = {};
    return {
      showToast: this.$showToast,

      userListHeaders: [
        {
          title: 'Reference Id',
          sortable: true,
          key: 'id',
        },

        {
          title: 'Username',
          sortable: true,
          key: 'username',
        },

        {
          title: 'Roblox Id',
          sortable: true,
          key: 'roblox_id',
        },

        {
          title: 'Discord Id',
          sortable: true,
          key: 'discord_id',
        },

        {
          title: 'Roles',
          sortable: true,
          key: 'group_roles',
        },

        {
          title: 'Delete',
          sortable: false,
          key: 'delete',
        },
      ],

      whitelistedGameListHeaders: [
        {
          title: 'Reference Id',
          sortable: true,
          key: 'id',
        },

        {
          title: 'Game Name',
          sortable: true,
          key: 'name',
        },

        {
          title: 'Place Id',
          sortable: true,
          key: 'place_id',
        },

        {
          title: 'Delete',
          sortable: true,
          key: 'delete',
        },
      ],

      usersOverlayShowing: ref<boolean>(false),
      loadingButtons
    }
  },

  methods: {
    onRolesUpdated(userId: number, menuOpened: boolean) {
      if (menuOpened) return;

      const value = this.groupRoleRefs[userId];
      this.usersOverlayShowing = true;

      const roleRefs = this.groupRoleRefs[userId];
      const roles: any[] = [];

      console.log(this.group);

      for (const roleRef of roleRefs) {
        const role = this.group.roles.find((role: IGroupRole) => role.role_name == roleRef);
        if (!role) continue;
        
        roles.push(role.id);
      }

      axios.patch(`user/${userId}/group/${this.group.id}/roles`, {
        roles
      })
        .then(response => {
          
        })
        .catch(error => {
          this.showToast("error", error.toString());
        })
        .finally(() => {
          this.usersOverlayShowing = false;
        })
    }
  },

  computed: {
    computedGroupUsers(): IUser[] {
      const users = this.groupUsers

      for (const user of users) {
        let parsedRoles = user.roles

        try {
          parsedRoles = JSON.parse(user.roles).join(", ")
        } catch (error) {}

        user.roles = parsedRoles
      }

      return users
    },

    groupRoleSelectItems() {
      return this.group.roles.map((role: IGroupRole) => role.role_name)
    }
  }
})

</script>

<template>
  <VRow>
    <VCol cols="12" lg="7" md="7">
      <VCard title="Users" prepend-icon="tabler-user">
        <template #append>
          <VBtn
            block
            class="justify-center"
            @click="$emit('openUserDialog')"
          >
            <VIcon
              icon="tabler-plus"
              class="mr-2"
            />
            Add User
          </VBtn>
        </template>

        <VOverlay
          v-model="usersOverlayShowing"
          contained
          persistent
          class="align-center justify-center"
        >
          <VProgressCircular indeterminate />
        </VOverlay>

        <VDataTable
          :headers="userListHeaders"
          :items="computedGroupUsers"
          class="elevation-1 pb-6"
        >
          <template #item.group_roles="{ item }">
            <VSelect 
              @update:menu="(enabled) => onRolesUpdated(item.id, enabled)" 
              multiple 
              v-model="groupRoleRefs[item.id]" 
              :items="groupRoleSelectItems"
              placeholder="None"
            />
          </template>

          <template #item.delete="{ item }">
            <VBtn
              @click="$emit('openRemoveUserDialog', item)"
              :loading="loadingButtons[item.id]"
              block
              color="error"
              variant="tonal"
            >
              <VIcon icon="tabler-trash" />
            </VBtn>
          </template>
        </VDataTable>
      </VCard>
    </VCol>
    <VCol cols="12" lg="5" md="5">
      <VCard title="Whitelisted Games" prepend-icon="tabler-clipboard-text">
        <template #append>
          <VBtn
            block
            class="justify-center"
            @click="$emit('openGameWhitelistDialog')"
          >
            <VIcon
              icon="tabler-plus"
              class="mr-2"
            />
            Add Game
          </VBtn>
        </template>

        <VDataTable
          class="pb-6"
          :headers="whitelistedGameListHeaders"
          :items="groupGames"
        />
      </VCard>
    </VCol>
  </VRow>
</template>
