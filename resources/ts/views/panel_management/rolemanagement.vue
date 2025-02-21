<script lang="ts">
import { minMaxValidator, requiredValidator } from "@validators";

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: string;
}

export default defineComponent({
  props: ["roles"],
  emits: ["openPermissionsDialog", 'update:roles'],

  data() {
    return {
      roleName: ref<string>(''),
      rolePosition: ref<number>(5),
    };
  },

  methods: {
    minMaxValidator,
    requiredValidator,

    finalize() {
      const addRoleForm: any = this.$refs.addRoleForm;

      addRoleForm?.validate().then(({ valid: isValid } : any) => {
        if (isValid) {
          this.$emit("openPermissionsDialog", {
            id: 0,
            name: this.roleName,
            position: this.rolePosition,
            permissions: "[]"
          })
        }
      })
    },

    openPermissionsDialog(role: IRole) {
      this.$emit('openPermissionsDialog', role)
    }
  },
});
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard title="Create Role" prepend-icon="tabler-books">
        <VForm @submit.prevent="finalize" ref="addRoleForm">
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
                :rules="[minMaxValidator(rolePosition, 1, 10), requiredValidator]"
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
    </VCol>
  </VRow>
</template>
