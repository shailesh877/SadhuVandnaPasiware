import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation, useIsFocused } from '@react-navigation/native';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const ChatListScreen = () => {
    const [friends, setFriends] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const navigation = useNavigation<any>();
    const isFocused = useIsFocused();
    const { t } = useLanguage();

    useEffect(() => {
        if (isFocused) fetchFriends();
    }, [isFocused]);

    const fetchFriends = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                const res = await api.get(`/get_active_chats.php?user_id=${user.id}`);
                if (res.data.status === 'success') {
                    setFriends(res.data.data);
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const renderItem = ({ item }: { item: any }) => {
        // NORMALIZE RECEIVER OBJECT
        const receiverObj = {
            id: item.partner_id, // Ensure ID is top-level
            name: item.full_name, // Ensure Name is top-level
            photo: item.profile_photo || item.photo, // Normalize photo to 'photo'
            ...item
        };

        return (
            <TouchableOpacity
                className="flex-row items-center p-4 bg-white border-b border-gray-100"
                onPress={() => navigation.navigate('Chat', { receiver: receiverObj })}
            >
                <Image
                    source={{ uri: (item.profile_photo) ? `${PHOTO_URL}${encodeURIComponent(item.profile_photo)}` : 'https://via.placeholder.com/100' }}
                    className="w-14 h-14 rounded-full bg-gray-200 mr-4"
                />
                <View className="flex-1">
                    <View className="flex-row justify-between items-center mb-1">
                        <Text className="text-lg font-bold text-gray-900">{item.full_name}</Text>
                        <Text className="text-xs text-gray-500">{item.time}</Text>
                    </View>
                    <Text className="text-gray-600 text-sm" numberOfLines={1}>{item.last_message || 'Photo'}</Text>
                </View>
            </TouchableOpacity>
        );
    };

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="p-4 border-b border-orange-100 bg-orange-50">
                <Text className="text-2xl font-bold text-orange-600">{t('messages')}</Text>
            </View>

            <FlatList
                data={friends}
                renderItem={renderItem}
                keyExtractor={(item, index) => item.partner_id ? item.partner_id.toString() : index.toString()}
                ListEmptyComponent={
                    <View className="items-center mt-20 p-4">
                        <Text className="text-4xl mb-4">📭</Text>
                        <Text className="text-gray-500 text-center text-lg">{t('noConversations')}</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

export default ChatListScreen;
