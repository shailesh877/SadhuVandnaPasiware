import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
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

export async function registerForPushNotificationsAsync(userId?: string) {
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
            // alert('Failed to get push token for push notification!');
            console.log('Failed to get push token');
            return;
        }

        // Get the token
        try {
            token = (await Notifications.getExpoPushTokenAsync()).data;
            console.log("Expo Push Token:", token);

            // Send to backend if user is logged in
            if (userId && token) {
                await sendTokenToBackend(userId, token);
            }

        } catch (error) {
            console.log("Error getting token:", error);
        }
    } else {
        console.log('Must use physical device for Push Notifications');
    }

    return token;
}

const sendTokenToBackend = async (userId: string, token: string) => {
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('token', token);
        formData.append('platform', Platform.OS);

        await api.post('/update_device_token.php', formData);
        console.log("Token sent to backend");
    } catch (error) {
        console.error("Failed to send token:", error);
    }
};
