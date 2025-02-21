<script setup lang="ts">
import axios from '@axios';
import { useConfigStore } from '@core/stores/config';
import Shepherd from 'shepherd.js';
import type { RouteLocationRaw } from 'vue-router';

interface Suggestion {
  icon: string
  title: string
  url: RouteLocationRaw
}

interface SearchItem {
  url: object
  icon: string
  title: string
}

interface SearchResults {
  title: string
  category: string
  children: SearchItem[]
}

defineOptions({
  inheritAttrs: false,
})

const configStore = useConfigStore()

interface SuggestionGroup {
  title: string
  content: Suggestion[]
}

// 👉 Is App Search Bar Visible
const isAppSearchBarVisible = ref(false)

// 👉 Default suggestions

const suggestionGroups: SuggestionGroup[] = [
  {
    title: 'Popular Searches',
    content: [
      { icon: 'tabler-chart-donut', title: 'Analytics', url: { name: 'dashboards-analytics' } },
      { icon: 'tabler-chart-bubble', title: 'CRM', url: { name: 'dashboards-crm' } },
      { icon: 'tabler-file', title: 'Landing Page', url: { name: 'front-pages-landing-page' } },
      { icon: 'tabler-users', title: 'User List', url: { name: 'apps-user-list' } },
    ],
  },
  {
    title: 'Apps & Pages',
    content: [
      { icon: 'tabler-calendar', title: 'Calendar', url: { name: 'apps-calendar' } },
      { icon: 'tabler-shopping-cart', title: 'ECommerce Product', url: { name: 'apps-ecommerce-product-list' } },
      { icon: 'tabler-school', title: 'Academy', url: { name: 'apps-academy-dashboard' } },
      { icon: 'tabler-truck', title: 'Logistic Fleet', url: { name: 'apps-logistics-fleet' } },
    ],
  },
  {
    title: 'User Interface',
    content: [
      { icon: 'tabler-letter-a', title: 'Typography', url: { name: 'pages-typography' } },
      { icon: 'tabler-square', title: 'Tabs', url: { name: 'components-tabs' } },
      { icon: 'tabler-map', title: 'Tour', url: { name: 'extensions-tour' } },
      { icon: 'tabler-keyboard', title: 'Statistics', url: { name: 'pages-cards-card-statistics' } },
    ],
  },
  {
    title: 'Popular Searches',
    content: [
      { icon: 'tabler-list', title: 'Select', url: { name: 'forms-select' } },
      { icon: 'tabler-currency-dollar', title: 'Payment', url: { name: 'front-pages-payment' } },
      { icon: 'tabler-calendar', title: 'Date & Time Picker', url: { name: 'forms-date-time-picker' } },
      { icon: 'tabler-home', title: 'Property Listing Wizard', url: { name: 'wizard-examples-property-listing' } },
    ],
  },
]

// 👉 No Data suggestion
const noDataSuggestions: Suggestion[] = [
  {
    title: 'Analytics Dashboard',
    icon: 'tabler-shopping-cart',
    url: { name: 'dashboards-analytics' },
  },
  {
    title: 'Account Settings',
    icon: 'tabler-user',
    url: { name: 'pages-account-settings-tab', params: { tab: 'account' } },
  },
  {
    title: 'Pricing Page',
    icon: 'tabler-cash',
    url: { name: 'pages-pricing' },
  },
]

const searchQuery = ref('')

const router = useRouter()
const searchResult = ref<SearchResults[]>([])

watchEffect(() => {
  if (searchQuery.value == "") return;
  
  axios.get(`search/${searchQuery.value}`)
    .then(response => {
      searchResult.value = response.data
    })
})

// redirect the selected page
const redirectToSuggestedOrSearchedPage = (selected: Suggestion) => {
  console.log("Hi");
  router.push(selected.url as string)
  isAppSearchBarVisible.value = false
  searchQuery.value = ''
}

const LazyAppBarSearch = defineAsyncComponent(() => import('@core/components/AppBarSearch.vue'))
</script>

<template>
  <div
    class="d-flex align-center cursor-pointer"
    v-bind="$attrs"
    style="user-select: none;"
    @click="isAppSearchBarVisible = !isAppSearchBarVisible"
  >
    <!-- 👉 Search Trigger button -->
    <!-- close active tour while opening search bar using icon -->
    <IconBtn
      class="me-1"
      @click="Shepherd.activeTour?.cancel()"
    >
      <VIcon
        size="26"
        icon="tabler-search"
      />
    </IconBtn>

    <span
      v-if="configStore.appContentLayoutNav === 'vertical'"
      class="d-none d-md-flex align-center text-disabled"
      @click="Shepherd.activeTour?.cancel()"
    >
      <span class="me-3">Search</span>
      <span class="meta-key">&#8984;K</span>
    </span>
  </div>

  <!-- 👉 App Bar Search -->
  <LazyAppBarSearch
    v-model:isDialogVisible="isAppSearchBarVisible"
    v-model:search-query="searchQuery"
    :search-results="searchResult"
    @item-selected="redirectToSuggestedOrSearchedPage"
  >
    <!-- search result -->
    <template #searchResult="{ item }">
      <!-- <VListSubheader class="text-disabled">
        {{ item.title }}
      </VListSubheader> -->
      <VListItem
        v-for="list in item.children"
        :key="list.title"
        link
        @click="redirectToSuggestedOrSearchedPage(list)"
      >
        <template #prepend>
          <VIcon
            size="20"
            :icon="list.icon"
            class="me-3"
          />
        </template>
        <template #append>
          <VIcon
            size="20"
            icon="tabler-corner-down-left"
            class="enter-icon text-disabled"
          />
        </template>
        <VListItemTitle>
          {{ list.title }}
        </VListItemTitle>
      </VListItem>
    </template>
  </LazyAppBarSearch>
</template>

<style lang="scss" scoped>
@use "@styles/variables/vuetify.scss";

.meta-key {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 1.5625rem;
  line-height: 1.3125rem;
  padding-block: 0.125rem;
  padding-inline: 0.25rem;
}
</style>
