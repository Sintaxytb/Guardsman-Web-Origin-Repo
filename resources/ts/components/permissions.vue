<script lang="ts">
import axios from "@axios";
import { VDataTable } from "vuetify/lib/labs/components.mjs";

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: string;
}

export default defineComponent({
  props: ["role", "open", "noSubmit"],
  emits: ["close", "update:roles"],
 components: { VDataTable },

  data() {
    return {
      showToast: this.$showToast,
      loading: ref<boolean>(false),
      permissionsPerPage: ref<number>(5),
      permissionsHeaders: [
        {
          title: 'Node',
          sortable: false,
          key: 'node'
        },

        {
          title: 'Enabled',
          sortable: true,
          key: 'enabled'
        }
      ]
    }
  },

  methods: {
    submit() {
      if (this.noSubmit != undefined) {
        return this.$emit('update:roles', this.role);
      }

      this.loading = true;

      const permissions: any = {};

      for (const permission of this.role.permissions) {
        permissions[permission.node] = permission.enabled
      }

      axios.put(`roles/${this.role.name}`, {
        position: this.role.position,
        permissions: JSON.stringify(permissions)
      })
      .then(response => {
        this.$emit('update:roles')
        this.$emit('close')
        this.showToast("success", `Role ${this.role.name} successfully ${this.role.id == 0 ? "created" : "updated"}!`)
      })
      .catch(response => {
        
      })
      .finally(() => {
        this.loading = false;
      })
    },
  }
});

</script>

<template>
  <VDialog persistent- v-model="open" @click:outside="$emit('close')">
    <DialogCloseBtn @click="$emit('close')" />

    <VCard :title="`${role.name} Permissions`">
      <VDataTable
        v-model:items-per-page="permissionsPerPage"
        :headers="permissionsHeaders"
        :items="role.permissions"
      >
        <template #item.enabled="{ item }">
          <VCheckbox v-model="item.enabled"></VCheckbox>
          <!-- <VCheckbox v-model="role.permissions[node]" /> -->
        </template>
      </VDataTable>
      <!-- <VTable>
        <thead>
          <tr>
            <th scope="col">NODE</th>
            <th scope="col">ENABLED</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(permission, node) of role.permissions">
            <td> {{ node }} </td>
            <td> <VCheckbox v-model="role.permissions[node]" /> </td>
          </tr>
        </tbody>
      </VTable> -->

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn 
          color="error"
          @click="$emit('close')"
        >
          Cancel
        </VBtn>

        <VBtn :loading="loading" @click="submit">
          Submit
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
