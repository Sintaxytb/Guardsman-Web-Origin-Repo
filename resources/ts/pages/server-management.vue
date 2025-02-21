<script lang="ts">
import axios from "@axios";
import { VDataTable } from "vuetify/lib/labs/components.mjs";

interface IServer {
  job_id: string
  game_name: string
  place_id: string
  is_vip: number
  players: string
  player_count: number
  last_ping: string
  created_at: string
  updated_at: string
}

interface AllowedService {
  service: string
  enabled: Ref<boolean>
}

export default defineComponent({
  components: {
    VDataTable
  },

  data() {
    const data = ref<any>([]);

    const getData = () => {
      axios.get("servers").then(response => {
        const servers = response.data;
        data.value = servers;
      })
    };

    getData();

    setInterval(getData, 30000)

    return {
      showToast: this.$showToast,
      
      serverHeaders: [
        { title: 'JOB ID', key: 'job_id' },
        { title: 'GAME NAME', key: 'game_name' },
        { title: 'PLACE ID', key: 'place_id' },
        { title: 'SERVER TYPE', key: 'is_vip' },
        { title: 'PLAYERS', key: 'players' },
        { title: 'LAST PING', key: 'last_ping' },
        { title: 'MESSAGE', key: 'message'},
        { title: 'REMOTE EXECUTE', key: "execute" },
        { title: 'CLOSE SERVER', key: "close" }
      ],

      playerHeaders: [
        { title: 'USERNAME', key: 'username' },
        { title: 'USER ID', key: 'roblox_id' },
        { title: 'ROLES', key: 'roles'},
        { title: 'PLAYER ACTIONS', key: 'actions'}
      ],

      data: data,
      admin: JSON.parse(localStorage.getItem("user") || "{'username': 'Unknown'}"),

      playerDialogVisible: ref<boolean>(false),
      playerDialogData: ref<any>([]),
      playerDialogJobId: ref<string>(''),
      playerActionsDialogVisible: ref<boolean>(false),
      playerActionsDialogPlayer: ref<string>(''),

      playerActionsReasonDialogVisible: ref<boolean>(false),
      playerActionsAction: ref<string>(''),
      playerActionsReason: ref<string>(''),

      sendMessageDialogVisible: ref<boolean>(false),
      sendMessageJobId: ref<string>(''),
      sendMessageMessage: ref<string>(''),

      remoteExecuteDialogVisible: ref<boolean>(false),
      remoteExecuteJobId: ref<string>(''),
      remoteExecuteCode: ref<string>(''),

      remoteExecuteServiceHeaders: [
        {
          title: "Service",
          sortable: true,
          key: "service",
        },

        {
          title: "Enabled",
          sortable: false,
          key: "enabled"
        }
      ],

      remoteExecuteAllowedServices: ref<AllowedService[]>([
        {
          service: "BadgeService",
          enabled: ref<boolean>(false),
        },

        {
          service: "Chat",
          enabled: ref<boolean>(false),
        },

        {
          service: "GamePassService",
          enabled: ref<boolean>(false),
        },

        {
          service: "GamepadService",
          enabled: ref<boolean>(false),
        },

        {
          service: "GroupService",
          enabled: ref<boolean>(false),
        },

        {
          service: "LanguageService",
          enabled: ref<boolean>(false),
        },

        {
          service: "Lighting",
          enabled: ref<boolean>(false),
        },

        {
          service: "LocalizationService",
          enabled: ref<boolean>(false),
        },

        {
          service: "MarketplaceService",
          enabled: ref<boolean>(false),
        },

        {
          service: "Players",
          enabled: ref<boolean>(false),
        },

        {
          service: "PointsService",
          enabled: ref<boolean>(false),
        },

        {
          service: "RunService",
          enabled: ref<boolean>(false),
        },

        {
          service: "ReplicatedFirst",
          enabled: ref<boolean>(false),
        },

        {
          service: "ReplicatedStorage",
          enabled: ref<boolean>(false),
        },

        {
          service: "ServerStorage",
          enabled: ref<boolean>(false),
        },

        {
          service: "SocialService",
          enabled: ref<boolean>(false),
        },

        {
          service: "SoundService",
          enabled: ref<boolean>(false),
        },

        {
          service: "StarterGui",
          enabled: ref<boolean>(false),
        },

        {
          service: "StarterPlayer",
          enabled: ref<boolean>(false),
        },

        {
          service: "Teams",
          enabled: ref<boolean>(false),
        },

        {
          service: "TeleportService",
          enabled: ref<boolean>(false),
        },

        {
          service: "TextChatService",
          enabled: ref<boolean>(false),
        },

        {
          service: "TextService",
          enabled: ref<boolean>(false),
        },

        {
          service: "TweenService",
          enabled: ref<boolean>(false),
        },

        {
          service: "UserInputService",
          enabled: ref<boolean>(false),
        },

        {
          service: "VoiceChatService",
          enabled: ref<boolean>(false),
        },

        {
          service: "Workspace",
          enabled: ref<boolean>(false),
        }
      ]),

      closeServerDialogVisible: ref<boolean>(false),
      closeServerJobId: ref<string>(''),
      closeServerReason: ref<string>(''),
    }
  },

  methods: {
    showPlayers(jobId: string) {
      const server = this.data.find((serverData: IServer) => serverData.job_id == jobId);
      const players = JSON.parse(server.players);
      // console.log(server.players);

      this.playerDialogJobId = server.job_id;
      this.playerDialogData = players;
      this.playerDialogVisible = true;
    },

    sendAction(jobId: string, action: string, userId: string, ...args: string[]) {
      console.log(action);
      if (action != "ResetCharacter") {
        if (!args[0]) {
          this.playerActionsAction = action;
          this.playerActionsReasonDialogVisible = true;
        } else {
          const reasonFormRef: any = this.$refs.reasonFormRef

          reasonFormRef?.validate().then(({ valid: isValid } : any) => {
            if (isValid) {
              console.log("posting")
              axios.post(`game/${jobId}/message/guardsmanapidata`, {
                message: JSON.stringify([
                  action,
                  jobId,
                  userId,
                  ...args
                ])
              })
              .then(response => {
                this.showToast("success", "Action successfully sent to the server.");
                this.playerActionsReasonDialogVisible = false;
              })
              .catch(error => {
                this.showToast("error", error);
              })
            }
          })
        }
      } else {
        axios.post(`game/${jobId}/message/guardsmanapidata`, {
          message: JSON.stringify([
            action,
            jobId,
            userId
          ])
        }).then(response => {
          this.showToast("success", "Action successfully sent to the server.");
          this.playerActionsReasonDialogVisible = false;
        })
        .catch(error => {
          this.showToast("error", error);
        })
      }
    },

    sendMessage() {
      axios.post(`game/${this.sendMessageJobId}/message/guardsmanapidata`, {
        message: JSON.stringify([
          "GlobalMessage",
          this.sendMessageJobId,
          this.sendMessageMessage
        ])
      })
      .then(response => {
        this.showToast("success", "Message successfully sent to the server.")
        this.sendMessageDialogVisible = false;
      })
      .catch(error => {
        this.showToast("error", error);
      })
    },

    remoteExecute() {
      axios.post(`game/${this.remoteExecuteJobId}/remote-execute`, {
        code: this.remoteExecuteCode,
        allowedServices: this.remoteExecuteAllowedServices.filter(service => service.enabled == true).map(service => service.service)
      })
      .then(response => {
        this.showToast("success", "Code successfully sent to the server.")
        this.remoteExecuteDialogVisible = false;
      })
      .catch(error => {
        this.showToast("error", error);
      })
    },

    closeServer() {
      axios.post(`game/${this.closeServerJobId}/close-server`, {
        reason: this.closeServerReason
      })
      .then(response => {
        this.showToast("success", "Successfully shut down server.");
        this.closeServerDialogVisible = false;
      })
      .catch(error => {
        this.showToast("error", error);
      })
    },

    refreshServers(hideOverlay: any) {
      axios.get("servers").then(response => {
        const servers = response.data;
        this.data = servers;
      })
      .catch(error => {
        this.showToast("error", `Failed to refresh server list. ${error}`);
      })
      .finally(hideOverlay);
    }
  },
})

</script>


<template>
  <div>
    <VDialog
      v-model="playerDialogVisible"
      scrollable
    >
      <DialogCloseBtn @click="playerDialogVisible = !playerDialogVisible" />

      <VCard>
        <VCardItem class="pb-5">
          <VCardTitle>Players</VCardTitle>
        </VCardItem>

        <VDivider />

        <VCardText>
          <VDataTable
            :headers="playerHeaders"
            :items="playerDialogData"
            :items-per-page="10"
          >
            <template #item.roles="{ item }">
              <VChip
                :color="item.position > 0 ? 'success' : 'info'"
                density="comfortable"
                class="font-weight-medium"
                size="small"
              >
                {{ item.position > 0 ? item.role.join(", ") : 'No' }}
              </VChip>
            </template>

            <template #item.actions="{ item }">
              <VBtn @click="playerActionsDialogPlayer = item.roblox_id; playerActionsDialogVisible = true">
                Open
              </VBtn>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="playerActionsDialogVisible" max-width="300">
      <DialogCloseBtn @click="playerActionsDialogVisible = false" />

      <VCard title="Player Actions">
        <VCardText>
          <VBtn block color="warning" class="mb-3" @click="sendAction(playerDialogJobId, 'ResetCharacter', playerActionsDialogPlayer)">
            Reset Character
          </VBtn>

          <VBtn block color="error" class="mb-3" @click="sendAction(playerDialogJobId, 'ServerKick', playerActionsDialogPlayer)">
            Kick from Server
          </VBtn>

          <VBtn block color="error" class="mb-3" @click="sendAction(playerDialogJobId, 'ServerBan', playerActionsDialogPlayer)">
            Ban from Server
          </VBtn>

          <VBtn block color="error" class="mb-3" @click="sendAction('all', 'HardBan', playerActionsDialogPlayer)">
            Cross-Server Ban
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog persistent v-model="playerActionsReasonDialogVisible" max-width="500">
      <VCard title="Reason">
        <VForm ref="reasonFormRef" @submit.prevent="sendAction(playerDialogJobId, playerActionsAction, playerActionsDialogPlayer, playerActionsReason, admin.username)" class="pa-6">
          <VRow>
            <VCol cols="12">
              <VTextField 
                v-model="playerActionsReason"
                label="Reason"
              />
            </VCol>

            <VCol cols="12" class="d-flex gap-4">
              <VBtn 
                type="submit"
                color="error"
              >
                Submit
              </VBtn>

              <VBtn
                color="secondary"
                type="reset"
                variant="tonal"
                @click="playerActionsReasonDialogVisible = false"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <VDialog v-model="sendMessageDialogVisible" max-width="500">
      <VCard title="Send Message">
        <VForm ref="sendMessageFormRef" @submit.prevent="sendMessage" class="pa-6">
          <VRow>
            <VCol cols="12">
              <VTextarea 
                v-model="sendMessageMessage"
                label="Message"
              />
            </VCol>

            <VCol cols="12" class="d-flex gap-4">
              <VBtn type="submit">
                Submit
              </VBtn>

              <VBtn
                color="secondary"
                type="reset"
                variant="tonal"
                @click="sendMessageDialogVisible = false"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <VDialog v-model="remoteExecuteDialogVisible" max-width="500">
      <VCard title="Remote Execution">
        <VForm ref="remoteExecutionFormRef" @submit.prevent="remoteExecute" class="pa-6">
          <VRow>
            <VCol cols="12">
              <VTextarea 
                v-model="remoteExecuteCode"
                label="Code"
              />
            </VCol>

            <VCardTitle>Allowed Services</VCardTitle>

            <VCol cols="12">
              <VDataTable
                :headers="remoteExecuteServiceHeaders"
                :items="remoteExecuteAllowedServices"
              >
                <template #item.enabled="{ item }">
                  <VCheckbox v-model="item.enabled"></VCheckbox>
                </template>
              </VDataTable>
            </VCol>

            <VCol cols="12" class="d-flex gap-4">
              <VBtn type="submit">
                Submit
              </VBtn>

              <VBtn
                color="secondary"
                type="reset"
                variant="tonal"
                @click="remoteExecuteDialogVisible = false"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <VDialog persistent v-model="closeServerDialogVisible" max-width="500">
      <VCard title="Shutdown Reason">
        <VForm ref="reasonFormRef" @submit.prevent="closeServer" class="pa-6">
          <VRow>
            <VCol cols="12">
              <VTextField 
                v-model="closeServerReason"
                label="Reason"
              />
            </VCol>

            <VCol cols="12" class="d-flex gap-4">
              <VBtn 
                type="submit"
                color="error"
              >
                Submit
              </VBtn>

              <VBtn
                color="secondary"
                type="reset"
                variant="tonal"
                @click="closeServerDialogVisible = false"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VDialog>

    <VRow>
      <VCol cols="12">
        <AppCardActions
          action-refresh
          title="Running Servers"
          @refresh="refreshServers"
          icon="tabler-server"
        >
          <VDataTable
            :headers="serverHeaders"
            :items="data"
            :items-per-page="10"
            class="pa-6"
          >
            <template #item.is_vip="{ item }">
              <VChip
                :color="item.job_id.includes('STUDIO-TEST-MODE') && 'info' || (item.is_vip == 0 && 'success' || 'error')"
                density="comfortable"
                class="font-weight-medium"
                size="small"
              >
                {{ item.job_id.includes('STUDIO-TEST-MODE') && "Studio Session" || (item.is_vip == 0 && "Public Server" || "Private / Reserved Server") }}
              </VChip>
            </template>

            <template #item.players="{ item }">
              <VBtn block @click="showPlayers(item.job_id)">
                Click to View
              </VBtn>
            </template>

            <template #item.last_ping="{ item }">
              {{ new Date(item.last_ping) }}
            </template>

            <template #item.message="{ item }">
              <VBtn 
                @click="sendMessageJobId = item.job_id; sendMessageDialogVisible = true" 
                block 
                color="success"
                variant="tonal"
                :disabled="!item.linked"
              >
                <VIcon icon="tabler-message-forward" />
              </VBtn>
            </template>

            <template #item.execute="{ item }">
              <VBtn 
                @click="remoteExecuteJobId = item.job_id; remoteExecuteDialogVisible = true" 
                block 
                color="warning"
                variant="tonal"
                :disabled="!item.linked"
              >
                <VIcon icon="tabler-terminal" />
              </VBtn>
            </template>

            <template #item.close="{ item }">
              <VBtn 
                @click="closeServerJobId = item.job_id; closeServerDialogVisible = true;" 
                block 
                color="error"
                variant="tonal"
                :disabled="!item.linked"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>
          </VDataTable>
        </AppCardActions>
      </VCol>
    </VRow>
    
  </div>
</template>

<route lang="yaml">
meta:
  action: "manage"
  subject: "servers"
  title: Server Management
</route>
