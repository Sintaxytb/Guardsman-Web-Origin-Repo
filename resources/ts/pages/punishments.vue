<route lang="yaml">
  meta:
    action: moderate
    subject: moderate
    title: Punishments
</route>

<script lang="ts">
import axios from "@axios";
import { themeConfig } from "@themeConfig";

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
    const punishmentTypes = ref<any>({});

    axios.get("punishments/types")
      .then(response => {
        punishmentTypes.value = response.data;
      })

    return {
      themeConfig,

      punishmentTypes,
      rowCount: ref<number>(10),
      currentPage: ref<number>(1),
      punishments: ref<IPunishment[]>([]),
      pages: ref<number>(0),
      filter: ref<string>(''),
      showInactive: ref<boolean>(false)
    }
  },

  mounted() {
    this.getPunishments();
  },

  methods: {
    async getPunishments() {
      let foundPunishments: IPunishment[] = (await (axios.get("punishments"))).data;
      const parsedPunishments: IPunishment[] = []

      const filter = this.filter;

      if (filter != "") {
        foundPunishments = foundPunishments.filter(punishment => {
          let filterPass = false;

          for (const value of Object.values(punishment)) {
            if (typeof value == "string" && value.includes(filter)) {
              filterPass = true;
            }
          }

          return filterPass;
        })
      }

      foundPunishments = foundPunishments.filter(punishment => {
        if (punishment.active == 0) {
          return this.showInactive;
        } else {
          return true;
        }
      })

      const rowCount = this.rowCount;
      const currentPage = this.currentPage;

      for (let index = (rowCount * currentPage) - rowCount; index < rowCount * currentPage; index++) {
        const punishment: IPunishment = foundPunishments[index];
        if (!punishment) continue;

        parsedPunishments.push(punishment);
      }

      let pageCount = Math.ceil(foundPunishments.length / rowCount);
      if (pageCount < 1) pageCount = 1;

      this.punishments = parsedPunishments;
      this.pages = pageCount;
    },

    async refresh(hideOverlay: any) {
      this.currentPage = 1;
      await this.getPunishments();

      hideOverlay();
    }
  }
});

</script>

<template>
  <div>
    <AppCardActions
      :title="themeConfig.app.title + ' Punishments'"
      action-refresh
      @refresh="refresh"
    >
      <VRow class="d-flex justify-center">
        <VCol
          cols="12"
          lg="3"
          class="px-6"
        >
          <VCard variant="flat" class="pt-4">
            <VSelect
              v-model="rowCount"
              label="Rows"
              name="rowCount"
              :items="[10, 25, 50, 100]"
              @update:modelValue="refresh"
            />
          </VCard>
        </VCol>
        
        <VCol
          cols="12"
          lg="3"
          class="px-6"
        >
          <VCard variant="flat" class="pt-4">
            <VTextField
              label="Filter (Search)"
              v-model="filter"
              @update:modelValue="getPunishments"
            />
          </VCard>
        </VCol>

        <VCol
          cols="12"
          lg="3"
          class="px-6"
        >
          <VCard variant="flat" class="pt-4">
            <VCheckbox
              v-model="showInactive"
              label="Show Inactive Punishments"
              @update:modelValue="getPunishments"
            />
          </VCard>
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="12">
          <VCard variant="flat" title="Punishments">
            <VTable>
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">USER</th>
                  <th scope="col">MODERATOR</th>
                  <th scope="col">TYPE</th>
                  <th scope="col">REASON</th>
                  <th scope="col">ACTIVE</th>
                  <th scope="col">EXPIRES</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="punishment in punishments"
                >
                  <td> {{ punishment.id }} </td>
                  <td> {{ punishment.user  }} </td>
                  <td> {{ punishment.moderator }} </td>
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
                  <td> {{ punishment.reason }} </td>
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
                  <td v-if="punishment.expires"> {{ new Date(punishment.expires * 1000) }}  </td>
                  <td v-else> Never </td>
                </tr>
              </tbody>
            </VTable>

            <VPagination
              v-model="currentPage"
              :length="pages"
              :total-visible="10"
              rounded="circle"
              @update:modelValue="getPunishments"
            />
          </VCard>
        </VCol>
      </VRow>
    </AppCardActions>
  </div>
</template>

<style lang="scss">
#three-line-list {
  .v-list-item-title {
    margin-block-end: 0.25rem;
  }

  .v-divider {
    margin-block: 0.25rem;
  }
}
</style>
