<route lang="yaml">
  meta:
    layout: blank
    title: Login
</route>

<script lang="ts">
import { router } from "@/plugins/1.router";
import axios, { baseUrl } from "@axios";
import discordLogo from "@images/discord.png";
import robloxLogo from "@images/roblox.png";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { requiredValidator } from "@validators";
import isElectron from "is-electron";
import { useTheme } from "vuetify";

const statusMessages: any = {
  "ENOLOGIN": `You must be logged in to access ${themeConfig.app.title}.`,
  "ENOUSER": "The user you logged in as does not exist.",
  "ENOCREDS": "An incorrect username / password was provided.",
  "EBADCODE": "The OAuth2 code provided was invalid. Please try again.",
  "ENOACCESS": `Your role does not permit you to log in to ${themeConfig.app.title}.`,
  "LOGOUT": "You have successfully logged out.",
  "PWCHANGE": "Your password has successfully been changed. Please log in again.",
}

export default defineComponent({
  components: {
    VNodeRenderer
  },

  mounted() {
    const queryStatus = useRoute().query.status?.toString();

    if (queryStatus) {
      this.showToast(queryStatus.startsWith("E") ? "error" : "success", statusMessages[queryStatus]);
    }
  },

  data() {
    return {
      showToast: this.$showToast,
      themeConfig,
      requiredValidator,
      baseUrl,
      discordLogo,
      robloxLogo,
      global: useTheme(),

      isElectron: isElectron(),
      electronAuthPort: import.meta.env.VITE_ELECTRON_AUTH_PORT,

      robloxOAuthClientId: import.meta.env.VITE_ROBLOX_OAUTH_CLIENT_ID,
      discordOAuthClientId: import.meta.env.VITE_DISCORD_OAUTH_CLIENT_ID,

      username: ref<string>(''),
      password: ref<string>(''),
      passwordVisible: ref<boolean>(false),
      loading: ref<boolean>(false),
    }
  },

  methods: {
    submitLogin() {
      const loginForm: any = this.$refs.loginForm;

      loginForm?.validate().then(({ valid: isValid } : any) => {
        if (isValid) {
          this.loading = true;
          
          axios.post('user/login/guardsman', {
            username: this.username,
            password: this.password
          })
          .then(response => {
            const data = response.data;
            localStorage.setItem('user', JSON.stringify(data.user));

            try {
              router.push({
                name: "dashboard",
                query: {
                  status: "LOGIN"
                }
              })
            } catch (error) {
              console.log(error);
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
  >
    <VCol lg="4" class="d-none d-lg-flex" />

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
            v-if="global.current.dark"
            :nodes="themeConfig.app.logo"
            class="mb-6"
            style="inline-size: 10rem;"
          />

          <VNodeRenderer 
            v-else
            :nodes="themeConfig.app.logo_black"
            class="mb-6"
            style="inline-size: 10rem;"
          />

          

          <h5 class="text-h5 font-weight-semibold mb-1">
            Welcome to {{ themeConfig.app.title }}!
          </h5>
          <p class="mb-0">
            Please enter your username and password.
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="loginForm"
            autocomplete="on"
            @submit.prevent="submitLogin"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="username"
                  label="Username"
                  :rules="[requiredValidator]"
                />
              </VCol>
              
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
                  Login
                </VBtn>
              </VCol>

              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">or</span>
                <VDivider />
              </VCol>

              <!-- auth providers -->
              <VCol
                cols="6"
                class="text-center"
              >
                <div class="d-flex justify-center flex-wrap">
                  <VBtn
                    variant="tonal"
                    class="rounded"
                    :target="isElectron ? '_blank' : '_self'"
                    :href="'https://authorize.roblox.com/?client_id=' + robloxOAuthClientId + '&response_type=Code&redirect_uri=' + (isElectron ? 'http://127.0.0.1:' + electronAuthPort + '/auth-roblox' : baseUrl + '%2Fapi%2Fuser%2Flogin%2Froblox') + '&scope=openid&state=6789&nonce=12345&step=accountConfirm'"
                  >
                    <VImg :src="robloxLogo" class="pr-2 auth-provider-logo" />
                    <span class="auth-provider-text">Login with ROBLOX</span>
                  </VBtn>
                </div>
              </VCol>

              <VCol
                cols="6"
                class="text-center"
              >
                <div class="d-flex justify-center flex-wrap">
                  <VBtn
                    variant="tonal"
                    class="rounded"
                    :target="isElectron ? '_blank' : '_self'"
                    :href="'https://discord.com/api/oauth2/authorize?client_id=' + discordOAuthClientId + '&redirect_uri=' + (isElectron ? 'http://127.0.0.1:' + electronAuthPort + '/auth-discord' : baseUrl + '%2Fapi%2Fuser%2Flogin%2Fdiscord') + '&response_type=code&scope=identify'"
                  >
                    <VImg :src="discordLogo" class="pr-2 auth-provider-logo" />
                    <span class="auth-provider-text">Login with Discord</span>
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText>
          By signing in, you agree to Guardsman's <router-link to="terms-of-service">Terms of Service</router-link> and <router-link to="privacy-policy">Privacy Policy</router-link>.
        </VCardText>
      </VCard>
    </VCol>

    <VCol lg="4" class="d-none d-lg-flex" />
  </VRow>
</template>


<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
