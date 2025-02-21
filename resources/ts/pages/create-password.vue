<route lang="yaml">
  meta:
    layout: blank
    title: Create Password
    auth: none
</route>

<script lang="ts">
import { router } from "@/plugins/1.router";
import axios from "@axios";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { requiredValidator } from "@validators";

const statusMessages: any = {
  "ENOPASS": "No password was provided.",
  "EPASSEXISTS": "You have already set a password. To change your password, visit the Dashboard."
}

export default defineComponent({
  components: {
    VNodeRenderer
  },

  data() {
    return {
      showToast: this.$showToast,
      themeConfig,
      requiredValidator,

      password: ref<string>(''),
      passwordVisible: ref<boolean>(false),
      loading: ref<boolean>(false),
    }
  },

  methods: {
    submitPassword() {
      const createPasswordForm: any = this.$refs.createPasswordForm;

      createPasswordForm?.validate().then(({ valid: isValid } : any) => {
        if (isValid) {
          this.loading = true;
          
          axios.put('user/create-password', {
            password: this.password
          })
          .then(response => {
            const data = response.data;

            try {
              router.push({
                name: "dashboard",
                query: {
                  status: "LOGIN"
                }
              })
            } catch (error) {
              this.showToast("error", error)
            }
          })
          .catch(response => {
            const data = response.response.data;
    
            this.showToast("error", statusMessages[data.status]);
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
  <VRow
    class="auth-wrapper"
    style="block-size: 101.25vh; inline-size: 100vw;"
  >
    <VCol lg="8" class="d-none d-lg-flex" />

    <VCol 
      cols="12" 
      lg="4" 
      class="d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="800"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText style="text-align: center;">
          <VNodeRenderer 
            :nodes="themeConfig.app.logo"
            class="mb-6"
            style="inline-size: 10rem;"
          />

          <h5 class="text-h5 font-weight-semibold mb-1">
            Welcome to {{ themeConfig.app.title }}!
          </h5>
          <p class="mb-0">
            This is your first time logging in. Please create a password.
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="createPasswordForm"
            autocomplete="on"
            @submit.prevent="submitPassword"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="password"
                  label="Password"
                  :rules="[requiredValidator]"
                  :type="passwordVisible ? 'text' : 'password'"
                  :append-inner-icon="passwordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="passwordVisible = !passwordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="loading"
                  class="justify-center"
                >
                  <VIcon icon="tabler-key" class="mr-2" />
                  Create Password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>


<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
