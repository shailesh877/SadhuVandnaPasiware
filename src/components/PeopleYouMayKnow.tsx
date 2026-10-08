import React, { useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, Alert, Dimensions } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../services/api';

const { width } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

interface Profile {
    id: string;
    full_name: string;
    photo?: string;
    city?: string;
    proposal_status?: string | null;
}

interface Props {
    profiles: Profile[];
    userId: string;
    navigation: any;
    onStatusChange?: () => void;
}

const PeopleYouMayKnow = ({ profiles, userId, navigation, onStatusChange }: Props) => {
    const [localProfiles, setLocalProfiles] = useState(profiles);

    const handleAction = async (receiverId: string, action: 'follow' | 'unfollow') => {
        if (!userId) return;
        try {
            const payload = {
                action: action,
                current_user_id: userId,
                user_id: receiverId
            };

            const res = await api.post('/api_follow.php', payload);

            if (res.data.ok) {
                setLocalProfiles(prev => prev.map(p => {
                    if (p.id === receiverId) {
                        return { ...p, proposal_status: res.data.status === 'unfollowed' ? null : 'pending' };
                    }
                    return p;
                }));
                if (onStatusChange) onStatusChange();
            } else {
                Alert.alert("Notice", res.data.message || "Action failed");
            }
        } catch (e: any) {
            Alert.alert("Error", "Network error");
        }
    };

    const renderItem = ({ item }: { item: Profile }) => {
        const isRequested = item.proposal_status === 'pending' || item.proposal_status === 'sent' || item.proposal_status === 'requested';

        return (
            <View 
                style={{
                    shadowColor: "#000",
                    shadowOffset: { width: 0, height: 2 },
                    shadowOpacity: 0.05,
                    shadowRadius: 8,
                    elevation: 2
                }}
                className="bg-white border border-gray-50 rounded-3xl p-4 mr-4 w-44 items-center mb-2"
            >
                <TouchableOpacity 
                    activeOpacity={0.8}
                    onPress={() => navigation.navigate('PublicProfile', { userId: item.id })}
                    className="relative"
                >
                    {item.photo ? (
                        <Image
                            source={{ uri: `${PHOTO_URL}${item.photo}` }}
                            className="w-20 h-20 rounded-full bg-gray-50 mb-3 border-2 border-orange-50"
                        />
                    ) : (
                        <View className="w-20 h-20 rounded-full bg-orange-50 items-center justify-center mb-3 border-2 border-orange-100">
                            <Text className="text-orange-600 font-black text-2xl">
                                {item.full_name ? item.full_name[0].toUpperCase() : 'U'}
                            </Text>
                        </View>
                    )}
                    <View className="absolute bottom-2 right-0 bg-white rounded-full p-1 shadow-sm border border-gray-100">
                        <Ionicons name="sparkles" size={12} color="#ea580c" />
                    </View>
                </TouchableOpacity>

                <Text className="font-extrabold text-gray-900 text-sm text-center mb-0.5" numberOfLines={1}>
                    {item.full_name || "Member"}
                </Text>
                <View className="flex-row items-center mb-4">
                    <Ionicons name="location-sharp" size={10} color="#9ca3af" />
                    <Text className="text-gray-400 font-bold text-[9px] ml-0.5 uppercase tracking-tighter" numberOfLines={1}>{item.city || 'Member'}</Text>
                </View>

                {isRequested ? (
                    <TouchableOpacity 
                        activeOpacity={0.7}
                        className="bg-gray-100 w-full py-2.5 rounded-2xl flex-row items-center justify-center border border-gray-200"
                        onPress={() => handleAction(item.id, 'unfollow')}
                    >
                        <Ionicons name="time" size={12} color="#6b7280" style={{ marginRight: 4 }} />
                        <Text className="text-gray-500 font-black text-center text-[10px] uppercase tracking-wider">Requested</Text>
                    </TouchableOpacity>
                ) : (
                    <TouchableOpacity 
                        activeOpacity={0.7}
                        className="bg-orange-600 w-full py-2.5 rounded-2xl flex-row items-center justify-center shadow-sm border border-orange-700"
                        onPress={() => handleAction(item.id, 'follow')}
                    >
                        <Ionicons name="person-add" size={12} color="white" style={{ marginRight: 4 }} />
                        <Text className="text-white font-black text-center text-[10px] uppercase tracking-wider">Follow</Text>
                    </TouchableOpacity>
                )}
                
                {!isRequested && (
                <TouchableOpacity 
                    className="mt-2"
                    onPress={() => setLocalProfiles(prev => prev.filter(p => p.id !== item.id))}
                >
                    <Text className="text-gray-300 font-bold text-center text-[9px] uppercase tracking-widest">Not Interested</Text>
                </TouchableOpacity>
                )}
            </View>
        );
    };

    if (!localProfiles || localProfiles.length === 0) return null;

    return (
        <View className="bg-white my-3 py-6 px-4 border-y border-gray-50">
            <View className="flex-row justify-between items-end mb-5 px-1">
                <View>
                    <Text className="text-[10px] text-orange-500 font-black uppercase tracking-[3px] mb-1">Discovery</Text>
                    <Text className="font-black text-gray-900 text-xl tracking-tight leading-tight">People You May Know</Text>
                </View>
                <TouchableOpacity 
                    activeOpacity={0.6}
                    onPress={() => navigation.navigate('FollowList', { userId: userId, action: 'fetch_all_members', title: 'Connect with People' })}
                    className="bg-orange-50 px-3 py-1.5 rounded-full border border-orange-100"
                >
                    <Text className="text-orange-600 font-black text-[10px] uppercase tracking-wider">View All</Text>
                </TouchableOpacity>
            </View>

            <FlatList
                data={localProfiles}
                renderItem={renderItem}
                keyExtractor={item => item.id.toString()}
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={{ paddingRight: 20, paddingLeft: 4 }}
            />
        </View>
    );
};

export default PeopleYouMayKnow;
