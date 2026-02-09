
import axios from 'axios';

// Replace with your actual local IP address if testing on device
// e.g., 'http://192.168.1.5:3000'
// Ideally this should be in an environment variable
const AGORA_SERVER_URL = 'http://192.168.1.4:3000'; // Updated to 192.168.1.4 as per user

export const getAgoraToken = async (channelName: string, uid: number) => {
    try {
        const response = await axios.get(`${AGORA_SERVER_URL}/rtc-token`, {
            params: {
                channelName,
                uid,
                role: 'publisher', // 'publisher' or 'subscriber'
                tokentype: 'uid', // 'uid' or 'userAccount'
            },
        });
        return response.data.token;
    } catch (error) {
        console.error('Error fetching Agora token:', error);
        return null;
    }
};
