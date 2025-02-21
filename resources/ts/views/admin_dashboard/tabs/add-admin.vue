<script lang="ts">
import axios from "@axios";
import { passwordValidator, requiredValidator } from "@validators";

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: string;
}

export default defineComponent({
  data() {
    const disabled = ref<boolean>(false);
    const roles = ref<Array<IRole>>([]);

    axios.get("roles")
      .then(response => {
        roles.value = response.data
      })
      .catch(response => {
        disabled.value = true;
      })

    return {
      requiredValidator,
      passwordValidator,

      loading: ref<boolean>(false),
      disabled,
      roles,

      username: ref<string>(''),
      userId: ref<string>(''),
      discordId: ref<string>(''),
      password: ref<string>(''),
      role: ref<string>('Player'),
    }
  },

  methods: {
    submit() {
      const addUserForm: any = this.$refs.addUserForm;

      addUserForm?.validate()
        .then(({ valid: isValid } : any) => {
          if (isValid) {
            this.loading = true;

            axios.post("create-user", {
              username: this.username,
              roblox_id: this.userId,
              discord_id: this.discordId,
              password: this.password,
              role: this.role
            })
            .then(response => {
              console.log(response);
            })
            .catch(response => {

            })
            .finally(() => {
              this.loading = false;
            })
          }
        })
    }
  }
});

</script>

<template>
  <VWindowItem>
    <VForm @submit.prevent="submit" ref="addUserForm">
      <VRow>
        <VCol cols="12">
          <VTextField
            v-model="username"
            label="Username"
            prepend-inner-icon="tabler-ballpen-filled"
            :rules="[requiredValidator]"
            :disabled="disabled"
          />
        </VCol>

        <VCol cols="12">
          <VTextField
            v-model="userId"
            label="User Id"
            prepend-inner-icon="tabler-ballpen-filled"
            :rules="[requiredValidator]"
            :disabled="disabled"
          />
        </VCol>

        <VCol cols="12">
          <VTextField
            v-model="discordId"
            label="Discord Id"
            prepend-inner-icon="tabler-ballpen-filled"
            :rules="[requiredValidator]"
            :disabled="disabled"
          />
        </VCol>
        
        <VCol cols="12">
          <VTextField
            v-model="password"
            label="Initial Password"
            type="password"
            prepend-inner-icon="tabler-circle-key"
            :rules="[requiredValidator, passwordValidator]"
            :disabled="disabled"
          />
        </VCol>

        <VCol cols="12">
          <VSelect
            v-model="role"
            label="Access Role"
            prepend-inner-icon="tabler-books"
            :items="roles.map(role => role.name)"
            :rules="[requiredValidator]"
            :disabled="disabled"
          />
        </VCol>

        <VCol cols="12">
          <VBtn type="submit" :disabled="disabled" :loading="loading">
            Submit
          </VBtn>

          <VBtn
            color="secondary"
            variant="tonal"
            type="reset"
            class="ml-6"
            :disabled="disabled"
          >
            Reset
          </VBtn>
        </VCol>
      </VRow>
    </VForm>
  </VWindowItem>
</template>
