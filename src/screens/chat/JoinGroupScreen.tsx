import React, { useEffect, useState } from 'react';
import { View, Text, ActivityIndicator, TouchableOpacity, Alert, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const JoinGroupScreen = ({ route, navigation }: any) => {
    const { inviteCode } = route.params || {};
    const [loading, setLoading] = useState(true);
    const [joining, setJoining] = useState(false);
    const [group, setGroup] = useState<any>(null);
    const [userId, setUserId] = useState<string | null>(null);

    useEffect(() => {
        if (!inviteCode) {
            Alert.alert("Error", "Invalid invite link");
            navigation.navigate('MainTabs');
            return;
        }

        const init = async () => {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                setUserId(JSON.parse(u).id);
            } else {
                navigation.navigate('Auth');
                return;
            }
            fetchGroupInfo();
        };
        init();
    }, [inviteCode]);

    const fetchGroupInfo = async () => {
        try {
            // We need an API to fetch group info by invite code without joining yet
            const res = await api.get(`/get_group_by_invite.php?invite_code=${inviteCode}`);
            if (res.data.status === 'success') {
                setGroup(res.data.data);
            } else {
                Alert.alert("Error", res.data.message || "Group not found");
                navigation.navigate('MainTabs');
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
            navigation.navigate('MainTabs');
        } finally {
            setLoading(false);
        }
    };

    const handleJoin = async () => {
        if (!userId || !group) return;
        setJoining(true);
        try {
            const formData = new FormData();
            formData.append('invite_code', inviteCode);
            formData.append('user_id', userId.toString());

            const res = await api.post('/join_group.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", "You have joined the group!");
                navigation.replace('Chat', { 
                    receiver: { 
                        partner_id: group.id, 
                        full_name: group.name, 
                        profile_photo: group.photo 
                    },
                    platform: group.platform || 'community',
                    isGroup: true 
                });
            } else {
                if (res?.data?.message && res.data.message.includes("already a member")) {
                     navigation.replace('Chat', { 
                        receiver: { 
                            partner_id: group.id, 
                            full_name: group.name, 
                            profile_photo: group.photo 
                        },
                        platform: group.platform || 'community',
                        isGroup: true 
                    });
                } else {
                    Alert.alert("Error", res?.data?.message || "Something went wrong");
                }
            }
        } catch (error) {
            Alert.alert("Error", "Failed to join group");
        } finally {
            setJoining(false);
        }
    };

    if (loading) {
        return (
            <View className="flex-1 bg-white justify-center items-center">
                <ActivityIndicator size="large" color="#ea580c" />
                <Text className="mt-4 text-gray-500 font-medium">Fetching group details...</Text>
            </View>
        );
    }

    if (!group) return null;

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="p-4 flex-row items-center">
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Ionicons name="arrow-back" size={24} color="#ea580c" />
                </TouchableOpacity>
            </View>

            <View className="flex-1 px-8 pt-10 items-center">
                <View className="w-40 h-40 rounded-full bg-orange-50 border-4 border-orange-100 items-center justify-center overflow-hidden mb-8 shadow-xl">
                    {group.photo ? (
                        <Image 
                            source={{ uri: `${PHOTO_URL}${encodeURIComponent(group.photo)}` }} 
                            className="w-full h-full"
                        />
                    ) : (
                        <Ionicons name="people" size={80} color="#ea580c" />
                    )}
                </View>

                <Text className="text-3xl font-black text-gray-900 text-center mb-2">{group.name}</Text>
                
                <View className="bg-orange-50 px-4 py-1.5 rounded-full mb-6">
                    <Text className="text-orange-600 font-bold text-sm uppercase tracking-widest">Group Invite</Text>
                </View>

                {group.description ? (
                    <View className="bg-gray-50 p-6 rounded-3xl border border-gray-100 w-full mb-10">
                        <Text className="text-gray-400 text-xs font-bold uppercase mb-2 tracking-widest">About this group</Text>
                        <Text className="text-gray-700 text-base leading-6 italic">"{group.description}"</Text>
                    </View>
                ) : (
                    <Text className="text-gray-500 text-center mb-10 text-base">You've been invited to join this group.</Text>
                )}

                <TouchableOpacity 
                    onPress={handleJoin}
                    disabled={joining}
                    className="bg-orange-600 w-full py-5 rounded-2xl flex-row justify-center items-center shadow-lg active:bg-orange-700"
                >
                    {joining ? (
                        <ActivityIndicator color="white" />
                    ) : (
                        <>
                            <Ionicons name="enter-outline" size={24} color="white" className="mr-3" />
                            <Text className="text-white font-black text-xl ml-2">Join Group</Text>
                        </>
                    )}
                </TouchableOpacity>

                <TouchableOpacity 
                    onPress={() => navigation.goBack()}
                    className="mt-6"
                >
                    <Text className="text-gray-400 font-bold">Not now</Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
};

export default JoinGroupScreen;
