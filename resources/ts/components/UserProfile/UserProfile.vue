<script lang="ts">
import axios from "@axios";
import { requiredValidator } from "@validators";
import { VDataTable } from "vuetify/lib/labs/components.mjs";
import ProfileField from "./ProfileField.vue";

interface IFingerprint {
  hash: string,
  known_accounts: string[]
}

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: string;
}

let showToast: any = undefined;


function compress(string: string, encoding: CompressionFormat) {
  const byteArray = new TextEncoder().encode(string);
  const cs = new CompressionStream(encoding);
  const writer = cs.writable.getWriter();
  writer.write(byteArray);
  writer.close();
  return new Response(cs.readable).arrayBuffer();
}

function decompress(byteArray: any, encoding: CompressionFormat) {
  const cs = new DecompressionStream(encoding);
  const writer = cs.writable.getWriter();
  writer.write(byteArray);
  writer.close();
  return new Response(cs.readable).arrayBuffer().then(function (arrayBuffer) {
    return new TextDecoder().decode(arrayBuffer);
  });
}

export default defineComponent({
   name: "UserProfile",
   props: ["user", "playerAvatarUrl", "show:back"],
   emits: ["on:back", "on:openGameData", "on:refresh", "on:loading"],

   components: {
    VDataTable,
    ProfileField,
   },

   data() {
    showToast = this.$showToast;
    const roles = ref<any[]>([]);
    const knownAccounts = ref<IFingerprint[]>([])
    const punishmentTypes = ref<any>([]);

    axios.get("punishments/types")
      .then(response => {
        punishmentTypes.value = response.data;
      })

    axios.get("roles")
      .then(response => {
        roles.value = response.data.map((role: IRole) => role.name);
      })
      .catch(error => {

      });

    axios.get(`user/${this.user.roblox_id}/accounts`)
      .then(response => {
        const minifiedAccounts: any[] = []
        
        response.data.forEach((element: any) => {
          if (element.known_accounts.length == 1) return;
          minifiedAccounts.push(element);
        });

        knownAccounts.value = minifiedAccounts;
      })
      .catch(error => {

      })

    return {
      roles,
      knownAccounts,
      loadingButtons: ref< { [id: string]: any } >([]),

      // punishment refs
      punishmentTypes,
      punishmentSelection: ref<string>('Global Ban'),
      punishmentReason: ref<string>(''),
      punishmentEvidence: ref<Array<any>>([]),
      punishmentSubmitting: ref<boolean>(false),
      punishmentSubmittingStep: ref<string>('Submitting punishment...'),
      punishmentExpireDate: ref<string>(''),

      knownAccountsShowing: ref<boolean>(false),

      gameDataHeaders: [
        {
          title: "Game Name",
          key: "game_name"
        },

        {
          title: "First Join",
          key: "created_at"
        },

        {
          title: "Last Updated",
          key: "updated_at"
        },

        {
          title: "Game Data",
          key: "data"
        }
      ],

      moderationDataHeaders: [
        {
          title: 'Ref ID',
          key: 'id'
        },

        {
          title: 'Moderator',
          key: 'moderator'
        },

        {
          title: 'Action',
          key: 'action'
        },

        {
          title: 'Reason',
          key: 'reason'
        },

        {
          title: 'Active',
          key: 'active'
        },

        {
          title: 'Expires',
          key: 'expires'
        },

        {
          title: 'Delete / Reinstate',
          key: 'temp_delete'
        },

        {
          title: 'Permanently Delete',
          key: 'perm_delete'
        }
      ]
    }
   },

   methods: {
    requiredValidator,
    
    onUserFieldsChanged(fields: any) {
      let fieldsChanged = 0;
      
      for (const field of fields) {
        if (!field.isDropdown && (field.type != 'editable' || this.user[field.key] == field.value)) continue;
        
        this.user[field.key] = field.value;
        fieldsChanged++;
      }
      
      if (fieldsChanged == 0) return;

      this.$emit("on:loading", true);
      
      axios.patch(`user/${this.user.id}`, { data: this.user })
        .then(response => {
          this.$emit("on:refresh", this.user.username);
        })
        .catch(error => {
          showToast("error", error);
        })
        .finally(() => {
          this.$emit("on:loading", false);
        })
    },

    togglePunishment(id: string, active: boolean) {
      this.loadingButtons[id] = 'toggle';
      
      axios.request({
        url: `user/${this.user.username}/punishment/${id}/${active && 'reinstate' || 'disable'}`,
        method: active && 'patch' || 'delete',
      })
        .then(response => {
          this.user.punishments = response.data.punishments;
        })
        .catch(error => {
          showToast("error", error);
        })
        .finally(() => {
          delete this.loadingButtons[id];
        })
    },

    deletePunishment(id: string) {
      this.loadingButtons[id] = 'delete';

      axios.delete(`user/${this.user.username}/punishment/${id}/full`)
        .then(response => {
          this.user.punishments = response.data.punishments;
        })
        .catch(error => {
          showToast("error", error);
        })
        .finally(() => {
          delete this.loadingButtons[id];
        })
    },

    submitPunishment() {
      const punishmentForm: any = this.$refs.submitPunishmentForm;
      
      punishmentForm.validate().then(async ({valid: isValid} : any) => {
        if (isValid) {
          this.punishmentSubmitting = true;

          const date = new Date(this.punishmentExpireDate);
          const expireTime = isNaN(date.getTime()) ? null : Math.floor((date.getTime() || 0) / 1000)

          axios.post(`user/${this.user.id}/punishment`, {
            type: this.punishmentTypes.find((type: any) => type.value == this.punishmentSelection).id,
            reason: this.punishmentReason,
            expires: this.punishmentExpireDate != "" ? date : undefined
          })
          .then(response => {
            this.user.punishments = response.data.punishments;

            if (this.punishmentEvidence.length > 0) {
              this.punishmentSubmittingStep = "Uploading evidence...";

              const userId = this.user.id;
              const reader = new FileReader();

              // reader.onload = async function () {
              //   const compressedData = new TextDecoder().decode((await compress(reader.result?.toString(), "gzip")));
                
              //   console.log(compressedData);

              //   axios.patch(`user/${userId}/punishment/${response.data.id}/evidence`, {
              //     data: [reader.result?.toString()]
              //   })
              //     .then(response => {
              //       console.log(response);
              //     })
              // }

              reader.readAsText(this.punishmentEvidence[0]);
            } else {
              this.punishmentSubmitting = false;
            }
          })
          .catch(error => {
            this.punishmentSubmitting = false;
            showToast("error", error);
          })
        };
      })
    },

    getPunishmentValue(action: number) {
      const type = this.punishmentTypes.find((type: any) => type.id == action);

      return type != undefined ? type.value : "Unknown"
    }
   }
});

</script>

<template>
  <VCard :prepend-avatar="playerAvatarUrl" :title="user.username">
    <VDialog v-model="punishmentSubmitting" width="300">
      <VCard color="primary" width="300">
        <VCardText class="pt-3">
          {{ punishmentSubmittingStep }}

          <VProgressLinear
            indeterminate
            class="mb-0"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="knownAccountsShowing" width="700">
      <DialogCloseBtn @click="knownAccountsShowing = false" />

      <VCard class="pb-6 px-3" :title="user.username + '\'s Known Accounts'">
        <VDataTable
          :headers="[{title: 'Fingerprint', key: 'hash'}, {title: 'Associated Accounts', key: 'accounts'}]"
          :items="knownAccounts"
        >
          <template #item.accounts="{ item }">
            {{ item.known_accounts.join(", ") }}
          </template>
        </VDataTable>
      </VCard>
    </VDialog>

    <template #append>
      <VBtn
        class="me-2"
        color="success"
        variant="tonal"
        icon="tabler-chevron-left"
        @click="$emit('on:back')" 
        size="small"
      />
      <VBtn
        color="secondary"
        variant="tonal" 
        icon="tabler-reload"
        size="small"
        @click="$emit('on:refresh')"
      />
    </template>

    
    <VCard class="pa-6 text-center" border="sm">
      <VCardTitle>
        Profile Data
      </VCardTitle>

      <VRow class="ma-4">
        <VCol
          cols="12"
          lg="4"
          md="4"
          sm="4"
        >
          <ProfileField 
            title="Roblox Profile"
            :user="user"
            @on:field-changed="onUserFieldsChanged"
            :fields="[
                { name: 'Roblox Username', key: 'username', value: user.username, type: 'editable' }, 
                { name: 'Roblox Id', key: 'roblox_id', value: user.roblox_id, type: 'editable' },
                { name: 'Roblox Profile', value: `https://www.roblox.com/users/${user.roblox_id}`, type: 'link'}  
              ]"
          >
            <VBtn @click="knownAccountsShowing = true" class="mb-6 mt-3">Known Accounts</VBtn>
          </ProfileField>
        </VCol>

        <VCol
          cols="12"
          lg="4"
          md="4"
          sm="4"
        >
          <ProfileField 
            title="Discord Profile"
            :user="user"
            @on:field-changed="onUserFieldsChanged"
            :fields="[
              { name: 'Discord Id', key: 'discord_id', value: user.discord_id, type: 'editable' },
              { name: 'Guardsman Id', value: user.id, type: 'static' }
            ]"
          />
        </VCol>

        <VCol
          cols="12"
          lg="4"
          md="4"
          sm="4"
        >
          <ProfileField 
            title="Guardsman Profile"
            :user="user"
            :roles="roles"
            @on:field-changed="onUserFieldsChanged"
            :fields="[
              { name: 'Roles', key: 'roles', value: user.roles, type: 'editable', isDropdown: true },
              { name: 'Position', value: user.position, type: 'static' },
            ]"
          />
        </VCol>
      </VRow>

      <VCardTitle>
        Game Data
      </VCardTitle>

      <VRow>
        <VCol cols="12">
          <VDataTable
            :headers="gameDataHeaders"
            :items="user.game_data"
          >
            <template #item.data="{ item }">
              <VBtn @click="$emit('on:openGameData', item)">View</VBtn>
            </template>
          </VDataTable>
        </VCol>
      </VRow>

      <VCardTitle>
        Add Punishment
      </VCardTitle>

      <VRow>
        <VCol cols="12">
          <VCard class="pa-6 text-left" variant="flat">
            <VForm @submit.prevent="submitPunishment" ref="submitPunishmentForm">
              <VRow>
                <VCol cols="12">
                  <VSelect
                    v-model="punishmentSelection"
                    :items="punishmentTypes.filter((type: any) => type.creatable == 1).map((type: any) => type.value)"
                    :rules="[requiredValidator]"
                    label="Punishment Type"
                  />
                </VCol>
              </VRow>

              <VRow>
                <VCol cols="12">
                  <VTextField
                    v-model="punishmentReason"
                    :rules="[requiredValidator]"
                    label="Punishment Reason"
                  />
                </VCol>
              </VRow>

              <VRow>
                <VCol cols="12">
                  <AppDateTimePicker
                    v-model="punishmentExpireDate"
                    label="Punishment Expiry Time (Optional)"
                    :config="{ enableTime: true, dateFormat: 'Y-m-d H:i' }"
                  />
                </VCol>
              </VRow>

              <VRow>
                <VCol cols="12">
                  <VFileInput
                    v-model="punishmentEvidence"
                    label="Punishment Evidence"
                    show-size
                    counter
                    multiple
                  />
                </VCol>
              </VRow>

              <VRow>
                <VCol
                  cols="12"
                  class="d-flex gap-4"
                >
                  <VBtn 
                    type="submit"
                    :loading="punishmentSubmitting"
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
        </VCol>
      </VRow>

      <VCardTitle>
        Moderation Record
      </VCardTitle>
      
      <VRow>
        <VCol cols="12">
          <VDataTable
            :headers="moderationDataHeaders"
            :items="user.punishments"
          >
            <template #item.action="{ item }">
              <VChip
                color="error"
                density="comfortable"
                class="font-weight-medium"
                size="small"
              >
                {{ getPunishmentValue(item.action) }}
              </VChip> 
            </template>

            <template #item.active="{ item }">
              <VChip
                :color="item.active == 1 && 'error' || 'success'"
                density="comfortable"
                class="font-weight-medium"
                size="small"
              >
                {{ item.active == 1 && 'Yes' || 'No' }}
              </VChip>
            </template>

            <template #item.expires="{ item }">
              {{ item.expires != null ? new Date(item.expires * 1000) : "Never" }}
            </template>

            <template #item.temp_delete="{ item }">
              <VBtn
                :color="item.active == 1 && 'error' || 'success'"
                @click="togglePunishment(item.id, item.active == 0)"
                :loading="loadingButtons[item.id] == 'toggle'"
                block
              >
                <VTooltip
                  activator="parent"
                >
                  This will toggle the punishment's active status, and leave the punishment on the user's record.
                </VTooltip>

                <VIcon v-if="item.active == 1" icon="tabler-trash" />
                <VIcon v-else icon="tabler-reload" />
              </VBtn>
            </template>

            <template #item.perm_delete="{ item }">
              <VBtn
                color="error"
                @click="deletePunishment(item.id)"
                :loading="loadingButtons[item.id] == 'delete'"
                block
              >
                <VTooltip
                  activator="parent"
                >
                  This will permanently remove the punishment from the user's record.
                </VTooltip>
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>
          </VDataTable>
        </VCol>
      </VRow>

    </VCard>
  </VCard>
</template>
