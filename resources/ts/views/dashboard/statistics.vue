<script lang="ts">

export default defineComponent({
  data() {
    let userStatistics = ref(this.getStatisticsWithData({
      title: 'Verified Users',
      color: '#36b9cc',
      icon: 'tabler-user-circle',
      stats: '',
      height: 120,
      series: [
        {
          data: [],
        },
      ],
    }));

    let serverStatistics = ref(this.getStatisticsWithData({
      title: 'Active Servers',
      color: '#36b9cc',
      icon: 'tabler-server',
      stats: '',
      height: 120,
      series: [
        {
          data: [],
        },
      ],
    }));

    let playerStatistics = ref(this.getStatisticsWithData({
      title: `Online Players`,
      color: '#36b9cc',
      icon: 'tabler-play',
      stats: '',
      height: 120,
      series: [
        {
          data: [],
        },
      ],
    }));

    fetch("statistics.json")
      .then(response => response.json())
      .then(response => {        
        userStatistics.value = this.getStatisticsWithData({
          title: 'Verified Users',
          color: '#36b9cc',
          icon: 'tabler-user-circle',
          stats: response[response.length - 1].users.toString(),
          height: 120,
          series: [
            {
              data: response.map((stat: any) => stat.users),
            },
          ],
        })

        serverStatistics.value = this.getStatisticsWithData({
          title: 'Active Servers',
          color: '#36b9cc',
          icon: 'tabler-server',
          stats: response[response.length - 1].servers.toString(),
          height: 120,
          series: [
            {
              data: response.map((stat: any) => stat.servers),
            },
          ],
        })

        playerStatistics.value = this.getStatisticsWithData({
          title: `Online Players`,
          color: '#36b9cc',
          icon: 'tabler-play',
          stats: response[response.length - 1].players.toString(),
          height: 120,
          series: [
            {
              data: response.map((stat: any) => stat.players)
            },
          ],
        })
      })
      .catch(response => {

      })

    return {
      userStatistics: userStatistics,
      serverStatistics: serverStatistics,
      playerStatistics: playerStatistics,
    }
  },

  methods: {
    getStatisticsWithData(data: any) {
      return {
        ...data,
        chartOptions: {
          chart: {
            height: 90,
            type: 'area',
            parentHeightOffset: 0,
            toolbar: {
              show: false,
            },
            sparkline: {
              enabled: true
            }
          },
          tooltip: {
            enabled: false,
          },
          markers: {
            colors: 'transparent',
            strokeColors: 'transparent',
          },
          grid: {
            show: false,
          },
          colors: [data.color],
          fill: {
            type: 'gradient',
            gradient: {
              shadeIntensity: 0.8,
              opacityFrom: 0.6,
              opacityTo: 0.1,
            },
          },
          dataLabels: {
            enabled: false,
          },
          stroke: {
            width: 2,
            curve: 'smooth',
          },
          xaxis: {
            show: true,
            lines: {
              show: false,
            },
            labels: {
              show: false,
            },
            stroke: {
              width: 0,
            },
            axisBorder: {
              show: false,
            },
          },
          yaxis: {
            stroke: {
              width: 0,
            },
            show: false,
          },
        },
      }
    },
  }
});

</script>

<template>
  <VRow class="match-height">
    <VCol
      cols="12"
      lg="4"
      md="4"
      sm="4"
    >
      <CardStatisticsVertical v-bind="userStatistics" />
    </VCol>

    <VCol
      cols="12"
      lg="4"
      md="4"
      sm="4"
    >
      <CardStatisticsVertical v-bind="serverStatistics" />
    </VCol>

    <VCol
      cols="12"
      lg="4"
      md="4"
      sm="4"
    >
      <CardStatisticsVertical v-bind="playerStatistics" />
    </VCol>
  </VRow>
</template>
