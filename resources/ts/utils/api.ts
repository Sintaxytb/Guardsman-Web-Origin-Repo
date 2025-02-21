import axios from 'axios'

const axiosIns = axios.create({
  // You can add your headers here
  // ================================
  // baseURL: 'https://supersecretproject.imskyyc.xyz/api/',
  baseURL: `${window.origin}/api/`
  // timeout: 1000,
  // headers: {'X-Custom-Header': 'foobar'}
})

export default axiosIns
export const baseUrl = window.origin
