<script lang="ts">
import { defineComponent } from 'vue';


export default defineComponent({
    props: [
        "groups",
        "groupName",
        "groupId",
        "groupRobloxId"
    ],
    emits: ["groupChanged"],

    data() {
      return {
        localGroupName: ref<string>(this.groupName)
      }
    }
});

</script>

<template>
  <VRow>
    <VCol cols="12" lg="3" md="3">
      <VCard title="Group">
        <VSelect
          v-model="localGroupName"
          label="Group"
          name="group"
          :items="groups.map((group: any) => group.group_name)"
          class="px-6 pb-6"
          @update:model-value="$emit('groupChanged', localGroupName)"
        />
      </VCard>
    </VCol>

    <VCol cols="12" lg="9" md="9">
      <VCard title="Group Metadata">
        <VCardText>Group Name & Reference Id: {{ groupName }}, #{{ groupId }}</VCardText>
        <VCardText>
        Group Roblox Id & Link: {{ groupRobloxId }}, <a
          :href="`https://roblox.com/groups/${groupRobloxId}`"
          target="_blank"
        >Group Page</a>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
