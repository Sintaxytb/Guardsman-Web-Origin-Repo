<script lang="ts">
import { VDataTable } from 'vuetify/lib/labs/components.mjs';

export default defineComponent({
  name: "JSONDataEditor",
  props: ["data", "dialogOpen"],
  emits: ["on:close"],

  components: {
    VDataTable
  },

  data() {
    return {
      headers: [
        {
          title: 'index',
          key: 'index'
        },

        {
          title: 'value',
          key: 'value'
        }
      ]
    }
  },

  computed: {
    isDialogOpen() {
      return this.dialogOpen != '';
    }
  }
});

</script>

<template>
  <VDialog persistent v-model="isDialogOpen">
    <DialogCloseBtn @click="$emit('on:close')" />
    <VCard :title="dialogOpen" class="pa-3">
      <VRow>
        <VCol cols="12">
      
          <VDataTable
            :headers="headers"
            :items="data"
          >

          </VDataTable>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>
