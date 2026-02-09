import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from './api';

Notifications.setNotificationHandler({
    handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
        shouldShowBanner: true,
        shouldShowList: true,
    }),
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
                projectId: '5ee8422c-a223-4f34-b76c-488624969e93' // Found in app.json
            })).data;
            console.log("Expo Push Token:", token);
        } catch (e) {
            console.error(e);
        }
    } else {
        alert('Must use physical device for Push Notifications');
    }

    return token;
};

export const updateServerToken = async (userId: string) => {
    const token = await registerForPushNotificationsAsync();
    if (token) {
        try {
            const fd = new FormData();
            fd.append('user_id', userId);
            fd.append('fcm_token', token);
            await api.post('/update_fcm_token.php', fd);
            console.log("Token updated on server");
        } catch (e) {
            console.error("Token update failed", e);
        }
    }
};
