import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, Image, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const FollowListScreen = ({ route, navigation }: any) => {
    const { userId, action, title } = route.params;

    const [currentUserId, setCurrentUserId] = useState<string | null>(null);
    const [users, setUsers] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(0);
    const [hasMore, setHasMore] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [processingId, setProcessingId] = useState<string | null>(null);

    useEffect(() => {
        const loadCurrentUser = async () => {
            const uStr = await AsyncStorage.getItem('user');
            if (uStr) {
                const u = JSON.parse(uStr);
                setCurrentUserId(u.id);
            }
        };
        loadCurrentUser();
    }, []);

    useEffect(() => {
        if (currentUserId) {
            fetchUsersList(false);
        }
    }, [currentUserId, userId, action]);

    const fetchUsersList = async (isMore = false) => {
        if (isMore && (loadingMore || !hasMore)) return;

        if (isMore) setLoadingMore(true);
        else {
            setLoading(true);
            setPage(0);
            setHasMore(true);
        }

        try {
            const currentOffset = isMore ? (page + 1) * 20 : 0;
            const res = await api.post(`/api_follow.php?action=${action}&limit=20&offset=${currentOffset}`, {
                current_user_id: currentUserId,
                user_id: userId
            });

            if (res.data.ok) {
                const newList = res.data.list || [];
                if (isMore) {
                    setUsers(prev => [...prev, ...newList]);
                    setPage(prev => prev + 1);
                } else {
                    setUsers(newList);
                }

                if (newList.length < 20) {
                    setHasMore(false);
                }
            } else {
                Alert.alert("Error", res.data.message || "Failed to load list");
            }
        } catch (error) {
            console.error("Fetch list error", error);
            Alert.alert("Error", "Network error while loading list.");
        } finally {
            setLoading(false);
            setLoadingMore(false);
        }
    };

    const handleToggleFollow = async (targetUserId: string) => {
        if (!currentUserId) return;
        setProcessingId(targetUserId);

        try {
            const formData = new FormData();
            formData.append('current_user_id', currentUserId);
            formData.append('user_id', targetUserId);
            const res = await api.post('/api_follow.php?action=follow', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.ok) {
                // Update local state without full refresh for better speed
                setUsers(prev => prev.map(u => {
                    if (u.id === targetUserId) {
                        const newIFollow = res.data.status !== 'unfollowed';
                        const newStatus = res.data.status === 'connected' ? 'accepted' : (res.data.status === 'requested' ? 'pending' : '');
                        return { ...u, i_follow: newIFollow, my_status: newStatus };
                    }
                    return u;
                }));
            } else {
                Alert.alert("Notice", res.data.message || "Failed to update status");
            }
        } catch (error) {
            Alert.alert("Error", "Network error while updating connection status.");
        } finally {
            setProcessingId(null);
        }
    };

    const renderEmpty = () => {
        if (loading) return null;
        return (
            <View className="flex-1 justify-center items-center py-20 px-6">
                <Ionicons name="people-outline" size={64} color="#d1d5db" />
                <Text className="text-gray-400 mt-4 text-center text-lg">No {title.toLowerCase()} found.</Text>
            </View>
        );
    };

    const renderItem = ({ item }: { item: any }) => {
        const isMe = String(item.id) === String(currentUserId);

        let btnText = "Connect";
        let btnStyle = "bg-orange-600 border-orange-700";
        let textStyle = "text-white";
        let iconName: any = "person-add-outline";

        if (item.i_follow) {
            if (item.my_status === 'accepted') {
                btnText = "Friends";
                btnStyle = "bg-green-100 border-green-200";
                textStyle = "text-green-700";
                iconName = "people";
            } else {
                btnText = "Requested";
                btnStyle = "bg-gray-100 border-gray-200";
                textStyle = "text-gray-600";
                iconName = "time-outline";
            }
        } else if (item.follows_me) {
            btnText = "Follow Back";
            btnStyle = "bg-orange-500 border-orange-600";
            iconName = "arrow-redo-outline";
        }

        return (
            <View className="px-4 py-2">
                <TouchableOpacity
                    activeOpacity={0.7}
                    className="flex-row items-center justify-between p-4 bg-white rounded-2xl shadow-sm border border-gray-100"
                    onPress={() => navigation.push('PublicProfile', { userId: item.id })}
                >
                    <View className="flex-row items-center flex-1">
                        <View className="relative">
                            {item.profile_photo ? (
                                <Image
                                    source={{ uri: `${PHOTO_URL}${item.profile_photo}` }}
                                    className="w-14 h-14 rounded-full border-2 border-orange-100 mr-4"
                                />
                            ) : (
                                <View className="w-14 h-14 rounded-full bg-orange-50 items-center justify-center border-2 border-orange-100 mr-4">
                                    <Text className="text-orange-600 font-bold text-xl">
                                        {item.name ? item.name.charAt(0).toUpperCase() : 'U'}
                                    </Text>
                                </View>
                            )}
                            {item.follows_me && (
                                <View className="absolute -bottom-1 -right-1 bg-green-500 w-4 h-4 rounded-full border-2 border-white items-center justify-center">
                                    <Ionicons name="checkmark" size={10} color="white" />
                                </View>
                            )}
                        </View>

                        <View className="flex-1 mr-2">
                            <Text className="font-extrabold text-gray-900 text-base" numberOfLines={1}>
                                {item.name || "Member"}
                            </Text>
                            <View className="flex-row items-center mt-1">
                                <Ionicons name="location-outline" size={12} color="#9ca3af" />
                                <Text className="text-xs text-gray-400 ml-1" numberOfLines={1}>
                                    {item.city || 'Member'}
                                </Text>
                            </View>
                            {item.follows_me && (
                                <Text className="text-[9px] text-green-600 mt-1 font-bold uppercase tracking-widest italic">
                                    Follows You
                                </Text>
                            )}
                        </View>
                    </View>

                    {!isMe && (
                        <TouchableOpacity
                            disabled={processingId === item.id}
                            onPress={() => handleToggleFollow(item.id)}
                            className={`px-4 py-2 rounded-xl items-center justify-center border ${btnStyle} shadow-sm min-w-[100px]`}
                        >
                            {processingId === item.id ? (
                                <ActivityIndicator size="small" color={textStyle.includes('white') ? 'white' : '#ea580c'} />
                            ) : (
                                <View className="flex-row items-center">
                                    <Ionicons name={iconName} size={14} color={textStyle.includes('white') ? 'white' : (textStyle.includes('green') ? '#15803d' : '#4b5563')} style={{ marginRight: 4 }} />
                                    <Text className={`font-bold text-xs ${textStyle}`}>
                                        {btnText}
                                    </Text>
                                </View>
                            )}
                        </TouchableOpacity>
                    )}
                </TouchableOpacity>
            </View>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            {/* Custom Header */}
            <View className="px-4 py-4 bg-white border-b border-gray-100 flex-row items-center justify-between">
                <View className="flex-row items-center">
                    <TouchableOpacity
                        onPress={() => navigation.goBack()}
                        className="mr-4 w-10 h-10 bg-gray-50 rounded-full items-center justify-center border border-gray-100"
                    >
                        <Ionicons name="chevron-back" size={24} color="#1f2937" />
                    </TouchableOpacity>
                    <View>
                        <Text className="text-xl font-black text-gray-900">{title}</Text>
                        <Text className="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Connect with Community</Text>
                    </View>
                </View>
                <TouchableOpacity className="w-10 h-10 bg-orange-50 rounded-full items-center justify-center border border-orange-100">
                    <Ionicons name="search-outline" size={20} color="#ea580c" />
                </TouchableOpacity>
            </View>

            {loading && users.length === 0 ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                </View>
            ) : (
                <FlatList
                    data={users}
                    keyExtractor={(item) => item.id.toString()}
                    renderItem={renderItem}
                    ListEmptyComponent={renderEmpty}
                    contentContainerStyle={{ paddingVertical: 12 }}
                    showsVerticalScrollIndicator={false}
                    onEndReached={() => fetchUsersList(true)}
                    onEndReachedThreshold={0.5}
                    ListFooterComponent={() => loadingMore ? (
                        <View className="py-4">
                            <ActivityIndicator size="small" color="#ea580c" />
                        </View>
                    ) : null}
                />
            )}
        </SafeAreaView>
    );
};

export default FollowListScreen;
