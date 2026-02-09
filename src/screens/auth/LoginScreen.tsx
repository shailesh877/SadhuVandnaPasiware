import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Image, Alert, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useLanguage } from '../../context/LanguageContext';

export default function LoginScreen() {
    const navigation = useNavigation<any>();
    const { t } = useLanguage();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);

    const handleLogin = async () => {
        if (!email || !password) {
            Alert.alert(t('error'), t('fillAllFields'));
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('user', email);
            formData.append('password', password);

            const response = await api.post('/api_login.php', formData);
            const data = response.data;

            if (data.status === 'success') {
                await AsyncStorage.setItem('user', JSON.stringify(data.user));
                // Reset navigation stack to MainTabs
                navigation.reset({
                    index: 0,
                    routes: [{ name: 'MainTabs' }],
                });
            } else {
                Alert.alert(t('loginFailed'), data.message || 'Invalid credentials');
            }
        } catch (error) {
            console.error(error);
            Alert.alert(t('error'), 'Network request failed. Check your URL.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <View className="flex-1 bg-white justify-center px-6">
            <View className="items-center mb-10">
                <Image
                    source={require('../../../assets/logo.png')}
                    className="w-32 h-32 rounded-full mb-4"
                    resizeMode="contain"
                />
                <Text className="text-3xl font-extrabold text-orange-600 tracking-wider">{t('appName')}</Text>
                <Text className="text-gray-500 font-medium mt-1">Community Connect</Text>
            </View>

            <View className="bg-white p-6">
                <Text className="text-2xl font-bold text-gray-800 mb-8">{t('welcomeBack')}</Text>

                <View className="mb-4 space-y-4">
                    <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                        <Ionicons name="mail-outline" size={20} color="gray" />
                        <TextInput
                            className="flex-1 ml-3 text-gray-700 text-base"
                            placeholder={t('emailOrMobile')}
                            value={email}
                            onChangeText={setEmail}
                            autoCapitalize="none"
                            placeholderTextColor="#9ca3af"
                        />
                    </View>

                    <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                        <Ionicons name="lock-closed-outline" size={20} color="gray" />
                        <TextInput
                            className="flex-1 ml-3 text-gray-700 text-base"
                            placeholder={t('password')}
                            value={password}
                            onChangeText={setPassword}
                            secureTextEntry
                            placeholderTextColor="#9ca3af"
                        />
                    </View>
                </View>

                <TouchableOpacity
                    className="self-end mb-6"
                    onPress={() => navigation.navigate('ForgotPassword')}
                >
                    <Text className="text-orange-500 font-semibold">{t('forgotPassword')}</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    className="w-full bg-orange-600 py-4 rounded-xl items-center shadow-lg shadow-orange-200 mb-6"
                    onPress={handleLogin}
                    disabled={loading}
                >
                    {loading ? (
                        <ActivityIndicator color="white" />
                    ) : (
                        <Text className="text-white font-bold text-lg">{t('login')}</Text>
                    )}
                </TouchableOpacity>

                <View className="flex-row justify-center items-center">
                    <Text className="text-gray-500">{t('dontHaveAccount')} </Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Register')}>
                        <Text className="text-orange-600 font-bold ml-1">{t('createAccount')}</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </View>
    );
}
