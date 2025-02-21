import type { App } from 'vue'

import useAbility from '@/plugins/casl/ability'
import { themeConfig } from '@themeConfig'
import { setupLayouts } from 'virtual:generated-layouts'
import type { RouteRecordRaw } from 'vue-router/auto'
// eslint-disable-next-line import/no-unresolved
import { canNavigate } from '@/@layouts/plugins/casl'
import axios from '@axios'
import { createRouter, createWebHistory } from 'vue-router/auto'

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])

    return route
  }

  return setupLayouts([route])[0]
}

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
  extendRoutes: pages => [
    ...[...pages].map(route => recursiveLayouts(route)),
  ],
})

export { router }

export default function (app: App) {
  app.use(router)

  router.beforeEach(async (to, from, next) => {
    let user: any = undefined
  
    // This goes through the matched routes from last to first, finding the closest route with a title.
    // e.g., if we have `/some/deep/nested/route` and `/some`, `/deep`, and `/nested` have titles,
    // `/nested`'s will be chosen.
    const nearestWithTitle = to.matched
      .slice()
      .reverse()
      .find(r => r.meta && r.meta.title)
  
    // Find the nearest route element with meta tags.
    const nearestWithMeta: any = to.matched
      .slice()
      .reverse()
      .find(r => r.meta && r.meta.metaTags)
  
    const previousNearestWithMeta = from.matched
      .slice()
      .reverse()
      .find(r => r.meta && r.meta.metaTags)
  
    // If a route with a title was found, set the document (page) title to that value.
    if (nearestWithTitle)
      document.title = `${themeConfig.app.title} - ${nearestWithTitle.meta.title}`
    else if (previousNearestWithMeta)
      document.title = `${themeConfig.app.title} - ${previousNearestWithMeta.meta.title}`
  
    // Remove any stale meta tags from the document using the key attribute we set below.
    Array.from(document.querySelectorAll('[data-vue-router-controlled]')).map(
      el => el.parentNode?.removeChild(el),
    )
  
    // Skip rendering meta tags if there are none.
    if (nearestWithMeta) {
      // Turn the meta tag definitions into actual elements in the head.
      nearestWithMeta.meta.metaTags
        .map((tagDef: any) => {
          const tag = document.createElement('meta')
  
          Object.keys(tagDef).forEach(key => {
            tag.setAttribute(key, tagDef[key])
          })
  
          // We use this to track which meta tags we create so we don't interfere with other ones.
          tag.setAttribute('data-vue-router-controlled', '')
  
          return tag
        })
  
        // Add the meta tags to the document head.
        .forEach((tag: any) => document.head.appendChild(tag))
    }
  
    try {
        //  user = {
        //    id: 1,
        //    user_id: 62097945,
        //    username: "imskyyc",
        //    user_id: "250805980491808768",
  
        //    level: 5,
        //    game_data: {},
  
        //    punishment: {},
  
        //    created_at: Date.now(),
        //    updated_at: Date.now()
        //  };

      try {    
        let response = await axios.get("user");
        user = response.data;
      } catch (error) {
        user = undefined;
      }

      localStorage.setItem("user", JSON.stringify(user));

      app.config.globalProperties.$ability = useAbility();
    } catch (error) {
      console.log(error);
    }
  
    if (from.path == to.path && from.query.status) {
      to.query.status = from.query.status;
    }
  
    switch (to.path) {
      case "/":
        if (user) {
          next({ path: "/dashboard" });
  
          break;
        }
  
        next({ path: "/login" });
        break;
      case "/login":
        if (user) {
          next({ path: "/dashboard" });
          break;
        }
  
        next();
        break;
      case "/create-password":
        next();
        break;
      default:
        if (user == null && to.meta.auth != "none") {
          next({ path: "/login", query: { status: "ENOLOGIN" } });
  
          break;
        }
  
        if (canNavigate(to)) {
          next()
        } else {
          next({ path: "/dashboard", query: { status: "ENOACCESS" }})
        }
  
    //     // const routeLevel = to.meta?.level;
  
    //     // if (routeLevel) {
    //     //   const userLevel = user.level || 0;
  
    //     //   if (userLevel >= routeLevel) {
    //     //     next();
  
    //     //     break;
    //     //   }
  
    //     //   next({ path: "/dashboard", query: { status: "ENOACCESS" } });
    //     //   break;
    //     // } else {
    //     //   next();
    //     // }
    //     break;
    }
  //  next();
  })
}
