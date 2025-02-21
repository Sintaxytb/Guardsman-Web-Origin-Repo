
import axios from "@axios";
import { App } from "vue";

export default function (app: App) {
  const globalProperties = app.config.globalProperties;

  app.mixin({
    methods: {
      $useAvatar: async (userId: number) => {
        const thumbnailData = (await axios.get(`thumbnail/${userId}`)).data;
        
        if (thumbnailData.data.status == "Pending") {
          await new Promise((resolve) => setTimeout(resolve, 2000))
          return globalProperties.$useAvatar(userId);
        }
  
        return thumbnailData.data[0].imageUrl;
      },

      $sleep: async (time: number) => {
        return await new Promise((resolve) => { setTimeout(resolve, time) })
      },
    }
  })
}
