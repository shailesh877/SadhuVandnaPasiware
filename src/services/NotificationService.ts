import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import { navigationRef, navigateWithRetry } from '../navigation/navigationRef';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from './api';

Notifications.setNotificationHandler({
    handleNotification: async (notification) => {
        const data = notification.request.content.data;
        const isCall = data && (data.is_call || data.type === 'incoming_call');
        
        return {
            shouldShowBanner: !isCall, // Don't show banner for calls (App UI handles it)
            shouldShowList: true,
            shouldPlaySound: !isCall,  // Don't play system sound for calls (App UI handles it)
            shouldSetBadge: false,
        };
    },
});

export const registerForPushNotificationsAsync = async () => {
    let token;

    if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('default', {
            name: 'default',
            importance: Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 250, 250, 250],
            lightColor: '#FF231F7C',
        });

        await Notifications.setNotificationChannelAsync('incoming_calls', {
            name: 'Incoming Calls',
            importance: Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 500, 500, 500],
            lightColor: '#FF231F7C',
            sound: 'ringtone', // Matches android/app/src/main/res/raw/ringtone.mp3
            bypassDnd: true,
        });
    }

    if (Device.isDevice) {
        const { status: existingStatus } = await Notifications.getPermissionsAsync();
        let finalStatus = existingStatus;
        if (existingStatus !== 'granted') {
            const { status } = await Notifications.requestPermissionsAsync();
            finalStatus = status;
        }
        if (finalStatus !== 'granted') {
            alert('Failed to get push token for push notification!');
            return;
        }

        // Get the token
        try {
            token = (await Notifications.getExpoPushTokenAsync({
                projectId: 'e0e24b0e-4455-465b-baab-ee520d15958b' // Linked to pasiwaresocial@gmail.com
            })).data;
            console.log("Expo Push Token:", token);
        } catch (e: any) {
            console.log("[Notification] Could not fetch Expo Push Token (Expo server down):", e.message || e);
        }
    } else {
        alert('Must use physical device for Push Notifications');
    }

    return token;
};

Notifications.addNotificationResponseReceivedListener(response => {
    const data = response.notification.request.content.data as any;
    console.log("Notification Clicked:", JSON.stringify(data));
    
    if (data && (data.is_call || data.type === 'incoming_call')) {
        navigateWithRetry('AgoraCall', {
            channelId: data.peer_id || data.channelId || data.channel_id,
            isVideo: (data.call_type === 'video' || data.type === 'video'),
            isCaller: false,
            otherUserId: String(data.caller_id),
        });
    }

    // New: Social Post/Like Deep-linking
    if (data && (data.type === 'post' || data.type === 'like') && data.postId) {
        navigateWithRetry('PostDetail', { postId: data.postId });
    }
});

export const updateServerToken = async (userId: string) => {
    const token = await registerForPushNotificationsAsync();
    if (token) {
        try {
            const payload = {
                user_id: userId,
                fcm_token: token
            };
            await api.post('/update_fcm_token.php', payload);
            console.log("Token updated on server");
        } catch (e) {
            console.error("Token update failed", e);
        }
    }
};
