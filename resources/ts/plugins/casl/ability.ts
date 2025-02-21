import { defineAbility } from "@casl/ability";

export default function useAbility() {
  return defineAbility((can, cannot) => {
    let user = localStorage.getItem("user");
    if (user == "undefined" || typeof user != "string") return;
  
    let userData = JSON.parse(user);
    if (!userData) return;
  
    const permissions = userData.permissions || {};
    for (const permissionNode in permissions) {
      const permissionComponents = permissionNode.split(":");
  
      if (permissions[permissionNode]) {
        can(permissionComponents[0], permissionComponents[1]);
      } else {
        cannot(permissionComponents[0], permissionComponents[1]);
      }
    }
  });
}