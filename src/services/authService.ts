import api from './api';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const authService = {
    login: async (email: string, password: string) => {
        try {
            const response = await api.post('/login.php', { email, password });
            if (response.data.status === 'success') {
                await SecureStore.setItemAsync('token', response.data.data.token);
                await AsyncStorage.setItem('user', JSON.stringify(response.data.data)); // Store user data for app usage
                return response.data.data;
            } else {
                throw new Error(response.data.message);
            }
        } catch (error) {
            throw error;
        }
    },

    register: async (userData: any) => {
        try {
            const response = await api.post('/register.php', userData);
            if (response.data.status === 'success') {
                return response.data;
            } else {
                throw new Error(response.data.message);
            }
        } catch (error) {
            throw error;
        }
    },

    logout: async () => {
        await SecureStore.deleteItemAsync('token');
    }
};
