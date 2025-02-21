<route lang="yaml">
meta:
  title: "Dashboard"
</route>

<script lang="ts">
import axios from "@axios";

interface IShout {
  shouter_id: number,
  shouter_name: string
  shout: string,
}

export default defineComponent({
  data() {
    const collapsed = ref<boolean>(false);
    const newShout = ref<boolean>(false);
    const shoutAvatarUrl = ref<string>('');
    const shout = ref<IShout>({
      shouter_id: 1, 
      shouter_name: "Roblox",
      shout: "Fetching...",
    });

    collapsed.value = localStorage.getItem("dashboardShoutHidden") == "true";
    
    axios.get("shout")
      .then(response => {
        shout.value = response.data;

        const storedShout = localStorage.getItem("last_shout");
        if (storedShout) {
          let shoutData: IShout | undefined = undefined;

          try {
            shoutData = JSON.parse(storedShout)
          } catch (error) {}

          if ((shoutData && shout.value.shout != shoutData.shout) || !shoutData) {
            newShout.value = true;
          }
        }
      })
      .catch(response => {
        console.log(response);

        shout.value = {
          shouter_id: 1,
          shouter_name: "Roblox",
          shout: `Shout failed to fetch. ${response}`
        }
      })
      .finally(() => {
        if (!shout.value.shouter_id) return;
        
        localStorage.setItem("last_shout", JSON.stringify(shout.value));

        this.$useAvatar(shout.value.shouter_id)
          .then((url: string) => {
            shoutAvatarUrl.value = url;
          })
      })

    return {
      shout,
      shoutAvatarUrl,
      newShout,
      collapsed
    }
  },

  methods: {
    setShoutCollapsed(collapsed: boolean) {
      this.collapsed = collapsed;
      localStorage.setItem('dashboardShoutHidden', collapsed.toString());
    }
  }
});

</script>

<template>
  <VRow v-if="shout.shout">
    <VCol cols="12">
      <AppCardActions
        action-collapsed
        :collapsed="collapsed"
        title="Dashboard Shout"
        icon="tabler-speakerphone"
        @collapsed="setShoutCollapsed(!collapsed)"
      >
        <template #before-actions v-if="newShout">
          <VChip color="info">
            New
          </VChip>
        </template>

        <VCardText>
          {{ shout.shout }}
        </VCardText>

        <VCardText
          class="d-flex justify-space-between align-center flex-wrap"
        >
          <div class="text-no-wrap">
            <VAvatar
              size="34"
              :image="shoutAvatarUrl"
              alt="User Avatar"
            />
            <span class="ms-2"> {{ shout.shouter_name }} </span>
          </div>
        </VCardText>
      </AppCardActions>
    </VCol>
  </VRow>
</template>
