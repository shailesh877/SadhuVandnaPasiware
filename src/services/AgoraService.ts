
import axios from 'axios';
import { API_BASE_URL } from './api';

export const AGORA_APP_ID = '42eb51e0bc30431cba75efefb9ea15ea';
export const AGORA_SERVER_URL = 'https://www.call.sadhuvandna.co.in';

export const getAgoraToken = async (channelName: string, uid: number): Promise<string> => {
    // 1. Try Primary Node Token Server
    try {
        const response = await axios.get(`${AGORA_SERVER_URL}/rtc-token`, {
            params: {
                channelName,
                uid,
                role: 'publisher',
                tokentype: 'uid',
            },
            timeout: 4000,
        });
        if (response.data && response.data.token) {
            return response.data.token;
        }
    } catch (error) {
        console.warn('Primary Node token server unreachable, trying PHP backend API...');
    }

    // 2. Try PHP Backend Token Server
    try {
        const phpResponse = await axios.get(`${API_BASE_URL}generate_agora_token.php`, {
            params: { channelName, uid },
            timeout: 5000,
        });
        if (phpResponse.data && phpResponse.data.token) {
            return phpResponse.data.token;
        }
    } catch (e) {
        console.warn('PHP token fallback failed');
    }

    return '';
};
