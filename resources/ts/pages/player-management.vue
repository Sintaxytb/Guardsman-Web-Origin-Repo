<route lang="yaml">
meta:
  action: "moderate"
  subject: "search"
  title: Player Management
</route>

<script lang="ts">
import GameDataViewer from "@/components/UserProfile/GameDataViewer.vue";
import UserProfile from "@/components/UserProfile/UserProfile.vue";
import axios from "@axios";
import { requiredValidator } from "@validators";
import type { RouteLocationNormalizedLoaded } from "vue-router";

let useAvatar: any = undefined;
let showToast: any = undefined;

const errorMessages: { [status: string]: string } = {
  "404": "No user was found matching that query.",
  "500": "An unexpected error occurred."
}

export default defineComponent({
  components: {
    UserProfile,
    GameDataViewer
  },

  data() {
    useAvatar = this.$useAvatar;
    showToast = this.$showToast;

    return {
      overlayShowing: ref<boolean>(false),
      page: ref<number>(0),
      searchQuery: ref<string>(''),
      gameDataDialogOpen: ref<boolean>(false),
      selectedGame: ref<any>({
        user_id: ref<number>(0),
        game_name: ref<string>(''),
        game_data: ref<any>({}),
        created_at: ref<any>(""),
        updated_at: ref<any>("")
      }),

      playerAvatarUrl: ref<string>(""),

      user: {
        id: ref<number>(-1),
        username: ref<string>('Unknown'),
        roblox_id: ref<string>('-1'),
        discord_id: ref<string>('-1'),
        roles: ref<string[]>([]),
        permissions: ref<string[]>([]),
        game_data: ref<any[]>([]),
        rollback_points: ref<any[]>([]),
        created_at: Date,
        updated_at: Date
      }
    }
  },

  mounted() {
    const route = useRoute();
    if (route.query.search != null) {
      this.search(route.query.search.toString());
    }
  },

  beforeRouteUpdate(route: RouteLocationNormalizedLoaded) {
    if (route.query.search != null) {
      this.search(route.query.search.toString());
    }
  },

  methods: {
    requiredValidator,

    loading(loading: boolean) {
      this.overlayShowing = loading;
    },

    submitSearchInBox () {
      const searchForm: any = this.$refs.searchForm;

      searchForm?.validate().then(({ valid: isValid } : any) => {
        if (isValid) {
          const route = this.$route;

          if (route && route.query.search != this.searchQuery) {
            this.$router.push({
              path: "player-management",
              query: {
                search: this.searchQuery
              }
            })
          } else {
            this.search(this.searchQuery);
          }
        }
      });
    },

    search (query: string) {
      this.overlayShowing = true;

      if (!query) {
        query = this.user.username;
      }

      this.searchQuery = query;

      axios.get(`user/${query}`)
        .then(response => {
          const userData = response.data;
          
          // set ref values
          for (const index in userData) {
            this.user[index] = userData[index];
          }

          for (const game of userData.game_data) {
            game.game_data = JSON.parse(game.game_data);

            for (const index in game.game_data) {
              game.game_data[index] = ref<any>(game.game_data[index])
            }
          }

          useAvatar(this.user.roblox_id)
            .then((url: string) => {
              this.playerAvatarUrl = url;
            })

          this.page = 1;
        })
        .catch(error => {
          const response = error.response;
          if (response && response.status && errorMessages[response.status.toString()]) {
            showToast("error", errorMessages[response.status.toString()]);
          } else {
            showToast("error", error.toString());
          }
        })
        .finally(() => {
          this.overlayShowing = false;
        })
    },

    showGameData (item: any) {
      for (const index of Object.keys(item)) {
        this.selectedGame[index] = item[index];
      }
      this.gameDataDialogOpen = true;
    }
  }
})

</script>

<template>
  <div>
    <GameDataViewer 
      :user="user"
      :data="selectedGame"
      :dialog-open="gameDataDialogOpen"
      @on:close="gameDataDialogOpen = false"
    />

    <VRow>
      <VCol cols="12">
        <VCard title="Player Management" prepend-icon="tabler-user-cog">
          <VOverlay
            v-model="overlayShowing"
            contained
            persistent
            class="align-center justify-center"
          >
            <VProgressCircular indeterminate />
          </VOverlay>

          <VCardItem>
            <VWindow v-model="page" class="ms-3">
              <VWindowItem>
                <VForm ref="searchForm" @submit.prevent="submitSearchInBox">
                  <VCol cols="12">
                    <VTextField
                      v-model="searchQuery"
                      prepend-inner-icon="tabler-user"
                      label="Player Identifier (Username, Roblox Id, Discord Id)"
                      :rules="[requiredValidator]"
                    />
                  </VCol>

                  <VCol cols="12">
                    <VBtn
                      type="submit"
                      class="me-2"
                    >
                      Search
                    </VBtn>

                    <VBtn
                      color="secondary"
                      type="reset"
                      variant="tonal"
                    >
                      Reset
                    </VBtn>
                  </VCol>
                </VForm>
              </VWindowItem>

              <VWindowItem>
                <UserProfile 
                  :user="user"
                  :player-avatar-url="playerAvatarUrl"
                  @on:back="page = 0"
                  @on:open-game-data="showGameData"
                  @on:refresh="search"
                  @on:loading="loading"
                  show-back
                />
              </VWindowItem>
            </VWindow>
          </VCardItem>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
