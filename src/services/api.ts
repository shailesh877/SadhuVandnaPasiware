import axios from 'axios';

// Production URL
export const API_BASE_URL = 'https://www.sadhuvandna.co.in/Api';
export const WEBSITE_URL = 'https://www.sadhuvandna.co.in';

// Local URL (for Emulator usage: 10.0.2.2 points to host localhost)
// Change this to your local IP if testing on real device
// export const API_BASE_URL = 'http://10.0.2.2/bangosambadApp/Sadhuvandna-Api';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'multipart/form-data',
    },
});

export default api;
