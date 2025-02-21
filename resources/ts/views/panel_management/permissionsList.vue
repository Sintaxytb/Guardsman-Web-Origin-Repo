<script lang="ts">

interface IPermissionNode {
  action: string
  subject: string
}

export default defineComponent({
  props: ["permissions", "rootPermissions", "lockRootPermissions"],
  emits: ["open:createPermissionDialog", "open:deletePermissionDialog"]
});

</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard title="Permissions List" prepend-icon="tabler-list">
        <template #append>
            <VBtn @click="$emit('open:createPermissionDialog')">
              <VIcon icon="tabler-plus" class="mr-2"></VIcon>
              Create Permission
            </VBtn>
        </template>

        <VTable>
            <thead>
              <tr>
                <th scope="col">ACTION</th>
                <th scope="col">SUBJECT</th>
                <th scope="col">DELETE</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="permission in permissions"
                >
                <td> {{ permission.action }} </td>
                <td> {{ permission.subject }} </td>
                <td> 
                  <VBtn 
                    :disabled="lockRootPermissions != undefined && (rootPermissions.find((perm: IPermissionNode) => perm.action == permission.action && perm.subject == permission.subject) != undefined)" 
                    color="error" 
                    variant="tonal" 
                    @click="$emit('open:deletePermissionDialog', permission)"
                  >
                    <VIcon icon="tabler-trash" />
                  </VBtn> 
                </td>
              </tr>
            </tbody>
          </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>
