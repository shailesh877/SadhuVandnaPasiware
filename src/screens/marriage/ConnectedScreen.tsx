import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, RefreshControl, Alert, Linking } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL, getFullPaymentUrl } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api/', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const ConnectedScreen = ({ navigation }: any) => {
    const [profiles, setProfiles] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [userId, setUserId] = useState<string | null>(null);

    const [page, setPage] = useState(0);
    const [hasMore, setHasMore] = useState(true);
    const [isLoadMoreLoading, setIsLoadMoreLoading] = useState(false);

    useEffect(() => {
        AsyncStorage.getItem('user').then(u => {
            if (u) {
                setUserId(JSON.parse(u).id);
            }
        });
    }, []);

    useEffect(() => {
        if (userId) {
            fetchConnectedProfiles();
        }
    }, [userId]);

    const fetchConnectedProfiles = async (isMore = false) => {
        if (isMore && (isLoadMoreLoading || !hasMore)) return;

        if (isMore) setIsLoadMoreLoading(true);
        else {
            setLoading(true);
            setPage(0);
        }

        try {
            const currentOffset = isMore ? (page + 1) * 20 : 0;
            const res = await api.get(`get_matrimony_profiles.php?user_id=${userId}&type=connected&limit=20&offset=${currentOffset}`);
            if (res.data.status === 'success') {
                const newData = res.data.data || [];
                if (isMore) {
                    setProfiles(prev => [...prev, ...newData]);
                    setPage(prev => prev + 1);
                } else {
                    setProfiles(newData);
                    setPage(0);
                }
                setHasMore(newData.length === 20);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
            setIsLoadMoreLoading(false);
        }
    };

    const checkPaymentAndNavigate = async (receiverItem: any) => {
        if (!userId) return;

        try {
            const res = await api.post('check_chat_payment.php', {
                user_id: userId,
                receiver_id: receiverItem.friend_profile_id || receiverItem.id,
                platform: 'marriage'
            });

            if (res.data.status === 'success') {
                if (res.data.paid) {
                    const chatReceiver = {
                        ...receiverItem,
                        id: receiverItem.friend_profile_id || receiverItem.id
                    };
                    navigation.navigate('Chat', { receiver: chatReceiver, platform: 'marriage' });
                } else {
                    const paymentUrl = getFullPaymentUrl(res.data.payment_url);
                    Alert.alert(
                        "Payment Required",
                        "You need to pay to chat with this profile.",
                        [
                            { text: "Cancel", style: "cancel" },
                            {
                                text: "Pay Now",
                                onPress: () => Linking.openURL(paymentUrl)
                            }
                        ]
                    );
                }
            } else {
                Alert.alert("Error", res.data.message || "Failed to check payment status");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        }
    };

    const renderProfile = ({ item }: { item: any }) => (
        <TouchableOpacity
            activeOpacity={0.7}
            onPress={() => checkPaymentAndNavigate(item)}
            className="flex-row items-center bg-white p-4 mx-4 mb-3 rounded-2xl shadow-sm border border-gray-100"
        >
            <View className="relative">
                <Image
                    source={{ uri: (item.profile_photo || item.photo) ? `${PHOTO_URL}${encodeURIComponent(item.profile_photo || item.photo)}` : 'https://via.placeholder.com/150' }}
                    className="w-16 h-16 rounded-full bg-gray-200 border-2 border-orange-100"
                />
                <View className="absolute bottom-1 right-1 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white" />
            </View>

            <View className="flex-1 ml-4 justify-center">
                <View className="flex-row justify-between items-center mb-1">
                    <Text className="text-lg font-bold text-gray-800" numberOfLines={1}>{item.full_name}</Text>
                </View>
                <Text className="text-gray-500 text-xs" numberOfLines={1}>{item.city} • {item.age} yrs</Text>
                <Text className="text-orange-600/80 text-xs font-medium mt-1">Tap to chat</Text>
            </View>

            <View className="ml-2">
                <Ionicons name="chevron-forward" size={20} color="#cbd5e1" />
            </View>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <View className="px-4 py-4 bg-white border-b border-gray-100 shadow-sm z-10 flex-row items-center justify-between">
                <View className="flex-row items-center">
                    <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3 bg-gray-50 p-2 rounded-full">
                        <Ionicons name="arrow-back" size={24} color="#374151" />
                    </TouchableOpacity>
                    <Text className="text-2xl font-bold text-gray-800 tracking-tight">Messages</Text>
                </View>
            </View>

            {loading && !refreshing ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                    <Text className="text-gray-400 text-xs mt-3">Loading conversations...</Text>
                </View>
            ) : (
                <FlatList
                    data={profiles}
                    renderItem={renderProfile}
                    keyExtractor={item => item.proposal_id?.toString() || item.id?.toString()}
                    contentContainerStyle={{ paddingVertical: 16 }}
                    onEndReached={() => fetchConnectedProfiles(true)}
                    onEndReachedThreshold={0.5}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchConnectedProfiles(); }} colors={['#ea580c']} />}
                    ListFooterComponent={isLoadMoreLoading ? <ActivityIndicator color="#ea580c" style={{ margin: 10 }} /> : null}
                    ListEmptyComponent={
                        <View className="items-center justify-center py-20 px-10">
                            <View className="w-32 h-32 bg-orange-50 rounded-full items-center justify-center mb-6">
                                <Ionicons name="chatbubbles-outline" size={64} color="#fdba74" />
                            </View>
                            <Text className="text-gray-800 font-bold text-xl mb-2 text-center">No Connections Yet</Text>
                            <Text className="text-gray-500 text-center leading-5 mb-8">
                                Connect with profiles in the Matrimony section to start chatting.
                            </Text>
                        </View>
                    }
                />
            )}
        </SafeAreaView>
    );
};

export default ConnectedScreen;
