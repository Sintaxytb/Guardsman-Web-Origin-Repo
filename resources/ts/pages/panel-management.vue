<route lang="yaml">
meta:
  title: "Panel Management"
  action: "administrate"
  subject: "manage-panel"
</route>

<script lang="ts">
import { requiredValidator } from "@/@core/utils/validators";
import PermissionsDialog from "@/components/permissions.vue";
import useAbility from "@/plugins/casl/ability";
import GroupManagement from "@/views/panel_management/groupmanagement.vue";
import PermissionsList from "@/views/panel_management/permissionsList.vue";
import RoleManagement from "@/views/panel_management/rolemanagement.vue";
import RoleList from "@/views/panel_management/roles.vue";
import DashboardShoutForm from "@/views/panel_management/shout.vue";
import axios from "@axios";

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: any;
}

interface IPermissionNode {
  action: string
  subject: string
}

export default defineComponent({
  components: {
    PermissionsDialog,
    DashboardShoutForm,
    RoleList,
    RoleManagement,
    PermissionsList,
    GroupManagement
  },

  data() {
    const roles = ref<IRole[]>([]);
    const permissions = ref<any[]>([]);
    const rootPermissions = ref<any[]>([]);

    axios.get("roles")
      .then(response => {
        roles.value = response.data
      })

    axios.get("permissions")
      .then(response => {
        rootPermissions.value = response.data;

        permissions.value = response.data.map((node: IPermissionNode) => {
          return {
            node: node.action + ":" + node.subject,
            enabled: ref<boolean>(false)
          }
        });
      })

    return {
      roles,
      ability: useAbility(),
      showToast: this.$showToast,

      permissionsDialogOpen: ref<boolean>(false),
      rootPermissions,
      role: {
        id: ref<number>(-1),
        name: ref<string>(''),
        position: ref<number>(-1),
        permissions
      },

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
    requiredValidator,

    openPermissionsDialog(role: IRole) {
      this.role.id = role.id;
      this.role.name = role.name;
      this.role.position = role.position;

      const permissions = JSON.parse(role.permissions);

      for (const node of this.role.permissions) {
        node.enabled = permissions[node.node]
      }

      this.permissionsDialogOpen = true;
    },

    closePermissionsDialog() {
      this.permissionsDialogOpen = false;
    },

    updateRoles() {
      axios.get("roles")
        .then(response => {
          this.roles = response.data
        });
    },

    deleteRole(role: IRole) {
      axios.delete(`roles/${role.name}`)
        .then(response => {
          this.updateRoles();
          this.showToast("success", `Role ${role.name} successfully deleted.`);
        })
        .catch(error => {
          const messages: { [status: string]: string } = {
            "ENOROLE": `No role was found matching ${role.name}.`,
            "ENOPERMS": `You do not have permission to delete roles.`
          }


          this.showToast("error", `${messages[error.response.data.status] || "An unknown error occurred."}`);
        })
    },
    
    openCreatePermissionDialog() {
      this.createPermissionDialogOpen = true;
    },

    openDeletePermissionDialog(permission: any) {
      this.deletePermissionItem = permission;
      this.deletePermissionDialogOpen = true;
    },

    createPermission() {
      const createPermissionNodeForm: any = this.$refs.createPermissionNodeForm;
      
      createPermissionNodeForm.validate().then(({ valid: isValid } : any) => {
        if (!isValid) return;

        this.createPermissionCreating = true;

        axios.post('permission', {
          action: this.createPermissionAction,
          subject: this.createPermissionSubject
        })
          .then(response => {
            this.rootPermissions = response.data.permissions;
            
            this.showToast("success", `Successfully created node ${this.createPermissionAction}:${this.createPermissionSubject}!`);
            
            this.createPermissionDialogOpen = false;
            this.createPermissionAction = "";
            this.createPermissionSubject = "";


            this.role.permissions = response.data.map((node: IPermissionNode) => {
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
      this.deletePermissionDeleting = true;

      axios.delete('permission', {
        data: {
          action: this.deletePermissionItem.action,
          subject: this.deletePermissionItem.subject
        }
      })
      .then(response => {
        this.rootPermissions = response.data.permissions;
        
        this.deletePermissionDialogOpen = false;
        this.deletePermissionItem = {
          action: "",
          subject: ""
        };

        this.showToast("success", `Successfully deleted node ${this.deletePermissionItem.action}:${this.deletePermissionItem.subject}!`);

        this.role.permissions = response.data.map((node: IPermissionNode) => {
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
            this.showToast("error", `Node ${this.deletePermissionItem.action}:${this.deletePermissionItem.subject} does not exists.`);
            break;
          case 401:
            this.showToast("error", `You do not have permission to delete nodes.`);
            break;
          default:
            this.showToast("error", `${error.toString()}`);
            break;
        }
      })
      .finally(() => {
        this.deletePermissionDeleting = false;
      })
    }
  }
})

</script>

<template>
  <div>
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

    <PermissionsDialog @update:roles="updateRoles" @close="closePermissionsDialog" :open="permissionsDialogOpen" :role="role" />
    <PermissionsList :root-permissions="rootPermissions" :permissions="rootPermissions" @open:create-permission-dialog="openCreatePermissionDialog" @open:delete-permission-dialog="openDeletePermissionDialog" />
    <RoleList :roles="roles" @open-permissions-dialog="openPermissionsDialog" @delete-role="deleteRole" />
    <RoleManagement :roles="roles" @update:roles="updateRoles" @open-permissions-dialog="openPermissionsDialog" />
    <GroupManagement v-if="ability.can('administrate', 'manage-group')" />
    <DashboardShoutForm />
  </div>
</template>
