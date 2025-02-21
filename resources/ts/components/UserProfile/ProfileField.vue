<script lang="ts">


export interface IField {
  name: string
  key: string
  value: any
  type: "editable" | "link" | "static"
  isDropdown?: boolean
}


export default defineComponent({
  name: "ProfileField",
  emits: ["on:fieldChanged"],
  props: ["title", "fields", "user", "roles"],

  data() {
    const el = this.$parent?.$el;

    watch(() => el ? el.offsetWidth : 500, (offsetWidth) => {
      console.log(offsetWidth)
    });

    return {
      editing: ref<boolean>(false),
    }
  },

  computed: {
    showOverflowTitle() {
      const el = this.$parent?.$el;
      const offsetWidth = el ? el.offsetWidth : 500

      return offsetWidth < 339
    }
  },

  methods: {
    editData() {
      this.editing = !this.editing;

      if (!this.editing) {
        this.$emit("on:fieldChanged", this.fields);
      }
    }
  }
});

</script>

<template>
  <VCard :title="showOverflowTitle ? '' : title" elevation="24">
    <VCardTitle v-if="showOverflowTitle" class="pb-4">{{ title }}</VCardTitle>
    <template #prepend>
      <VBtn
        icon="tabler-reload"
        variant="tonal"
        size="small"
        color="error"
      >
        <VIcon icon="tabler-reload" />
        <VTooltip
          activator="parent"
          location="top"
        >
          This will reset the profile field to its default values.
        </VTooltip>
      </VBtn>
    </template>

    <template #append>
      <VBtn
        icon="tabler-tool"
        variant="tonal"
        size="small"
        @click="editData"
      />
    </template>

    <div v-for="(value, field) of fields" class="pa-2">
      <h4 v-if="value.type != 'link'">{{ value.name }}:</h4>

      <!-- display link -->
      <a target="_blank" v-if="value.type == 'link'" :href="value.value">{{ value.name }}</a>

      <!-- display multi-select dropdown -->
      <VSelect multiple :items="roles" v-model="user.roles" v-else-if="editing && value.isDropdown"></VSelect>

      <!-- display JSON list data if type is a dropdown -->
      <span v-else-if="!editing && value.isDropdown">{{ value.value.join(", ") }}</span>

      <!-- display text field for input if editing -->
      <VTextField prepend-inner-icon="tabler-ballpen-filled" v-else-if="editing && value.type != 'static'" v-model="value.value" />

      <!-- display static value -->
      <p v-else>{{ value.value }}</p>

    </div>

    <slot class="pa-6"></slot>
  </VCard>
</template>
