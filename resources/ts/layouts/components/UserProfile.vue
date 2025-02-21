<script lang="ts">
import { router } from "@/plugins/1.router";
import axios from "@axios";

export default {
  data() {
    let user: any = null;

    try {
      user = JSON.parse(localStorage.user)
    } catch (error) {
      user = {
        username: "Unknown",
        roblox_id: 1,
        role: "Unknown"
      }
    }

    const avatarUrl = ref<string>("");

    this.$useAvatar(user.roblox_id)
      .then((url: string) => {
        avatarUrl.value = url;
      })

    return {
      username: user.username,
      userId: user.roblox_id,
      role: user.role,
      avatarUrl: avatarUrl
    }
  },

  methods: {
    logout: async () => {
      await axios.delete("user/logout")
        .then(r => {
          localStorage.removeItem('user')

          router.push({ name: "login", query: { status: "LOGOUT" }});
        })
    }
  }
}

</script>

<template>
  <VAvatar
    class="cursor-pointer"
    color="primary"
    variant="tonal"
  >
    <VImg :src="avatarUrl"  alt="User Avatar" />

    <!-- SECTION Menu -->
    <VMenu
      activator="parent"
      width="230"
      location="bottom end"
      offset="14px"
    >
      <VList>
        <!--  User Avatar & Name -->
        <VListItem>
          <template #prepend>
            <VListItemAction start>
              <VAvatar
                color="primary"
                variant="tonal"
              >
                <VImg :src="avatarUrl" />
              </VAvatar>
            </VListItemAction>
          </template>

          <VListItemTitle class="font-weight-semibold">
            {{ username }}
          </VListItemTitle>
          <VListItemSubtitle>{{ role.name }}</VListItemSubtitle>
        </VListItem>

        <VDivider class="my-2" />

        <!-- Profile -->
        <VListItem to="dashboard">
          <template #prepend>
            <VIcon
              class="me-2"
              icon="tabler-user"
              size="22"
            />
          </template>

          <VListItemTitle>Dashboard</VListItemTitle>
        </VListItem>

        <!-- Divider -->
        <VDivider class="my-2" />

        <!--  Logout -->
        <VListItem @click="logout" v-if="username != 'Unknown'">
          <template #prepend>
            <VIcon
              class="me-2"
              icon="tabler-logout"
              size="22"
            />
          </template>

          <VListItemTitle>Logout</VListItemTitle>
        </VListItem>
      </VList>
    </VMenu>
    <!-- !SECTION -->
  </VAvatar>
</template>
imskyyc
