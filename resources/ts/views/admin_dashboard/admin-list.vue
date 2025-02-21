<script lang="ts">
import axios from "@axios";
import { themeConfig } from "@themeConfig";

interface IGroupRole {
  id: number
  group_id: number
  role_name: string
  position: number
}

interface IUser {
  id: number
  username: string
  roblox_id: string
  discord_id: string
  roles: string[]
  group_roles: { [groupId: string]: IGroupRole[] }
  game_data: string
  avatarUrl?: string
  created_at: string
  updated_at: string
}

interface IListUser {
  title: string,
  value: string,
  props: {
    prependAvatar: any,
    rounded: string,
  }
}

interface IRole {
  id: number;
  name: string;
  position: number;
  permissions: string;
}

interface IPunishment {
  id: string,
  user: string,
  moderator: string,
  action: string,
  reason: string,
  active: number,
  expires?: number,
}

export default defineComponent({
  data() {
    const adminData = ref<Array<IUser>>([]);
    const adminList = ref<Array<IListUser>>([]);
    const adminNames = ref<Array<string>>([]);
    const roles = ref<Array<IRole>>([]);
    const ability: any = this.$ability;
    const punishmentTypes = ref<any>({});

    axios.get("punishments/types")
      .then(response => {
        punishmentTypes.value = response.data;
      })

    axios.get("roles")
      .then(response => {
        roles.value = response.data
      })

    axios.get("administrators")
      .then(response => {
        adminData.value = response.data;

        for (const index in response.data) {
          const user: any = response.data[index];
          user.roles = JSON.parse(user.roles);
          const avatarUrl = ref<string>("");

          this.$useAvatar(user.roblox_id).then((url: string) => {
            avatarUrl.value = url;
          });

          adminNames.value.push(user.username);

          adminList.value = [
            ...adminList.value,
            {
              title: user.username,
              value: index,
              props: {
                prependAvatar: avatarUrl,
                rounded: "xl",
              }
            }
          ]
        }
      })
      .catch(response => {

      })
      .finally(() => {

      })

    return {
      ability,
      themeConfig,
      roles,
      loadingButtons: ref<{ [id: string]: boolean }>({}),
      userRoles: ref<Array<string>>([]),

      editingRobloxAccount: ref<boolean>(false),
      editingDiscordAccount: ref<boolean>(false),
      editingAccess: ref<boolean>(false),

      currentTab: ref<number>(0),
      selectedAdmins: ref<Array<any>>([]),
      admin: ref<any>({}),
      adminData,
      adminList,
      adminNames,

      roleName: ref<string>(''),
      rolePosition: ref<number>(0),

      punishmentTypes,
      punishments: ref<any>([]),
      punishmentRowCount: ref<number>(10),
      punishmentPage: ref<number>(1),
      punishmentPages: ref<number>(0),
      punishmentFilter: ref<string>(""),

      audits: ref<any>([]),
      auditsRowCount: ref<number>(10),
      auditsPage: ref<number>(1),
      auditsPages: ref<number>(0),
      auditsFilter: ref<string>(""),
    }
  },

  methods: {
    editRobloxAccount() {
      this.editingRobloxAccount = !this.editingRobloxAccount;

      if (!this.editingRobloxAccount) {
        axios.patch(`user/${this.admin.id}`, {
          data: this.admin
        })
        .then(response => {
          console.log(response);
        })
        .catch(response => {

        })
        .finally(() => {

        })
      }
    },

    editDiscordAccount() {
      this.editingDiscordAccount = !this.editingDiscordAccount;

      if (!this.editingDiscordAccount) {
        axios.patch(`user/${this.admin.id}`, {
          data: this.admin
        })
        .then(response => {
          console.log(response);
        })
        .catch(response => {

        })
        .finally(() => {

        })
      }
    },

    editAccess() {
      this.editingAccess = !this.editingAccess;

      if (!this.editingAccess) {
        axios
          .patch(`user/${this.admin.id}`, {
            data: this.admin,
          })
          .then((response) => {
            console.log(response);
          })
          .catch((response) => {})
          .finally(() => {});
      }
    },

    onRoleValueUpdated(roles: string[]) {
      this.admin.roles = roles;

      let highestPosition = 0;

      for (const name of roles){
        const role = this.roles.find((role) => role.name == name);

        if (role && role.position > highestPosition) {
          highestPosition = role.position;
        }
      }

      this.rolePosition = highestPosition;
    },

    deletePunishment(punishment: IPunishment) {
      this.loadingButtons[punishment.id] = true;

      axios.delete(`user/${punishment.user}/punishment/${punishment.id}`)
        .then(this.adminSelected)
        .catch(response => {

        })
        .finally(() => {
          this.loadingButtons[punishment.id] = false;
        })
    },

    fullDelete(punishment: IPunishment) {
      this.loadingButtons[punishment.id + 'delete'] = true;

      axios.delete(`user/${punishment.user}/punishment/${punishment.id}/full`)
        .then(this.adminSelected)
        .catch(response => {

        })
        .finally(() => {
          this.loadingButtons[punishment.id + 'delete'] = false;
        })
    },

    reinstatePunishment(punishment: IPunishment) {
      this.loadingButtons[punishment.id] = true;

      axios.patch(`user/${punishment.user}/punishment/${punishment.id}/reinstate`)
        .then(this.adminSelected)
        .catch(response => {

        })
        .finally(() => {
          this.loadingButtons[punishment.id] = false;
        })
    },

    adminSelected(hideOverlay: (() => void) | any) {
      const selected = this.selectedAdmins[0];
      console.log(this.selectedAdmins);
      if (!selected) return;

      const admin = this.adminData[selected];
      admin.avatarUrl = this.adminList[selected].props.prependAvatar;

      axios.get(`punishments/by-moderator/${admin.id}`)
        .then(response => {
          const parsedPunishments: Array<any> = [];

          if (this.punishmentFilter != "") {
            response.data = response.data.filter((punishment: Array<any>) => {
              let filterPass = false;

              for (const value of Object.values(punishment)) {
                if (
                  this.punishmentFilter == "" ||
                  value.toString().includes(this.punishmentFilter)
                ) {
                  filterPass = true;
                }
              }

              return filterPass;
            });
          }

          for (
            let index =
              this.punishmentRowCount * this.punishmentPage -
              this.punishmentRowCount;
            index < this.punishmentRowCount * this.punishmentPage;
            index++
          ) {
            let punishment = response.data[index];
            if (!punishment) continue;

            parsedPunishments.push(punishment);
          }

          let pageCount = Math.ceil(
            response.data.length / this.punishmentRowCount
          );
          if (pageCount < 1) pageCount = 1;

          this.punishments = parsedPunishments;
          this.punishmentPages = pageCount;
          this.punishmentPage = 1;
        })

      axios.get(`audits/by-moderator/${admin.id}`)
        .then(response => {
          const parsedAudits: Array<any> = [];

          if (this.auditsFilter != "") {
            response.data = response.data.filter((audits: Array<any>) => {
              let filterPass = false;

              for (const value of Object.values(audits)) {
                if (
                  this.auditsFilter == "" ||
                  value.toString().includes(this.auditsFilter)
                ) {
                  filterPass = true;
                }
              }

              return filterPass;
            });
          }

          for (
            let index =
              this.auditsRowCount * this.auditsPage - this.auditsRowCount;
            index < this.auditsRowCount * this.auditsPage;
            index++
          ) {
            let audit = response.data[index];
            if (!audit) continue;

            parsedAudits.push(audit);
          }

          let pageCount = Math.ceil(response.data.length / this.auditsRowCount);
          if (pageCount < 1) pageCount = 1;

          this.audits = parsedAudits;
          this.auditsPages = pageCount;
        })

      this.admin = admin;
      this.userRoles = admin.roles;
      this.admin.roles = this.userRoles;

      let highestPosition = 0;

      for (const name of this.userRoles){
        const role = this.roles.find((role) => role.name == name);

        if (role && role.position > highestPosition) {
          highestPosition = role.position;
        }
      }

      this.rolePosition = highestPosition;
      this.currentTab = 1;

      if (typeof hideOverlay == "function") {
        hideOverlay();
      }
    }
  }
});

</script>
  
<template>
  <AppCardActions title="Administrators" icon="tabler-users" action-collapsed>
    <VWindow v-model="currentTab">
      <VWindowItem>
        <VCard variant="flat">
          <VList
            :items="adminList"
            v-model:selected="selectedAdmins"
            @click="adminSelected"
            class="px-2"
          />
        </VCard>
      </VWindowItem>

      <VWindowItem>
        <VRow class="px-6">
          <VCol cols="8">
            <VAvatar :image="admin.avatarUrl"/>
            <span class="ml-2"><strong>{{ admin.username }}</strong></span>
          </VCol>
          <VCol cols="4" class="text-right">
            <VBtn
              color="secondary"
              type="reset"
              variant="tonal"
              @click="
                currentTab = 0;
                selectedAdmins = [];
              "
            >
              Back
            </VBtn>
          </VCol>
        </VRow>

        <AppCardActions
          action-refresh
          @refresh="adminSelected"
          variant="flat"
          class="mx-6 mb-6"
        >
          <VRow>
            <VCol cols="12" lg="4" md="4">
              <VCard variant="tonal">
                <VCardItem>
                  <template #append>
                    <VBtn
                      icon="tabler-tool"
                      @click="editRobloxAccount"
                    />
                  </template>

                  <VCardTitle>
                    Roblox Account
                  </VCardTitle>
                </VCardItem>

                <VCardText v-if="editingRobloxAccount">
                  <strong>Username:</strong>
                  <VTextField
                    label="New username"
                    v-model="admin.username"
                    prepend-inner-icon="tabler-ballpen-filled"
                    class="mt-2"
                  />
                </VCardText>

                <VCardText v-else><strong>Username:</strong> {{ admin.username }} </VCardText>

                <VCardText v-if="editingRobloxAccount">
                  <strong>Roblox Id:</strong>
                  <VTextField
                    label="New Id"
                    v-model="admin.roblox_id"
                    prepend-inner-icon="tabler-ballpen-filled"
                    class="mt-2"
                  />
                </VCardText>

                <VCardText v-else><strong>Roblox Id:</strong> {{ admin.roblox_id }} </VCardText>
                <VCardText><a :href="`https://www.roblox.com/users/${admin.roblox_id}/profile`" target="_blank">Roblox Profile</a></VCardText> 
              </VCard>
            </VCol>

            <VCol cols="12" lg="4" md="4">
              <VCard variant="tonal">
                <VCardItem>
                  <template #append>
                    <VBtn
                      icon="tabler-tool"
                      @click="editDiscordAccount"
                    />
                  </template>

                  <VCardTitle>
                    Discord Account
                  </VCardTitle>
                </VCardItem>

                <VCardText v-if="editingDiscordAccount">
                  <strong>Discord Id:</strong>
                  <VTextField
                    label="New Discord Id"
                    v-model="admin.discord_id"
                    prepend-inner-icon="tabler-ballpen-filled"
                    class="mt-2"
                  />
                </VCardText>

                <VCardText v-else><strong>Discord Id:</strong> {{ admin.discord_id }} </VCardText>

                <VCardText><strong>Guardsman Id:</strong> {{ admin.id }} </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" lg="4" md="4">
              <VCard variant="tonal">
                <VCardItem>
                  <template #append v-if="ability.can('administrate', 'make-user')">
                    <VBtn icon="tabler-tool" @click="editAccess" />
                  </template>

                  <VCardTitle> Guardsman Access </VCardTitle>
                </VCardItem>

                <VCardText v-if="editingAccess">
                  <strong>Role:</strong>
                  <VSelect
                    multiple
                    :items="roles.map((role) => role.name)"
                    @update:model-value="onRoleValueUpdated"
                    label="Role(s)"
                    v-model="userRoles"
                    prepend-inner-icon="tabler-book"
                    class="mt-2"
                  />
                </VCardText>

                <VCardText v-else><strong>Role(s):</strong> {{ userRoles.join(", ") }} </VCardText>

                <VCardText
                  ><strong>Position:</strong> {{ rolePosition }}
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VCard title="Punishments Given" variant="flat">
                <VRow class="d-flex justify-center">
                  <VCol cols="12" lg="4" md="4">
                    <VCard variant="flat" class="pt-4">
                      <VSelect
                        v-model="punishmentRowCount"
                        label="Rows"
                        name="rowCount"
                        :items="[10, 25, 50, 100]"
                        @update:modelValue="
                          adminSelected(null);
                          punishmentPage = 1;
                        "
                      />
                    </VCard>
                  </VCol>

                  <VCol cols="12" lg="4" md="4">
                    <VCard variant="flat" class="pt-4">
                      <VTextField
                        label="Filter (Search)"
                        v-model="punishmentFilter"
                        @update:modelValue="
                          adminSelected(null);
                          punishmentPage = 1;
                        "
                      >
                      </VTextField>
                    </VCard>
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VCard variant="flat">
                      <VTable>
                        <thead>
                          <tr>
                            <th scope="col">ID</th>
                            <th scope="col">USER</th>
                            <th scope="col">MODERATOR</th>
                            <th scope="col">TYPE</th>
                            <th scope="col">REASON</th>
                            <th scope="col">EXPIRES</th>
                            <th scope="col">ACTIVE</th>
                            <th scope="col">DELETE / REINSTATE</th>
                            <th scope="col">PERMANENTLY DELETE</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="punishment in punishments">
                            <td>{{ punishment.id }}</td>
                            <td>{{ punishment.user }}</td>
                            <td>{{ punishment.moderator }}</td>
                            <td>
                              <VChip
                                :color="'error' "
                                density="comfortable"
                                class="font-weight-medium"
                                size="small"
                              >
                                {{ punishmentTypes.find((type: any) => type.id == punishment.action).value }}
                              </VChip> 
                            </td>
                            <td>{{ punishment.reason }}</td>
                            <td v-if="punishment.expires"> {{ new Date(punishment.expires * 1000) }}  </td>
                            <td v-else> Never </td>
                            <td>
                              <VChip
                                :color="punishment.active == 1 && 'error' || 'success'"
                                density="comfortable"
                                class="font-weight-medium"
                                size="small"
                              >
                                {{ punishment.active == 1 && 'Yes' || 'No' }}
                              </VChip> 
                            </td>
                            <td
                              v-if="punishment.active"
                            >
                              <VBtn
                                color="error"
                                @click="deletePunishment(punishment)"
                                :loading="loadingButtons[punishment.id]"
                                block
                              >
                                <VIcon icon="tabler-trash" />
                              </VBtn>
                            </td>

                            <td
                              v-else
                            >
                              <VBtn
                                color="success"
                                @click="reinstatePunishment(punishment)"
                                :loading="loadingButtons[punishment.id]"
                                block
                              >
                                <VIcon icon="tabler-restore" />
                              </VBtn>
                            </td>

                            <td>
                              <VBtn
                                color="error"
                                @click="fullDelete(punishment)"
                                :loading="loadingButtons[punishment.id + 'delete']"
                                block
                              >
                                <VIcon icon="tabler-trash" />
                              </VBtn>
                            </td>
                          </tr>
                        </tbody>
                      </VTable>

                      <VPagination
                        v-model="punishmentPage"
                        :length="punishmentPages"
                        :total-visible="10"
                        rounded="circle"
                        @update:modelValue="adminSelected"
                      />
                    </VCard>
                  </VCol>
                </VRow>
              </VCard>
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VCard title="Audits" variant="flat">
                <VRow class="d-flex justify-center">
                  <VCol cols="12" lg="4" md="4">
                    <VCard variant="flat" class="pt-4">
                      <VSelect
                        v-model="auditsRowCount"
                        label="Rows"
                        name="rowCount"
                        :items="[10, 25, 50, 100]"
                        @update:modelValue="
                          adminSelected(null);
                          auditsPage = 1;
                        "
                      />
                    </VCard>
                  </VCol>

                  <VCol cols="12" lg="4" md="4">
                    <VCard variant="flat" class="pt-4">
                      <VTextField
                        label="Filter (Search)"
                        v-model="auditsFilter"
                        @update:modelValue="
                          adminSelected(null);
                          auditsPage = 1;
                        "
                      >
                      </VTextField>
                    </VCard>
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VCard variant="flat">
                      <VTable>
                        <thead>
                          <tr>
                            <th scope="col">ID</th>
                            <th scope="col">USER</th>
                            <th scope="col">TYPE</th>
                            <th scope="col">ACTION</th>
                            <th scope="col">TIME</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="log in audits">
                            <td>{{ log.id }}</td>
                            <td>{{ log.user }}</td>
                            <td>{{ log.type }}</td>
                            <td>{{ log.action }}</td>
                            <td>{{ log.created_at }}</td>
                          </tr>
                        </tbody>
                      </VTable>

                      <VPagination
                        v-model="auditsPage"
                        :length="auditsPages"
                        :total-visible="3"
                        rounded="circle"
                        @update:modelValue="adminSelected"
                      />
                    </VCard>
                  </VCol>
                </VRow>
              </VCard>
            </VCol>
          </VRow>
        </AppCardActions>
      </VWindowItem>
    </VWindow>
  </AppCardActions>
</template>
  