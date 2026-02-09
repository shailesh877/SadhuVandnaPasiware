import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, TextInput, Alert, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const MarriageScreen = ({ navigation }: any) => {
    const [profiles, setProfiles] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [myProfileId, setMyProfileId] = useState<number>(0);
    const [requestCount, setRequestCount] = useState<number>(0);

    // Filters
    const [search, setSearch] = useState('');
    const [filterVisible, setFilterVisible] = useState(true);
    const [gender, setGender] = useState('');
    const [ageGroup, setAgeGroup] = useState('');
    const [city, setCity] = useState('');
    const [education, setEducation] = useState('');
    const [minAge, setMinAge] = useState('');
    const [maxAge, setMaxAge] = useState('');

    const [userId, setUserId] = useState<string | null>(null);

    useEffect(() => {
        loadUser();
    }, []);

    useFocusEffect(
        useCallback(() => {
            if (userId) {
                fetchProfiles();
            }
        }, [userId, gender, minAge, maxAge, city, education, search])
    );

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) {
            setUserId(JSON.parse(u).id);
        }
    };

    const fetchProfiles = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.append('user_id', userId || '0');
            if (search) params.append('city', search); // Search box acts as City search on web? Web has separate City input.
            // Let's us search as city or generic search.
            if (gender) params.append('gender', gender);
            if (minAge && maxAge) params.append('age', `${minAge}-${maxAge}`);
            if (city) params.append('city', city);
            if (education) params.append('education', education);

            const res = await api.get(`/get_matrimony_profiles.php?${params.toString()}`);
            if (res.data.status === 'success') {
                setProfiles(res.data.data);
                setMyProfileId(res.data.my_profile_id);
                setRequestCount(res.data.request_count);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleSendRequest = async (receiverId: string) => {
        if (!userId) {
            Alert.alert("Notice", "Please login.");
            return;
        }
        if (!myProfileId) {
            Alert.alert("Profile Required", "Please create your marriage profile first.", [
                { text: "Cancel" },
                { text: "Create Now", onPress: () => navigation.navigate('CreateMarriageProfile') }
            ]);
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'send_request');
            formData.append('user_id', userId);
            formData.append('receiver_id', receiverId);

            // using api_connect.php for unified connection handling
            const res = await api.post('/api_connect.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("Success", "Proposal Sent Successfully");
                fetchProfiles(); // Refresh status
            } else {
                Alert.alert("Notice", res.data.message || "Failed to send request");
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
        }
    };

    const renderProfile = ({ item }: { item: any }) => {
        const isSender = item.is_sender;
        const status = item.proposal_status;

        return (
            <TouchableOpacity
                activeOpacity={0.9}
                onPress={() => navigation.navigate('MarriageDetail', { profile: item })}
                className="flex-1 bg-white m-1.5 rounded-2xl shadow-md border border-gray-100 overflow-hidden"
                style={{ elevation: 4 }}
            >
                <View className="relative">
                    <Image
                        source={{ uri: item.photo ? `${PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/150' }}
                        className="w-full h-40 object-cover bg-gray-200"
                    />
                    <View className="absolute bottom-0 left-0 right-0 bg-black/40 p-2">
                        <Text className="text-white font-bold text-sm" numberOfLines={1}>{item.full_name}</Text>
                        <Text className="text-white/90 text-xs">{item.age} yrs, {item.city}</Text>
                    </View>
                    <View className="absolute top-2 right-2 bg-white/90 px-2 py-0.5 rounded-full">
                        <Text className="text-orange-600 text-[10px] font-bold uppercase">{item.status}</Text>
                    </View>
                </View>

                <View className="p-3 bg-white">
                    <Text className="text-gray-500 text-xs mb-2" numberOfLines={1}>🎓 {item.education || 'Not specified'}</Text>

                    {/* Action Buttons */}
                    <View className="flex-row gap-2 mt-1">
                        {(!status || status === 'rejected') && (
                            <TouchableOpacity
                                className="flex-1 bg-orange-50 py-2 rounded-xl items-center border border-orange-100"
                                onPress={() => navigation.navigate('MarriageDetail', { profile: item })}
                            >
                                <Text className="text-orange-600 font-bold text-xs uppercase">View</Text>
                            </TouchableOpacity>
                        )}

                        {status === 'pending' && (
                            <TouchableOpacity
                                disabled={true}
                                className="flex-1 bg-orange-100 border border-orange-200 py-2 rounded-xl items-center"
                            >
                                <Text className="text-orange-600 font-bold text-xs">Requested</Text>
                            </TouchableOpacity>
                        )}

                        {(status === 'accepted' || status === 'friend') && (
                            <TouchableOpacity
                                className="flex-1 bg-green-600 py-2 rounded-xl items-center shadow-sm"
                                onPress={() => navigation.navigate('Chat', { receiver: item })}
                            >
                                <Text className="text-white font-bold text-xs">Message</Text>
                            </TouchableOpacity>
                        )}
                    </View>
                </View>
            </TouchableOpacity>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <View className="px-4 py-3 bg-white border-b border-gray-100 shadow-sm z-10">
                <View className="flex-row justify-between items-center mb-4">
                    <Text className="text-2xl font-extrabold text-gray-800 tracking-tight">Matrimony</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('CreateMarriageProfile', { profile: myProfileId ? { id: myProfileId } : null })} className="bg-orange-50 border border-orange-100 px-3 py-1.5 rounded-full flex-row items-center gap-1 shadow-sm">
                        <Ionicons name={myProfileId ? "create-outline" : "add-circle-outline"} size={16} color="#ea580c" />
                        <Text className="text-orange-600 text-xs font-bold">{myProfileId ? 'My Profile' : 'Create Profile'}</Text>
                    </TouchableOpacity>
                </View>

                {/* Header Actions */}
                <View className="flex-row justify-between gap-3 mb-4">
                    {/* Removed Sent Button as requested */}

                    <TouchableOpacity className="flex-1 items-center bg-white p-2.5 rounded-2xl border border-gray-100 shadow-sm" onPress={() => navigation.navigate('Requests')}>
                        <View className="bg-pink-50 p-2 rounded-full mb-1 relative">
                            <Ionicons name="heart" size={18} color="#db2777" />
                            {requestCount > 0 && <View className="absolute -top-1 -right-1 bg-red-500 w-5 h-5 rounded-full items-center justify-center border-2 border-white"><Text className="text-white text-[9px] font-bold">{requestCount}</Text></View>}
                        </View>
                        <Text className="text-xs font-semibold text-gray-600">Requests</Text>
                    </TouchableOpacity>

                    <TouchableOpacity className="flex-1 items-center bg-white p-2.5 rounded-2xl border border-gray-100 shadow-sm" onPress={() => navigation.navigate('Connected')}>
                        <View className="bg-green-50 p-2 rounded-full mb-1"><Ionicons name="people" size={18} color="#16a34a" /></View>
                        <Text className="text-xs font-semibold text-gray-600">Matches</Text>
                    </TouchableOpacity>
                </View>

                {/* Filters Toggle */}
                <TouchableOpacity onPress={() => setFilterVisible(!filterVisible)} className="flex-row justify-between items-center bg-white p-3 mx-4 rounded-xl border border-gray-100 shadow-sm mb-2">
                    <View className="flex-row items-center gap-2">
                        <View className="bg-orange-50 p-1.5 rounded-lg"><Ionicons name="options" size={18} color="#ea580c" /></View>
                        <Text className="text-gray-800 font-bold text-sm">Filter Matches</Text>
                    </View>
                    <Ionicons name={filterVisible ? "chevron-up" : "chevron-down"} size={20} color="gray" />
                </TouchableOpacity>

                {/* Filters View */}
                {filterVisible && (
                    <View className="mx-4 mb-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                        <Text className="text-xs font-bold text-gray-500 uppercase mb-3 tracking-wider">Gender</Text>
                        <View className="flex-row gap-3 mb-4">
                            <TouchableOpacity onPress={() => setGender('Male')} className={`flex-1 py-2.5 rounded-xl border ${gender === 'Male' ? 'bg-orange-600 border-orange-600' : 'bg-gray-50 border-gray-100'}`}>
                                <Text className={`text-center text-sm font-bold ${gender === 'Male' ? 'text-white' : 'text-gray-500'}`}>Groom (Male)</Text>
                            </TouchableOpacity>
                            <TouchableOpacity onPress={() => setGender('Female')} className={`flex-1 py-2.5 rounded-xl border ${gender === 'Female' ? 'bg-pink-600 border-pink-600' : 'bg-gray-50 border-gray-100'}`}>
                                <Text className={`text-center text-sm font-bold ${gender === 'Female' ? 'text-white' : 'text-gray-500'}`}>Bride (Female)</Text>
                            </TouchableOpacity>
                        </View>

                        <Text className="text-xs font-bold text-gray-500 uppercase mb-3 tracking-wider">Age Range</Text>
                        <View className="flex-row gap-3 mb-4">
                            <View className="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex-row items-center px-3">
                                <TextInput placeholder="Min Age" value={minAge} onChangeText={setMinAge} keyboardType="numeric" className="flex-1 py-2.5 px-2 text-gray-700 text-sm" placeholderTextColor="#9ca3af" />
                            </View>
                            <View className="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex-row items-center px-3">
                                <TextInput placeholder="Max Age" value={maxAge} onChangeText={setMaxAge} keyboardType="numeric" className="flex-1 py-2.5 px-2 text-gray-700 text-sm" placeholderTextColor="#9ca3af" />
                            </View>
                        </View>

                        <Text className="text-xs font-bold text-gray-500 uppercase mb-3 tracking-wider">Location & Education</Text>
                        <View className="flex-row gap-3 mb-4">
                            <View className="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex-row items-center px-3">
                                <Ionicons name="location-outline" size={16} color="gray" />
                                <TextInput placeholder="City..." value={city} onChangeText={setCity} className="flex-1 py-2.5 px-2 text-gray-700 text-sm" placeholderTextColor="#9ca3af" />
                            </View>
                            <View className="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex-row items-center px-3">
                                <Ionicons name="school-outline" size={16} color="gray" />
                                <TextInput placeholder="Degree..." value={education} onChangeText={setEducation} className="flex-1 py-2.5 px-2 text-gray-700 text-sm" placeholderTextColor="#9ca3af" />
                            </View>
                        </View>

                        <View className="flex-row justify-between items-center pt-2 border-t border-gray-50">
                            <TouchableOpacity onPress={() => { setGender(''); setCity(''); setEducation(''); setMinAge(''); setMaxAge(''); }} className="px-3 py-1">
                                <Text className="text-gray-400 text-xs font-bold">Reset All</Text>
                            </TouchableOpacity>
                            <TouchableOpacity onPress={() => { setFilterVisible(false); fetchProfiles(); }} className="bg-gray-800 px-6 py-2 rounded-lg">
                                <Text className="text-white text-xs font-bold">Apply Filters</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                )}
            </View>

            {loading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                    <Text className="text-gray-400 text-xs mt-2">Finding matches...</Text>
                </View>
            ) : (
                <FlatList
                    data={profiles}
                    renderItem={renderProfile}
                    keyExtractor={item => item.id?.toString()}
                    numColumns={2}
                    contentContainerStyle={{ padding: 12, paddingBottom: 80 }}
                    className="bg-gray-50"
                    ListEmptyComponent={
                        <View className="items-center justify-center py-20">
                            <Image source={{ uri: 'https://cdn-icons-png.flaticon.com/512/7486/7486744.png' }} className="w-20 h-20 opacity-30 mb-4" />
                            <Text className="text-gray-500 font-bold text-lg">No Profiles Found</Text>
                            <Text className="text-gray-400 text-xs mt-1 text-center w-64">Try changing filters or check back later for new matches.</Text>
                            <TouchableOpacity onPress={fetchProfiles} className="mt-6 bg-orange-100 px-6 py-2 rounded-full">
                                <Text className="text-orange-600 font-bold text-xs">Refresh</Text>
                            </TouchableOpacity>
                        </View>
                    }
                />
            )}
        </SafeAreaView>
    );
};

export default MarriageScreen;
