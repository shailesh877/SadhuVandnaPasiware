import React, { useEffect, useState } from 'react';
import { View, Text, Image, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation } from '@react-navigation/native';
import api, { API_BASE_URL } from '../../services/api';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const ProfileScreen = () => {
    const [user, setUser] = useState<any>(null);
    const [marriageProfile, setMarriageProfile] = useState<any>(null);
    const navigation = useNavigation<any>();
    const { t } = useLanguage();

    useEffect(() => {
        loadUser();
    }, []);

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) {
            const userData = JSON.parse(u);
            setUser(userData);
            checkMarriageProfile(userData.id);
        }
    };

    const checkMarriageProfile = async (userId: string) => {
        try {
            const res = await api.get(`/get_my_profile.php?user_id=${userId}`);
            if (res.data.status === 'success') {
                setMarriageProfile(res.data.data);
            } else {
                setMarriageProfile(null);
            }
        } catch (e) {
            setMarriageProfile(null);
        }
    };

    const handleLogout = async () => {
        Alert.alert(
            t('logoutTitle'),
            t('logoutConfirm'),
            [
                { text: t('cancel'), style: "cancel" },
                {
                    text: t('logout'),
                    style: 'destructive',
                    onPress: async () => {
                        await AsyncStorage.removeItem('user');
                        navigation.reset({
                            index: 0,
                            routes: [{ name: 'Auth' }],
                        });
                    }
                }
            ]
        );
    };

    if (!user) return <View className="flex-1 bg-white" />;

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <View className="p-4 bg-white border-b border-gray-100 mb-4">
                <Text className="text-2xl font-bold text-gray-800">{t('menu')}</Text>
            </View>

            <ScrollView contentContainerStyle={{ padding: 20 }}>
                {/* Profile Card / Link */}
                <TouchableOpacity
                    className="flex-row items-center bg-white p-4 rounded-2xl shadow-sm mb-6"
                    onPress={() => navigation.navigate('PublicProfile', { userId: user.id })}
                >
                    <Image
                        source={{ uri: user.profile_photo ? `${PHOTO_URL}${user.profile_photo}` : 'https://via.placeholder.com/100' }}
                        className="w-16 h-16 rounded-full bg-gray-200 mr-4"
                    />
                    <View className="flex-1">
                        <Text className="text-lg font-bold text-gray-900">{user.name}</Text>
                        <Text className="text-gray-500">{t('viewProfile')}</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={24} color="#d1d5db" />
                </TouchableOpacity>

                {/* Grid Options */}
                <Text className="text-gray-500 font-bold mb-3 ml-2 uppercase text-xs tracking-wider">{t('explore')}</Text>
                <View className="flex-row flex-wrap justify-between mb-6">
                    {[
                        { label: t('temples'), icon: "home", route: "Temples", color: "#f97316" },
                        { label: t('branches'), icon: "git-branch", route: "Branches", color: "#8b5cf6" },
                        { label: t('jobs'), icon: "briefcase", route: "Jobs", color: "#10b981" },
                        { label: t('shokSandesh'), icon: "reader", route: "ShokSanvedana", color: "#525252" },
                        { label: t('gallery'), icon: "images", route: "Gallery", color: "#eab308" },
                        { label: "Make Festival Poster", icon: "brush", route: "FestivalPoster", color: "#f43f5e" },
                    ].map((item, index) => (
                        <TouchableOpacity
                            key={index}
                            onPress={() => navigation.navigate(item.route)}
                            className="w-[48%] bg-white p-4 rounded-xl mb-4 items-center shadow-sm"
                        >
                            <View className="w-12 h-12 rounded-full items-center justify-center mb-2 bg-gray-50">
                                <Ionicons name={item.icon as any} size={24} color={item.color} />
                            </View>
                            <Text className="font-semibold text-gray-800">{item.label}</Text>
                        </TouchableOpacity>
                    ))}
                </View>

                {/* Settings / Other */}
                <Text className="text-gray-500 font-bold mb-3 ml-2 uppercase text-xs tracking-wider">{t('general')}</Text>
                <View className="bg-white rounded-2xl p-2 shadow-sm mb-6">
                    <MenuItem label={t('settings')} icon="settings-outline" onPress={() => navigation.navigate('Settings')} />

                </View>

                <TouchableOpacity
                    className="bg-white rounded-2xl p-4 shadow-sm flex-row items-center mb-6"
                    onPress={handleLogout}
                >
                    <View className="w-10 h-10 rounded-full items-center justify-center bg-red-50 mr-4">
                        <Ionicons name="log-out-outline" size={22} color="#ef4444" />
                    </View>
                    <Text className="flex-1 text-base font-semibold text-red-500">{t('logout')}</Text>
                </TouchableOpacity>

                <View className="items-center pb-8">
                    <Text className="text-gray-400 text-xs">Version 1.0.0</Text>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const MenuItem = ({ label, icon, onPress }: any) => (
    <TouchableOpacity onPress={onPress} className="flex-row items-center p-4 border-b border-gray-50 last:border-0">
        <View className="w-8 h-8 rounded-full bg-gray-50 items-center justify-center mr-4">
            <Ionicons name={icon} size={20} color="gray" />
        </View>
        <Text className="flex-1 text-lg font-medium text-gray-700">{label}</Text>
        <Ionicons name="chevron-forward" size={20} color="#d1d5db" />
    </TouchableOpacity>
);

export default ProfileScreen;
