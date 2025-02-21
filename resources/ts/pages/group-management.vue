<route lang="yaml">
meta:
  title: Group Management
  action: administrate
  subject: manage-group
</route>

<script lang="ts">
import ErrorHeader from '@/components/ErrorHeader.vue';
import PermissionsDialog from '@/components/permissions.vue';
import Metadata from '@/views/group_management/metadata.vue';
import RolesAndGuilds from "@/views/group_management/roles_and_guilds.vue";
import UsersAndGames from "@/views/group_management/users_and_games.vue";
import PermissionsList from '@/views/panel_management/permissionsList.vue';
import axios from "@axios";
import { minMaxValidator, requiredValidator } from '@validators';
import { VDataTable } from 'vuetify/lib/labs/components.mjs';

interface IPermissionNode {
  action: string
  subject: string
}

const LazyAppBarSearch = defineAsyncComponent(() => import('@core/components/AppBarSearch.vue'))

interface IGroup {
  id: number
  group_name: string
  group_id: number
  roles: IGroupRole[]
  users: IUser[]
  games: string[]
  guilds: string[]
  permissions: { action: string, subject: string }[]
}

interface IGroupRole {
  id: number
  group_id: number
  role_name: string
  position: number
  permissions: { [node: string]: boolean }
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

interface IWhitelistedGame {
  id: number
  name: string
  place_id: number
}

interface IPermissionNode {
  action: string
  subject: string
}

interface IRole {
  id: number
  name: string
  position: number
  permissions: string
}

export default defineComponent({
  components: {
    LazyAppBarSearch,
    Metadata,
    UsersAndGames,
    RolesAndGuilds,
    ErrorHeader,
    PermissionsDialog,
    PermissionsList,
    VDataTable
},

  data() {
    const groups = ref<IGroup[]>([
      {
        id: -1,
        group_id: -1,
        group_name: "Unknown",
        roles: [],
        users: [],
        games: [],
        guilds: [],
        permissions: []
      }
    ]);

    const group = ref<IGroup>(groups.value[0])

    const groupName = ref<string>(groups.value[0].group_name)
    const groupId = ref<number>(groups.value[0].id)
    const groupRobloxId = ref<number>(groups.value[0].group_id)
    const groupUsers = ref<IUser[]>([])
    const groupWhitelistedGames = ref<string[]>([]);
    const groupPermissions = ref<{ action: string, subject: string }[]>([]);
    const groupRoleRefs: { [id: string]: Ref<string[]> } = {};

    const noGroupAccess = ref<boolean>(true)

    axios.get('user/groups')
      .then(response => {
        if (response.data.length != 0) {
          groups.value = response.data;

          const defaultGroup = groups.value[0];

          group.value = defaultGroup;
          groupId.value = defaultGroup.id;
          groupName.value = defaultGroup.group_name;
          groupRobloxId.value = defaultGroup.group_id;
          groupUsers.value = defaultGroup.users;
          groupWhitelistedGames.value = defaultGroup.games;
          groupPermissions.value = defaultGroup.permissions;

          for (const user of defaultGroup.users) {
            if (!user.group_roles[defaultGroup.id]) {
              groupRoleRefs[user.id] = ref<string[]>([]);
              continue
            }

            groupRoleRefs[user.id] = ref<string[]>(user.group_roles[defaultGroup.id].map(role => role.role_name));
          }

          noGroupAccess.value = false;
        }
      })
      .catch(error => {
        
      })

    const permissions = ref<any[]>([]);
    const rootPermissions = ref<any[]>([]);
    const allowedPermissions = ref<any[]>([]);

    axios.get("permissions")
      .then(response => {
        rootPermissions.value = response.data;

        response.data = response.data.filter((node: IPermissionNode) => node.action != "administrate" && node.action != "override");

        allowedPermissions.value = [...response.data, ...groupPermissions.value];
        
        permissions.value = response.data.map((node: IPermissionNode) => {
          return {
            node: node.action + ":" + node.subject,
            enabled: ref<boolean>(false)
          }
        });
      });

    const addUserSearchQuery = ref<string>('')
    const addUserSearchResults = ref<any[]>([])

    watchEffect(() => {      
      if(addUserSearchQuery.value == "") return;
      axios.get(`search/${addUserSearchQuery.value}`)
        .then(response => {
          addUserSearchResults.value = response.data
        })
    })

    const loadingButtons = ref<{ [id: string]: any }>({});

    return {
      requiredValidator,
      minMaxValidator,

      showToast: this.$showToast,
      loadingButtons,

      permissionsDialogOpen: ref<boolean>(false),
      permissionsDialogIsUpdate: ref<boolean>(false),
      rootPermissions,
      allowedPermissions,
      role: {
        id: ref<number>(-1),
        name: ref<string>(''),
        position: ref<number>(-1),
        permissions
      },

      groups,
      noGroupAccess,
      group,

      groupName,
      groupId,
      groupRobloxId,
      groupUsers,
      groupWhitelistedGames,
      groupPermissions,
      groupRoleRefs,

      addUserDialogOpen: ref<boolean>(false),
      addUserSearchQuery,
      addUserSearchResults,

      whitelistGameDialogOpen: ref<boolean>(false),
      whitelistGameDialogPlaceId: ref<string>(''),
      whitelistGameSubmitting: ref<boolean>(false),

      removeUserDialogOpen: ref<boolean>(false),
      removeUser: ref<IUser>({
        id: 0,
        username: '',
        roblox_id: '',
        discord_id: '',
        roles: [],
        group_roles: {},
        game_data: '',
        created_at: '',
        updated_at: ''
      }),
      
      deleteRoleDialogOpen: ref<boolean>(false),
      deleteRole: ref<IGroupRole>({
        id: 0,
        group_id: 0,
        role_name: '',
        position: 0,
        permissions: {}
      }),

      userRoleDialogOpen: ref<boolean>(false),
      userRoleUser: ref<IUser>({
        id: 0,
        username: '',
        roblox_id: '',
        discord_id: '',
        roles: [],
        group_roles: {},
        game_data: '',
        created_at: '',
        updated_at: ''
      }),

      roleCreateDialogOpen: ref<boolean>(false),
      roleName: ref<string>(''),
      rolePosition: ref<number>(0),

      createPermissionDialogOpen: ref<boolean>(false),
      createPermissionCreating: ref<boolean>(false),
      createPermissionAction: ref<string>(''),
      createPermissionSubject: ref<string>(''),

      deletePermissionDialogOpen: ref<boolean>(false),
      deletePermissionDeleting: ref<boolean>(false),
      deletePermissionItem: ref<any>({
        action: "",
        subject: ""
      }),
    }
  },
  methods: {
    onGroupChanged(groupName: string) {
      const group = this.groups.find(group => group.group_name == groupName)

      if (!group)
        return

      this.group = group
      this.groupName = group.group_name
      this.groupId = group.id
      this.groupRobloxId = group.group_id
      this.groupUsers = group.users;
      this.groupWhitelistedGames = group.games;
      this.groupPermissions = group.permissions;
      this.groupRoleRefs = {};

      for (const user of this.group.users) {
        if (!user.group_roles[this.group.id]) {
          this.groupRoleRefs[user.id] = ref<string[]>([]);
          continue
        }

        this.groupRoleRefs[user.id] = ref<string[]>(user.group_roles[this.group.id].map(role => role.role_name));
      }
    },

    openGameWhitelistDialog() {
      this.whitelistGameDialogPlaceId = ''
      this.whitelistGameSubmitting = false
      this.whitelistGameDialogOpen = true
    },

    addUserItemSelected(item: IUser) {
      const users = this.groupUsers.map(user => user.id);
      users.push(item.id);

      axios.patch(`group/${this.group.id}/users`, {
        users
      })
        .then(response => {
          this.showToast("success", `Successfully added ${item.username} to the group.`)
          this.groupUsers = response.data.users;
          this.addUserDialogOpen = false;
        })
        .catch(error => {
          this.showToast("error", error.toString())
        })
    },

    whitelistGame() {
      const whitelistGameForm: any = this.$refs.whitelistGameForm

      whitelistGameForm?.validate().then(({ valid: isValid }: any) => {
        if (isValid) {

        }
      })
    },

    openPermissionsDialog(role: IGroupRole, isUpdate?: boolean) {
      this.role.id = role.id;
      this.role.name = role.role_name;
      this.role.position = role.position;

      for (const node of this.role.permissions) {
        node.enabled = role.permissions[node.node]
      }

      this.permissionsDialogOpen = true
      this.permissionsDialogIsUpdate = isUpdate || false;
    },

    updateGroupRole(role: any) {
      const permissions: any = {};

      for (const permission of role.permissions) {
        permissions[permission.node] = permission.enabled
      }

      axios.patch(`group/${this.group.id}/role`, {
        role_name: role.name,
        position: role.position,
        permissions
      })
        .then(response => {
          this.group.roles = response.data.roles;
          this.permissionsDialogOpen = false;
          this.roleCreateDialogOpen = false;
          this.showToast("success", `Successfully updated role ${role.name}.`);
        })
        .catch(response => {

        })
    },

    finalizeGroupRole() {
      const createGroupRoleForm: any = this.$refs.createGroupRoleForm;

      createGroupRoleForm?.validate().then(({ valid: isValid } : any) => {
        if (!isValid) return;

        this.openPermissionsDialog({
          id: 0,
          group_id: this.groupId,
          role_name: this.roleName,
          position: this.rolePosition,
          permissions: {}
        })
      })
    },

    createGroupRole(role: any) {
      if (this.permissionsDialogIsUpdate) return this.updateGroupRole(role);

      const permissions: any = {};

      for (const permission of role.permissions) {
        permissions[permission.node] = permission.enabled
      }

      axios.post(`group/${this.group.id}/role`, {
        role_name: role.name,
        position: role.position,
        permissions
      })
        .then(response => {
          this.group.roles = response.data.roles;
          this.permissionsDialogOpen = false;
          this.roleCreateDialogOpen = false;
          this.showToast("success", `Successfully created role ${role.name}.`);
        })
        .catch(response => {

        })
    },

    createPermission() {
      const createPermissionNodeForm: any = this.$refs.createPermissionNodeForm;
      
      createPermissionNodeForm.validate().then(({ valid: isValid } : any) => {
        if (!isValid) return;

        this.createPermissionCreating = true;

        axios.post(`group/${this.group.id}/permission`, {
          action: this.createPermissionAction,
          subject: this.createPermissionSubject
        })
          .then(response => {            
            this.showToast("success", `Successfully created node ${this.createPermissionAction}:${this.createPermissionSubject}!`);

            this.createPermissionDialogOpen = false;
            this.createPermissionAction = "";
            this.createPermissionSubject = "";

            let filteredPermissions = [];
            
            filteredPermissions = response.data.root_permissions.filter((node: IPermissionNode) => node.action != "administrate" && node.action != "override");
            
            this.allowedPermissions = [...filteredPermissions, ...response.data.group_permissions];
            this.rootPermissions = response.data.root_permissions;
            
            this.role.permissions = this.allowedPermissions.map(node => {
              return {
                node: node.action + ":" + node.subject,
                enabled: ref<boolean>(false)
              }
            });
          })
          .catch(error => {
            if (!error.response) return;
            switch(error.response.status) {
              case 409:
                this.showToast("error", `Node ${this.createPermissionAction}:${this.createPermissionSubject} already exists.`);
                break;
              case 401:
                this.showToast("error", `You do not have permission to create nodes.`);
                break;
              default:
                this.showToast("error", `${error.toString()}`);
                break;
            }
          })
          .finally(() => {
            this.createPermissionCreating = false;
          })
      });
    },

    deletePermission() {
      axios.delete(`group/${this.group.id}/permission`, {
        data: {
          action: this.deletePermissionItem.action,
          subject: this.deletePermissionItem.subject
        }
      })
        .then(response => {            
          this.showToast("success", `Successfully deleted node ${this.deletePermissionItem.action}:${this.deletePermissionItem.subject}!`);

          this.deletePermissionDialogOpen = false;
          this.deletePermissionItem = {
            action: "",
            subject: ""
          }

          let filteredPermissions = [];
          
          filteredPermissions = response.data.root_permissions.filter((node: IPermissionNode) => node.action != "administrate" && node.action != "override");
          
          this.allowedPermissions = [...filteredPermissions, ...response.data.group_permissions];
          this.rootPermissions = response.data.root_permissions;
          
          this.role.permissions = this.allowedPermissions.map(node => {
            return {
              node: node.action + ":" + node.subject,
              enabled: ref<boolean>(false)
            }
          });
        })
        .catch(error => {
          if (!error.response) return;
          switch(error.response.status) {
            case 404:
              this.showToast("error", `Node ${this.createPermissionAction}:${this.createPermissionSubject} was not found.`);
              break;
            case 401:
              this.showToast("error", `You do not have permission to create nodes.`);
              break;
            default:
              this.showToast("error", `${error.toString()}`);
              break;
          }
        })
        .finally(() => {
          this.deletePermissionDeleting = false;
        })
    },

    openRemoveUserDialog(user: IUser) {
      this.removeUser = user;
      this.removeUserDialogOpen = true;
    },

    openCreatePermissionDialog() {
      this.createPermissionDialogOpen = true;
    },

    openDeletePermissionDialog(permission: any) {
      this.deletePermissionItem = permission;
      this.deletePermissionDialogOpen = true;
    },

    openDeleteRoleDialog(role: IGroupRole) {
      this.deleteRole = role;
      this.deleteRoleDialogOpen = true;
    },

    removeUserFromGroup(user: IUser) {
      this.loadingButtons[user.id] = true;

      const users = this.groupUsers.map(user => user.id);
      users.splice(users.indexOf(user.id), 1);

      axios.patch(`group/${this.group.id}/users`, {
        users
      })
        .then(response => {
          this.showToast("success", `Successfully removed ${user.username} from the group.`)
          this.groupUsers = response.data.users;
          this.removeUserDialogOpen = false;
          
          this.loadingButtons[user.id] = false;
        })
        .catch(error => {
          this.showToast("error", error.toString())
        })
    },

    removeRole(role: IGroupRole) {
      this.loadingButtons[role.id] = true;

      axios.delete(`group/${this.groupId}/role`, {
        data: {
          role_id: role.id
        }
      })
        .then(response => {
          this.group.roles = response.data.roles;
          this.group.users = response.data.users;

          for (const user of this.group.users) {
            if (!user.group_roles[this.group.id]) {
              this.groupRoleRefs[user.id] = ref<string[]>([]);
              continue
            }

            this.groupRoleRefs[user.id] = ref<string[]>(user.group_roles[this.group.id].map(role => role.role_name));
          }

          this.deleteRoleDialogOpen = false;
          this.showToast("success", `Successfully deleted role ${role.role_name}.`);
        })
    }
  },
})
</script>

<template>
  <div v-if="!noGroupAccess">
    <!-- User search dialog -->
    <LazyAppBarSearch
      v-model:isDialogVisible="addUserDialogOpen"
      v-model:searchQuery="addUserSearchQuery"
      :search-results="addUserSearchResults"
      @item-selected="addUserItemSelected"
    >

    </LazyAppBarSearch>

    <!-- Whitelist Game Dialog -->
    <VDialog
      v-model="whitelistGameDialogOpen"
      max-width="600"
      @click:outside="whitelistGameDialogOpen = !whitelistGameDialogOpen"
    >
      <DialogCloseBtn @click="whitelistGameDialogOpen = !whitelistGameDialogOpen" />
      <VCard title="Whitelist New Place">
        <VForm
          ref="whitelistGameForm"
          class="pa-6"
          @submit.prevent="whitelistGame"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="whitelistGameDialogPlaceId"
                label="Place Id"
                :rules="[requiredValidator]"
              />
            </VCol>
          </VRow>
          <VRow>
            <VCol
              cols="12"
              class="d-flex gap-4"
            >
              <VBtn
                type="submit"
                :loading="whitelistGameSubmitting"
              >
                Submit
              </VBtn>

              <VBtn
                type="reset"
                color="secondary"
                variant="tonal"
              >
                Reset
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <!-- Create Role Dialog -->
    <VDialog
      v-model="roleCreateDialogOpen"
      max-width="600"
      @click:outside="roleCreateDialogOpen = !roleCreateDialogOpen"
    >
      <DialogCloseBtn @click="roleCreateDialogOpen = !roleCreateDialogOpen" />
      <VCard title="Create Role" prepend-icon="tabler-address-book">
        <VForm @submit.prevent="finalizeGroupRole" ref="createGroupRoleForm">
          <VRow class="pa-6">
            <VCol cols="12">
              <VTextField
                v-model="roleName"
                label="Role Name"
                prepend-inner-icon="tabler-ballpen-filled"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="rolePosition"
                label="Role Position (Level)"
                prepend-inner-icon="tabler-key"
                type="number"
                :rules="[minMaxValidator(rolePosition, 1, 255), requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VBtn type="submit">
                Finalize
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <!-- Create Permission Dialog -->
    <VDialog v-model="createPermissionDialogOpen" max-width="500">
      <DialogCloseBtn @click="createPermissionDialogOpen = false" />
      <VCard prepend-icon="tabler-playlist-add" title="Create Permission Node">
        <VForm
          ref="createPermissionNodeForm"
          class="pa-6"
          @submit.prevent="createPermission"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                  v-model="createPermissionAction"
                  label="Action"
                  :rules="[requiredValidator]"
                  prepend-inner-icon="tabler-key"
                />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VTextField
                  v-model="createPermissionSubject"
                  label="Subject"
                  :rules="[requiredValidator]"
                  prepend-inner-icon="tabler-key"
                />
            </VCol>
          </VRow>

          <VRow>
            <VCol
              cols="12"
              class="d-flex gap-4"
            >
              <VBtn 
                type="submit"
                :loading="createPermissionCreating"
              >
                Submit
              </VBtn>

              <VBtn
                type="reset"
                color="secondary"
                variant="tonal"
              >
                Reset
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <!-- Delete permission dialog -->
    <VDialog v-model="deletePermissionDialogOpen" max-width="500">
      <DialogCloseBtn @click="deletePermissionDialogOpen = false" />
      <VCard title="Delete Permission Node">
        <VCardText>Confirm deletion of permission node {{ deletePermissionItem.action }}:{{ deletePermissionItem.subject }}</VCardText>
        <VRow class="pa-6">
          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn
              @click="deletePermission"
              color="error"
              :loading="deletePermissionDeleting"
            >
              Delete
            </VBtn>

            <VBtn
              color="secondary"
              variant="tonal"
              @click="deletePermissionDialogOpen"
            >
              Cancel
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VDialog>

    <!-- Remove user dialog -->
    <VDialog v-model="removeUserDialogOpen" max-width="500">
      <DialogCloseBtn @click="removeUserDialogOpen = false" />
      <VCard :title="'Remove ' + removeUser.username + ' from group'">
        <VCardText>Confirm removal of user {{ removeUser.username }} (ID {{ removeUser.id }})</VCardText>
        <VRow class="pa-6">
          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn
              @click="removeUserFromGroup(removeUser)"
              color="error"
              :loading="loadingButtons[removeUser.id]"
            >
              Remove
            </VBtn>

            <VBtn
              color="secondary"
              variant="tonal"
              @click="removeUserDialogOpen = false"
            >
              Cancel
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VDialog>

    <!-- Remove role dialog -->
    <VDialog v-model="deleteRoleDialogOpen" max-width="500">
      <DialogCloseBtn @click="deleteRoleDialogOpen = false" />
      <VCard :title="'Remove ' + deleteRole.role_name + ' from group'">
        <VCardText>Confirm removal of role {{ deleteRole.role_name }} (ID {{ deleteRole.id }})</VCardText>
        <VRow class="pa-6">
          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn
              @click="removeRole(deleteRole)"
              color="error"
              :loading="loadingButtons[deleteRole.id]"
            >
              Remove
            </VBtn>

            <VBtn
              color="secondary"
              variant="tonal"
              @click="deleteRoleDialogOpen = false"
            >
              Cancel
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VDialog>

    <!-- Page Content -->
    <PermissionsDialog 
      @update:roles="createGroupRole" 
      @close="permissionsDialogOpen = false"
      no-submit
      :open="permissionsDialogOpen" 
      :role="role"
    />

    <!-- Group Select & Metadata -->
    <Metadata 
      :groups="groups"
      :group-name="groupName"
      :group-id="groupId"
      :group-roblox-id="groupRobloxId"
      @group-changed="onGroupChanged"
    />

    <!-- Users & Whitelisted gamees -->
    <UsersAndGames 
      :group="group"
      :group-users="groupUsers"
      :group-role-refs="groupRoleRefs"
      @open-user-dialog="addUserDialogOpen = true"
      @open-remove-user-dialog="openRemoveUserDialog"
      @open-game-whitelist-dialog="openGameWhitelistDialog"
    />

    <!-- Roles & Guilds -->
    <RolesAndGuilds 
      :roles="group.roles"
      @open-permissions-dialog="openPermissionsDialog"
      @open-role-dialog="roleCreateDialogOpen = true"
      @delete-role="openDeleteRoleDialog"
    />

    <PermissionsList 
      :permissions="allowedPermissions"
      :root-permissions="rootPermissions"
      lock-root-permissions
      @open:create-permission-dialog="openCreatePermissionDialog" 
      @open:delete-permission-dialog="openDeletePermissionDialog"
    />
  </div>

  <div class="misc-wrapper" v-else>
    <ErrorHeader
      error-title="401"
      error-description="Looks like you don't have access to manage any groups. Message a group owner to request access!"
    >

    </ErrorHeader>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/misc.scss";
</style>
