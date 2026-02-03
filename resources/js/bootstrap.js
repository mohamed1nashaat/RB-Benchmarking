import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add request interceptor to include X-Tenant-ID header
axios.interceptors.request.use((config) => {
    const tenantId = localStorage.getItem('current_tenant_id') || sessionStorage.getItem('current_tenant_id');
    if (tenantId && tenantId !== 'null') {
        config.headers['X-Tenant-ID'] = tenantId;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});
