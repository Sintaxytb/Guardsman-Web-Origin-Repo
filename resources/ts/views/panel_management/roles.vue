<script lang="ts">

interface IRole {
  id: number,
  name: string,
  position: number,
  permissions: string
}

export default defineComponent({
  props: ["roles"],
  emits: ["openPermissionsDialog", "deleteRole"],
});

</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard title="Roles" prepend-icon="tabler-books">
        <VTable>
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">ROLE NAME</th>
              <th scope="col">POSITION</th>
              <th scope="col">PERMISSIONS</th>
              <th scope="col">DELETE</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="role in roles"
            >
              <td> {{ role.id }} </td>
              <td> {{ role.name }} </td>
              <td> {{ role.position }} </td>
              <td> <VBtn @click="$emit('openPermissionsDialog', role)" >List</VBtn> </td>
              <td> <VBtn color="error" variant="tonal" @click="$emit('deleteRole', role)"><VIcon icon="tabler-trash" /></VBtn> </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>
