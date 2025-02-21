<script lang="ts">
import { requiredValidator } from "@/@core/utils/validators";
import defineAbilities from "@/plugins/casl/ability";
import axios from "@axios";
import { VDataTable } from "vuetify/lib/labs/components.mjs";

interface APIKey
{
  id: number
  user_id: string
  name: string
  key: string
  scopes: string
}

export default defineComponent({
  components: {
    VDataTable,
  },

  data() {
    const data = ref<APIKey[]>([]);
    const abilities = defineAbilities();
    
    const scopes: string[] = [];

    function addScopeIfCan(scope: string, action: string, subject: string) {
      if (abilities.can(action, subject)) {
        scopes.push(scope);
      }
    }

    addScopeIfCan("users.read", "moderate", "search");
    addScopeIfCan("users.write", "moderate", "moderate");
    addScopeIfCan("servers.read", "manage", "servers");
    addScopeIfCan("servers.execute", "development", "remote-execute")

    axios.get('user/api-keys')
      .then(response => {
        data.value = response.data;
      });

    const showToast: any = this.$showToast;
    const scopeRefs: { [scope: string]: Ref<boolean> } = {};

    for (const scope of scopes) {
      scopeRefs[`${scope}`] = ref<boolean>(false)
    }

    return {
      showToast,
      data,
      headers: [
        {
          title: "REF ID",
          key: "id",
        },
        {
          title: "Key Name",
          key: "name"
        },
        {
          title: "Key",
          key: "key"
        },
        {
          title: "Scopes",
          key: "scopes"
        },
        {
          title: "Revoke",
          key: "revoke"
        }
      ],

      scopes,

      deleteDialogOpen: ref<boolean>(false),
      deleteItem: ref<APIKey>({
        id: -1,
        user_id: "",
        name: "",
        key: "",
        scopes: ""
      }),
      keyDeleting: ref<number>(0),

      apiKeySubmitting: ref<boolean>(false),
      createDialogOpen: ref<boolean>(false),
      createAPIKeyName: ref<string>(''),
      createAPIScopes: ref<{ [scope: string]: Ref<boolean> }>(scopeRefs),
      keysVisible: ref<string[]>([]),
    }
  },

  methods: {
    requiredValidator,

    confirmDeleteKey(item: APIKey) {
      this.deleteItem = item;
      this.deleteDialogOpen = true;
    },

    async deleteKey(id: number) {
      this.keyDeleting = id;

      axios.delete(`user/api-key/${id}`)
        .then(response => {
          this.showToast("success", `Successfully revoked key ${id}`);
          this.data = response.data.keys;
          this.deleteDialogOpen = false;
          this.deleteItem = {
            id: -1,
            user_id: "",
            name: "",
            key: "",
            scopes: ""
          }
        })
        .catch(error => {
          this.showToast("error", error.toString());
        })
        .finally(() => {
          this.keyDeleting = 0;
        })
    },

    toggleKeyVisibility(key: string) {
      if (this.keysVisible.includes(key)) {
        this.keysVisible.splice(this.keysVisible.indexOf(key), 1);
      } else {
        this.keysVisible.push(key);
      }
    },

    async createAPIKey() {
      const keyForm: any = this.$refs.createKeyForm;

      keyForm.validate().then(({ valid: isValid } : any) => {
        if (!isValid) return;

        const scopes = this.createAPIScopes;

        if (Object.values(scopes).length == 0) {
          return this.showToast("error", "You must select at least one API scope!");
        }

        this.apiKeySubmitting = true;
        
        axios.post(`user/api-key/${this.createAPIKeyName}`, {
          scopes
        })
          .then(response => {
            this.data = response.data.keys;
            this.showToast("success", "Successfully issued a new API key!");
          })
          .catch(error => {
            if (error.response.status == 409) {
              this.showToast("error", "A key with that name already exists!")
            } else {
              this.showToast("error", error.toString());
            }
          })
          .finally(() => {
            this.apiKeySubmitting = false;
          })
      });
    }
  }
});

</script>

<template>
  <div>
    <!-- Create key dialog -->
    <VDialog v-model="createDialogOpen" max-width="500">
      <DialogCloseBtn @click="createDialogOpen = false" />
      <VCard prepend-icon="tabler-key" title="Create API Key">
        <VForm
          ref="createKeyForm"
          class="pa-6"
          @submit.prevent="createAPIKey"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                  v-model="createAPIKeyName"
                  label="Key Name"
                  :rules="[requiredValidator]"
                  prepend-inner-icon="tabler-ballpen-filled"
                />
            </VCol>
          </VRow>

          <VDivider class="ma-6" />
          <VRow>
            <VCol cols="12">
              <VCardTitle>Scopes</VCardTitle>
          
              <VTable>
                <thead>
                  <tr>
                    <td scope="col"> SCOPE NAME </td>
                    <td scope="col"> ENABLED </td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="scope in scopes">
                    <td>{{ scope }}</td>
                    <td><VCheckbox v-model="createAPIScopes[scope]"></VCheckbox></td>
                  </tr>
                </tbody>
              </VTable>
            </VCol>
          </VRow>

          <VRow>
            <VCol
              cols="12"
              class="d-flex gap-4"
            >
              <VBtn 
                type="submit"
                :loading="apiKeySubmitting"
              >
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
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <!-- Delete key dialog -->
    <VDialog v-model="deleteDialogOpen" max-width="500">
      <DialogCloseBtn @click="createDialogOpen = false" />
      <VCard title="Delete API Key">
        <VCardText>Confirm deletion of API Key {{ deleteItem.name }} (Ref ID #{{ deleteItem.id }})</VCardText>
        <VRow class="pa-6">
          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn
              @click="deleteKey(deleteItem.id)"
              color="error"
              :loading="keyDeleting == deleteItem.id"
            >
              Delete
            </VBtn>

            <VBtn
              color="secondary"
              variant="tonal"
              @click="deleteDialogOpen = false"
            >
              Cancel
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
    </VDialog>

    <VRow>
      <VCol cols="12">
        <VCard prepend-icon="tabler-key" title="API Keys">
          <template #append>
            <VBtn @click="createAPIKeyName = ''; createAPIScopes = {}; createDialogOpen = true">
              <VIcon icon="tabler-plus" class="mr-2"></VIcon>
              Create Key
            </VBtn>
          </template>

          <VDataTable
            :headers="headers"
            :items="data"
            class="pb-6 px-6"
          >
            <template #item.key="{ item }">
              <VTextField 
                readonly 
                v-model="item.key" 
                @click:append-inner="toggleKeyVisibility(item.key)"
                :type="keysVisible.includes(item.key) ? 'text' : 'password'"
                :append-inner-icon="keysVisible.includes(item.key) ? 'tabler-eye-off' : 'tabler-eye'" 
              />
            </template>
            <template #item.scopes="{ item }">
              {{ JSON.parse(item.scopes).join(", ") }}
            </template>

            <template #item.revoke="{ item }">
              <VBtn @click="confirmDeleteKey(item)" color="error" variant="tonal" :loading="keyDeleting == deleteItem.id">
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>
          </VDataTable>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
  meta:
    title: API Keys
    action: development
    subject: development
</route>
