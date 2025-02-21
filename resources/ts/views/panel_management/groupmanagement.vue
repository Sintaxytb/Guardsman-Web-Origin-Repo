<script lang="ts">
import axios from "@axios";
import { requiredValidator } from "@validators";

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

export default defineComponent({
  props: [],
  emits: [],

  data() {
    const rawGroups = ref<any[]>([]);
    const groups = ref<any[]>([]);

    axios.get("groups")
      .then(response => {
        rawGroups.value = response.data;
        groups.value = response.data.map((group: IGroup) => group.group_name);
      })

    return {
      showToast: this.$showToast,

      rawGroups,
      groups,
      group: ref<any>(),
      groupName: ref<string>(''),
      groupId: ref<number>(0),

      creating: ref<boolean>(false),
      deleting: ref<boolean>(false),
      deleteConfirmed: ref<boolean>(false),
    };
  },

  methods: {
    requiredValidator,

    createGroup() {
      const createGroupForm: any = this.$refs.createGroupForm

      createGroupForm.validate().then(({ valid: isValid }) => {
        if (!isValid) return;
        this.creating = true;

        axios.post('group/create', {
          groupName: this.groupName,
          groupId: this.groupId
        })
          .then(response => {
            this.showToast("success", `Successfully created group ${this.groupName}`);

            createGroupForm.reset();
          })
          .catch(error => {
            if (!error.response) return;

            switch (error.response.status) {
              case 409:
                this.showToast("error", `A group with ID ${this.groupId} already exists.`)
                break;
              default:
                this.showToast("error", error.toString())
            }
          })
          .finally(() => {
            this.creating = false;
          })
      })
    },

    deleteGroup() {
      const deleteGroupForm: any = this.$refs.deleteGroupForm

      deleteGroupForm.validate().then(({ valid: isValid }) => {
        if (!isValid) return;

        this.deleting = true;
      })
    },

    confirmDeleteGroup() {
      const groupData = this.rawGroups.find(group => group.group_name == this.group);

      this.deleteConfirmed = true;

      axios.delete(`group/${groupData.id}`)
        .then(response => {
          this.showToast("success", `Successfully deleted group ${groupData.group_name}`);
          this.rawGroups = response.data.groups;
          this.groups = response.data.groups.map((group: IGroup) => group.group_name);
          this.deleting = false;
          
          const deleteGroupForm: any = this.$refs.deleteGroupForm;
          deleteGroupForm.reset();
        })
        .catch(error => {
          this.showToast("error", error.toString());
        })
        .finally(() => {
          this.deleteConfirmed = false;
        })
    }
  },
});
</script>

<template>
  <!-- Group Delete Confirmation -->
  <VDialog v-model="deleting" max-width="500">
    <DialogCloseBtn @click="deleting = false" />
    <VCard title="Confirm Deletion">
      <VCardText>Confirm deletion of group {{ group }}. This will delete <strong>ALL group data, group roles, and will remove all users from the group.</strong></VCardText>
      <VCol cols="12" class="d-flex gap-4">
        <VBtn @click="confirmDeleteGroup" color="error" :loading="deleteConfirmed" type="submit">
          Delete 
        </VBtn>

        <VBtn @click="deleting = false" color="secondary" variant="tonal">
          Cancel 
        </VBtn>
      </VCol>
    </VCard>
  </VDialog>

  <VRow>
    <VCol cols="12" lg="6" md="6">
      <VCard title="Create Group" prepend-icon="tabler-users-group">
        <VForm @submit.prevent="createGroup" ref="createGroupForm">
          <VRow class="pa-6">
            <VCol cols="12">
              <VTextField
                v-model="groupName"
                label="Group Name"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                type="number"
                v-model="groupId"
                label="Group Id"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VBtn :loading="creating" type="submit">
                Create 
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VCol>

    <VCol cols="12" lg="6" md="6">
      <VCard title="Delete Group" prepend-icon="tabler-users-minus">
        <VForm @submit.prevent="deleteGroup" ref="deleteGroupForm">
          <VRow class="pa-6">
            <VCol cols="12">
              <VSelect
                v-model="group"
                :items="groups"
                label="Group"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12">
              <VBtn color="error" :loading="deleting" type="submit">
                Delete 
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VCol>
  </VRow>
</template>
