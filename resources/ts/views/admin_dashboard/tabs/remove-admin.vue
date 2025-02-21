<script lang="ts">
import axios from "@axios";
import { requiredValidator } from "@validators";

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
  data() {
    const admins = ref<Array<IUser>>([]);
    const disabled = ref<boolean>(false);

    axios.get("administrators")
      .then(response => {
        admins.value = response.data;
      })
      .catch(() => {
        disabled.value = true;
      })

    return {
      requiredValidator,

      disabled,
      admins,
      user: ref<string>()
    }
  },

  methods: {
    submit() {

    }
  }
});

</script>

<template>
  <VWindowItem>
    <VForm @submit.prevent="submit" ref="addUserForm">
      <VRow>
        <VCol cols="12">
          <VSelect
            v-model="user"
            label="Admin"
            prepend-inner-icon="tabler-user-minus"
            :items="admins.map(admin => admin.username)"
            :rules="[requiredValidator]"
            :disabled="disabled"
          />
        </VCol>

        <VCol cols="12">
          <VBtn color="error" type="submit" :disabled="disabled">
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
