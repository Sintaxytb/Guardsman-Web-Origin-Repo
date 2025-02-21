<script lang="ts">
import { VDataTable } from "vuetify/lib/labs/components.mjs";
import JSONDataEditor from "./JSONDataEditor.vue";

export default defineComponent({
  name: "GameDataViewer",
  props: ["user", "data", "dialogOpen"],
  emits: ["on:close"],

  components: {
    VDataTable,
    JSONDataEditor,
  },

  data() {
    return {
      editingData: ref<boolean>(false),

      jsonEditorOpen: ref<string>(''),
      jsonEditorKey: ref<string>('none'),
      jsonEditorData: ref<any>([]),
    }
  },

  methods: {
    isJSON(key: string) {
      const jsonInputs: any[] = []
      for (const index in this.data.game_data) {
        if (Array.isArray(this.data.game_data[index])) {
          jsonInputs.push(index);
        }
      }

      console.log(jsonInputs);

      return jsonInputs.includes(key);
    }
  }
})

</script>

<template>
  <JSONDataEditor 
    :dialog-open="jsonEditorOpen"
    :data="jsonEditorData"
    @on:close="jsonEditorOpen = ''"
  />

  <VDialog persistent v-model="dialogOpen">
    <DialogCloseBtn @click="$emit('on:close')" />

    <VCard :title="`${user.username}'s ${data.game_name} Game Data`">
      <VRow>
        <VCol cols="12">
          <VTable>
            <thead>
              <tr>
                <th v-for="key of Object.keys(data.game_data)">
                  {{ key }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td v-for="(value, key) of data.game_data">
                  <!-- normal table display  -->
                  <span v-if="!editingData && !isJSON(key.toString())">{{ data.game_data[key] }}</span>
                  
                  <!-- editing inputs -->
                  
                  <!-- number input -->
                  <VTextField 
                    v-if="editingData && (typeof value == 'number')" 
                    v-model.number="data.game_data[key]" 
                    type="number" 
                  />

                  <!-- JSON input -->
                  <VBtn @click="jsonEditorOpen = key.toString();" v-else-if="isJSON(key.toString())">Edit Data</VBtn>

                  <!-- string input -->
                  <VTextField
                    v-else-if="editingData"
                    v-model="data.game_data[key]" 
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCol>
      </VRow>

      <VRow class="ma-6">
        <VCol cols="12" align="right">
          <VBtn
            color="secondary"
            variant="tonal"
            class="me-2"
            @click="$emit('on:close')"
          >
            Cancel
          </VBtn>
          
          <VBtn
            variant="tonal"
            class="me-2"
            @click="editingData = !editingData"
          >
            Modify
          </VBtn>

          <VBtn
            color="error"
            variant="tonal"
            class="me-2"
          >
            Reset
          </VBtn>
          <VBtn
            color="warning"
            variant="tonal"
          >
            Submit
          </VBtn>
          
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>
