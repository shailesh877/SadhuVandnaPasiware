import axios from 'axios';

// Production URL
const PROD_URL = 'https://www.sadhuvandna.co.in/Api/';
const LOCAL_URL = 'http://10.0.2.2/bangosambadApp/Api/';

// Toggle between Production and Local here
export const API_BASE_URL = PROD_URL; 
export const WEBSITE_URL = 'https://www.sadhuvandna.co.in';

export const getFullPaymentUrl = (paymentUrl: string): string => {
    if (!paymentUrl) return '';
    if (paymentUrl.startsWith('http://') || paymentUrl.startsWith('https://')) {
        return paymentUrl;
    }
    const cleanBase = API_BASE_URL.endsWith('/') ? API_BASE_URL : `${API_BASE_URL}/`;
    if (paymentUrl.startsWith('/')) {
        const rootUrl = API_BASE_URL.replace(/\/Api\/?$/, '');
        return `${rootUrl}${paymentUrl}`;
    }
    return `${cleanBase}${paymentUrl}`;
};

const api = axios.create({
    baseURL: API_BASE_URL,
    timeout: 30000, // Increased timeout to 30s for slower server responses
});

// Request Interceptor for logging
api.interceptors.request.use(
    (config) => {
        console.log(`[API Request] ${config.method?.toUpperCase()} ${config.url}`, config.data || '');
        return config;
    },
    (error) => {
        console.warn('[API Request Error]', error);
        return Promise.reject(error);
    }
);

// Response Interceptor for logging
api.interceptors.response.use(
    (response) => {
        console.log(`[API Response] ${response.status} from ${response.config.url}`);
        return response;
    },
    (error) => {
        if (error.response) {
            console.warn(`[API Response Error] ${error.response.status} from ${error.config.url}`, error.response.data || '');
        } else if (error.request) {
            console.warn(`[API Network Error] No response received from ${error.config.url}. Check server status or URL.`, error.message);
        } else {
            console.warn('[API Error]', error.message);
        }
        return Promise.reject(error);
    }
);

export default api;
