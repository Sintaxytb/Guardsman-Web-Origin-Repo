<route lang="yaml">
  meta:
    title: Audit Logs
</route>

<script lang="ts">
import axios from "@axios";
import { themeConfig } from "@themeConfig";

interface IAuditLog {
  id: string,
  user: string,
  type: string,
  action: string,
  created_at: string,
  updated_at: string
}

export default defineComponent({
  data() {
    return {
      themeConfig,

      rowCount: ref<number>(10),
      currentPage: ref<number>(1),
      audits: ref<IAuditLog[]>([]),
      pages: ref<number>(0),
      filter: ref<string>('')
    }
  },

  mounted() {
    this.getPunishments();
  },

  methods: {
    async getPunishments() {
      let foundAuditLogs: IAuditLog[] = (await (axios.get("audits"))).data;
      const parsedAuditLogs: IAuditLog[] = []

      const filter = this.filter;

      if (filter != "") {
        foundAuditLogs = foundAuditLogs.filter(punishment => {
          let filterPass = false;

          for (const value of Object.values(punishment)) {
            if (typeof value == "string" && value.includes(filter)) {
              filterPass = true;
            }
          }

          return filterPass;
        })
      }

      const rowCount = this.rowCount;
      const currentPage = this.currentPage;

      for (let index = (rowCount * currentPage) - rowCount; index < rowCount * currentPage; index++) {
        const punishment: IAuditLog = foundAuditLogs[index];
        if (!punishment) continue;

        parsedAuditLogs.push(punishment);
      }

      let pageCount = Math.ceil(foundAuditLogs.length / rowCount);
      if (pageCount < 1) pageCount = 1;

      this.audits = parsedAuditLogs;
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
      :title="themeConfig.app.title + ' Audit Logs'"
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
      </VRow>

      <VRow>
        <VCol cols="12">
          <VCard variant="flat" title="Punishments">
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
                <tr
                  v-for="log in audits"
                >
                  <td> {{ log.id }} </td>
                  <td> {{ log.user }} </td>
                  <td> {{ log.type }} </td>
                  <td> {{ log.action }} </td>
                  <td> {{ log.created_at }} </td>
                </tr>
              </tbody>
            </VTable>

            <VPagination
              v-model="currentPage"
              :length="pages"
              :total-visible="3"
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
