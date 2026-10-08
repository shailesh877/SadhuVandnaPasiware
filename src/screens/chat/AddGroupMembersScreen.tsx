import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, ActivityIndicator, Alert, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { API_BASE_URL } from '../../services/api';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const AddGroupMembersScreen = ({ navigation, route }: any) => {
    const { group, currentMembers } = route.params;
    const [allUsers, setAllUsers] = useState<any[]>([]);
    const [filteredUsers, setFilteredUsers] = useState<any[]>([]);
    const [selectedFriends, setSelectedFriends] = useState<number[]>([]);
    const [loading, setLoading] = useState(false);
    const [fetching, setFetching] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        fetchAllUsers();
    }, []);

    const fetchAllUsers = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                const res = await api.get(`/get_all_users.php?user_id=${user.id}`);
                if (res.data.status === 'success') {
                    // Filter out already added members
                    const currentMemberIds = currentMembers.map((m: any) => parseInt(m.user_id));
                    const potentialMembers = res.data.data.filter((u: any) => 
                        !currentMemberIds.includes(parseInt(u.partner_id))
                    );
                    setAllUsers(potentialMembers);
                    setFilteredUsers(potentialMembers);
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            setFetching(false);
        }
    };

    const handleSearch = (query: string) => {
        setSearchQuery(query);
        if (!query.trim()) {
            setFilteredUsers(allUsers);
            return;
        }
        const filtered = allUsers.filter(u => 
            u.full_name.toLowerCase().includes(query.toLowerCase())
        );
        setFilteredUsers(filtered);
    };

    const toggleFriendSelection = (id: number) => {
        setSelectedFriends(prev => 
            prev.includes(id) ? prev.filter(fid => fid !== id) : [...prev, id]
        );
    };

    const addMembers = async () => {
        if (selectedFriends.length === 0) {
            Alert.alert('Error', 'Please select at least one member to add');
            return;
        }

        setLoading(true);
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                const formData = new FormData();
                formData.append('group_id', group.id || group.partner_id);
                formData.append('user_id', user.id.toString());
                formData.append('member_ids', selectedFriends.join(','));

                const res = await api.post('/add_group_members.php', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (res.data.status === 'success') {
                    Alert.alert('Success', 'Members added successfully');
                    navigation.goBack();
                } else {
                    Alert.alert('Error', res.data.message);
                }
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'Failed to add members');
        } finally {
            setLoading(false);
        }
    };

    const renderFriend = ({ item }: { item: any }) => {
        const isSelected = selectedFriends.includes(item.partner_id);
        return (
            <TouchableOpacity 
                className={`flex-row items-center p-3 mb-2 rounded-lg border ${isSelected ? 'border-orange-500 bg-orange-50' : 'border-gray-200 bg-white'}`}
                onPress={() => toggleFriendSelection(item.partner_id)}
            >
                <View className="relative mr-3">
                    <Image
                        source={{ uri: item.profile_photo ? `${PHOTO_URL}${encodeURIComponent(item.profile_photo)}` : 'https://via.placeholder.com/100' }}
                        className="w-10 h-10 rounded-full bg-gray-200"
                    />
                    <View 
                        className={`absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white ${item.is_online ? 'bg-green-500' : 'bg-red-500'}`} 
                    />
                </View>
                <Text className="flex-1 text-base font-medium">{item.full_name}</Text>
                {isSelected && <Ionicons name="checkmark-circle" size={24} color="#ea580c" />}
            </TouchableOpacity>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="flex-row items-center p-4 border-b border-gray-100 bg-orange-50">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-4">
                    <Ionicons name="arrow-back" size={24} color="#ea580c" />
                </TouchableOpacity>
                <Text className="text-xl font-bold flex-1 text-orange-600">Add Members</Text>
                <TouchableOpacity onPress={addMembers} disabled={loading || selectedFriends.length === 0}>
                    {loading ? (
                        <ActivityIndicator color="#ea580c" />
                    ) : (
                        <Text className={`font-bold text-base ${selectedFriends.length > 0 ? 'text-orange-600' : 'text-orange-300'}`}>Add</Text>
                    )}
                </TouchableOpacity>
            </View>

            <View className="flex-1 p-4">
                <View className="mb-4">
                    <Text className="text-sm text-gray-500 font-bold mb-2 uppercase">Search Members</Text>
                    <View className="flex-row items-center bg-gray-50 rounded-lg px-3 py-1 border border-orange-100">
                        <Ionicons name="search" size={18} color="#ea580c" />
                        <TextInput
                            className="flex-1 ml-2 text-base p-1"
                            placeholder="Type name to search..."
                            value={searchQuery}
                            onChangeText={handleSearch}
                        />
                        {searchQuery.length > 0 && (
                            <TouchableOpacity onPress={() => handleSearch('')}>
                                <Ionicons name="close-circle" size={18} color="#9ca3af" />
                            </TouchableOpacity>
                        )}
                    </View>
                </View>

                <Text className="text-sm text-gray-500 font-bold mb-2 uppercase">Select Members ({selectedFriends.length} selected)</Text>

                {fetching ? (
                    <ActivityIndicator color="#ea580c" style={{ marginTop: 20 }} />
                ) : (
                    <FlatList
                        data={filteredUsers}
                        renderItem={renderFriend}
                        keyExtractor={(item, index) => item.partner_id ? `user_${item.partner_id}` : index.toString()}
                        contentContainerStyle={{ paddingBottom: 100 }}
                        ListEmptyComponent={<Text className="text-center text-gray-500 mt-4">No users found.</Text>}
                    />
                )}
            </View>
        </SafeAreaView>
    );
};

export default AddGroupMembersScreen;
