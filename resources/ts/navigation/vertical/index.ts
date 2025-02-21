import type { VerticalNavItems } from '@/@layouts/types'

export default [
  {
    title: 'Dashboard',
    to: { name: 'dashboard' },
    icon: { icon: 'tabler-smart-home' },
  },
  {
    title: 'Webmail',
    icon: { icon: 'tabler-mail' },
    href: 'https://webmail.imskyyc.com',
    target: '_blank'
  },
  {
    title: 'Statistics',
    to: { name: 'statistics' },
    icon: { icon: 'tabler-gauge' },
  },
  {
    title: 'Audit Logs',
    to: { name: 'audit-logs' },
    icon: { icon: 'tabler-list' },
  },
  { 
    heading: 'Moderation',

    action: 'moderate',
    subject: 'search'
  },
  {
    title: 'Punishments',
    to: { name: 'punishments' },
    icon: { icon: 'tabler-gavel' },

    action: 'moderate',
    subject: 'search'
  },
  {
    title: 'Player Management',
    to: { name: 'player-management' },
    icon: { icon: 'tabler-user' },

    action: 'moderate',
    subject: 'search'
  },
  { 
    heading: 'Administration',
    action: 'manage',
    subject: 'manage'
  },
  {
    title: 'Server Management',
    to: { name: 'server-management' },
    icon: { icon: 'tabler-server' },

    action: 'manage',
    subject: 'servers'
  },
  {
    title: 'Anti-Exploit Logs',
    to: { name: 'exploit-logs' },
    icon: { icon: 'tabler-user-cancel' },

    action: 'manage',
    subject: 'exploit-logs'
  },
  { 
    heading: 'Development',
    action: 'development',
    subject: 'development'
  },
  {
    title: 'API Keys',
    to: { name: 'api-keys' },
    icon: { icon: 'tabler-key' },

    action: 'development',
    subject: 'development',
  },
  {
    title: 'Source Control',
    icon: { icon: 'tabler-brand-gitlab' },
    href: "https://git.bunkerbravointeractive.com",
    target: "_blank",

    action: 'development',
    subject: 'source-control'
  },
  {
    title: 'Error Tracking',
    icon: { icon: 'tabler-brand-sentry' },
    href: "https://sentry.bunkerbravointeractive.com",
    target: "_blank",

    action: 'development',
    subject: 'error-tracking'
  },
  { 
    heading: 'Panel Administration',
    action: 'administrate',
    subject: 'administrate' 
  },
  {
    title: 'Admin Dashboard',
    to: { name: 'admin-dashboard' },
    icon: { icon: 'tabler-layout-dashboard' },

    action: 'administrate',
    subject: 'administrate'
  },
  {
    title: 'Panel Management',
    to: { name: 'panel-management' },
    icon: { icon: 'tabler-folder-cog' },

    action: 'administrate',
    subject: 'manage-panel'
  },
  {
    title: 'Group Management',
    to: { name: "group-management" },
    icon: { icon: 'tabler-users-group' },

    action: 'administrate',
    subject: 'manage-group'
  }
] as VerticalNavItems
