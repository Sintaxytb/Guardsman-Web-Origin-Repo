<script lang="ts">
import axios from "@axios";
import { confirmedValidator, passwordValidator, requiredValidator } from "@validators";

export default defineComponent({
  data() {
    return {
      requiredValidator,
      passwordValidator,
      confirmedValidator,

      currentPassword: ref<string>(''),
      repeatNewPassword: ref<string>(''),
      newPassword: ref<string>(''),
    } 
  },

  methods: {
    submit() {
      const changePasswordForm: any = this.$refs.changePasswordForm;

      changePasswordForm?.validate()
        .then(({valid: isValid} : any) => {
          if (isValid) {
            axios.patch("user/change-password", {
              current_password: this.currentPassword,
              new_password: this.newPassword
            })
            .then(() => {
              this.$router.push({ name: "login", query: { status: "PWCHANGE" } })
            })
            .catch(() => {
              this.$router.push({ name: "login", query: { status: "PWCHANGE" } })
            })
          }
        })
    }
  }
});

</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <template #prepend>
            <VIcon
              size="1.5rem"
              color="black"
              icon="tabler-lock"
            />
          </template>
          <VCardTitle class="text-black">
            Change Password
          </VCardTitle>
        </VCardItem>

        <VForm
          @submit.prevent="submit"
          ref="changePasswordForm"
        >
          <VCol cols="12">
            <VTextField
              v-model="currentPassword"
              prepend-inner-icon="tabler-lock"
              label="Current Password"
              type="password"
              :rules="[requiredValidator]"
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="newPassword"
              prepend-inner-icon="tabler-lock"
              label="New Password"
              type="password"
              :rules="[requiredValidator, passwordValidator]"
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="repeatNewPassword"
              prepend-inner-icon="tabler-lock"
              label="Repeat New Password"
              type="password"
              :rules="[requiredValidator, confirmedValidator(repeatNewPassword, newPassword)]"
            />
          </VCol>

          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn type="submit">
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
        </VForm>
      </VCard>
    </VCol>
  </VRow>
</template>
