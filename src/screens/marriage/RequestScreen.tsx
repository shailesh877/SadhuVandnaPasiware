import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Alert, useWindowDimensions } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { TabView, SceneMap, TabBar } from 'react-native-tab-view';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const RequestScreen = ({ navigation }: any) => {
    const layout = useWindowDimensions();

    const [index, setIndex] = useState(0);
    const [routes] = useState([
        { key: 'pending', title: 'Pending' },
        { key: 'sent', title: 'Sent' },
        { key: 'connected', title: 'Friends' },
    ]);

    const [pendingRequests, setPendingRequests] = useState<any[]>([]);
    const [sentRequests, setSentRequests] = useState<any[]>([]);
    const [connectedFriends, setConnectedFriends] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        const unsubscribe = navigation.addListener('focus', () => {
            fetchData();
        });
        fetchData(); // Initial load
        return unsubscribe;
    }, [navigation]);

    const fetchData = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const parsedUser = JSON.parse(u);
                setUser(parsedUser);

                const res = await api.post('api_connect.php', {
                    action: 'fetch_my_requests',
                    user_id: parsedUser.id
                });

                if (res.data.status === 'success') {
                    setPendingRequests(res.data.received);
                    setSentRequests(res.data.sent);
                    setConnectedFriends(res.data.connected);
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleAction = async (id: string, action: 'accept' | 'reject' | 'remove') => {
        // action: accept/reject/remove
        // unified API uses 'manage_request' for accept/reject (by receiver)
        // For remove, it might be same or specialized.
        // My API: 'manage_request' uses 'sender_id' (the other person).
        // Wait, manage_request expects 'sender_id' (who sent the request). 
        // In pending list, 'item.sender_profile_id' is the profile ID of sender.

        // For 'remove' (unfriend), usually we delete the row. My API didn't explicitly have 'remove_friend' but 'reject' deletes row.
        // Let's use 'reject' logic for remove if 'manage_request' allows it or create specific if needed.
        // Actually, 'manage_request' deletes if sub_action is reject.
        // But manage_request requires 'sender_id' (profile ID of other person). 
        // In 'connected' list, if I am receiver, sender_id is other. If I am sender, receiver_id is other.
        // This is complex. Let's simplify API to take 'proposal_id' directly?
        // Ah, api_matrimony.php uses `WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'`.
        // This only works if *I* am the receiver and I am rejecting/accepting.
        // For REMOVING a friend, I could be either sender or receiver.

        // Let's quickly ADD a robust 'delete_proposal' to api_matrimony.php that takes proposal_id.
        // It's safer.

        // Proceeding with assumption I'll add 'delete_proposal' action to API.

        // try { // Removed unclosed try
        const formData = new FormData();
        formData.append('action', 'delete_proposal');
        formData.append('proposal_id', id); // We need to pass the ID of the proposal row
        formData.append('sub_action', action === 'accept' ? 'accept' : 'delete');

        // Wait, for ACCEPT, I need 'manage_request'.
        // For REJECT/REMOVE, I can use 'delete_proposal'.

        // id here is effectively the PROPOSAL ID according to previous code,
        // BUT for api_connect.php we need SENDER_ID or OTHER_ID (Profile IDs).
        // Let's find the item to get IDs.

        let item: any;
        if (action === 'remove') item = connectedFriends.find(p => p.id === id);
        else item = pendingRequests.find(p => p.id === id);

        if (!item) return;

        const payload = {
            user_id: user.id,
            action: action === 'accept' ? 'accept_request' : (action === 'reject' ? 'reject_request' : 'remove_connection'),
            sender_id: item.sender_profile_id || item.sender_id,
            receiver_id: item.receiver_profile_id || item.receiver_id,
            proposal_id: item.proposal_id || item.id,
            other_user_id: item.user_id
        };

        try {
            await api.post('api_connect.php', payload);
            Alert.alert("Success", "Action Completed");
            fetchData();
        } catch (error) {
            Alert.alert("Error", "Action Failed");
        }
    };

    const handleCancelRequest = async (receiverId: string) => {
        Alert.alert("Cancel Request", "Are you sure you want to cancel this request?", [
            { text: "No", style: "cancel" },
            {
                text: "Yes, Cancel", style: "destructive", onPress: async () => {
                    try {
                        await api.post('api_connect.php', {
                            action: 'cancel_request',
                            user_id: user.id,
                            receiver_id: receiverId
                        });
                        fetchData();
                        Alert.alert("Success", "Request Cancelled");
                    } catch (error) {
                        Alert.alert("Error", "Failed to cancel request");
                    }
                }
            }
        ]);
    };

    const renderPendingItem = ({ item }: { item: any }) => (
        <View className="flex-row items-center p-4 bg-white border-b border-gray-100">
            <Image
                source={{ uri: item.photo ? `${PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/100' }}
                className="w-16 h-16 rounded-full bg-gray-200 mr-4"
            />
            <View className="flex-1">
                <Text className="text-lg font-bold text-gray-800">{item.full_name}</Text>
                <Text className="text-gray-500 text-sm">{item.city} • {item.age} yrs</Text>

                <View className="flex-row gap-2 mt-2">
                    <TouchableOpacity
                        className="bg-green-600 px-4 py-1.5 rounded-lg flex-1 items-center"
                        onPress={() => handleAction(item.id, 'accept')}
                    >
                        <Text className="text-white font-bold text-xs">Accept</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                        className="bg-red-50 text-red-600 border border-red-200 px-4 py-1.5 rounded-lg flex-1 items-center"
                        onPress={() => handleAction(item.id, 'reject')}
                    >
                        <Text className="text-red-600 font-bold text-xs">Reject</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </View>
    );

    const renderConnectedItem = ({ item }: { item: any }) => (
        <View className="flex-row items-center p-4 bg-white border-b border-gray-100">
            <Image
                source={{ uri: item.photo ? `${PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/100' }}
                className="w-16 h-16 rounded-full bg-gray-200 mr-4"
            />
            <View className="flex-1">
                <Text className="text-lg font-bold text-gray-800">{item.full_name}</Text>
                <Text className="text-gray-500 text-sm">{item.city} • {item.age} yrs</Text>

                <View className="flex-row gap-2 mt-2">
                    <TouchableOpacity
                        className="bg-blue-600 px-4 py-1.5 rounded-lg flex-1 items-center flex-row justify-center space-x-1"
                        onPress={() => navigation.navigate('Chat', {
                            receiver: {
                                id: item.friend_profile_id,
                                full_name: item.full_name,
                                photo: item.photo,
                                user_id: item.user_id
                            },
                            platform: 'marriage'
                        })}
                    >
                        <Ionicons name="chatbubble-ellipses" size={16} color="white" />
                        <Text className="text-white font-bold text-xs">Chat</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                        className="bg-gray-100 border border-gray-300 px-4 py-1.5 rounded-lg flex-1 items-center"
                        onPress={() => Alert.alert("Remove Friend", "Are you sure?", [
                            { text: "Cancel", style: "cancel" },
                            { text: "Remove", style: "destructive", onPress: () => handleAction(item.id, 'remove') }
                        ])}
                    >
                        <Text className="text-gray-600 font-bold text-xs">Remove</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </View>
    );

    const renderSentItem = ({ item }: { item: any }) => (
        <View className="flex-row items-center p-4 bg-white border-b border-gray-100">
            <Image
                source={{ uri: item.photo ? `${PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/100' }}
                className="w-16 h-16 rounded-full bg-gray-200 mr-4"
            />
            <View className="flex-1">
                <Text className="text-lg font-bold text-gray-800">{item.full_name}</Text>
                <Text className="text-gray-500 text-sm">{item.city} • {item.age} yrs</Text>
                <TouchableOpacity
                    className="mt-2 bg-gray-100 border border-gray-300 px-4 py-1.5 rounded-lg items-center self-start"
                    onPress={() => handleCancelRequest(item.receiver_id)}
                >
                    <Text className="text-gray-600 font-bold text-xs">Cancel Request</Text>
                </TouchableOpacity>
            </View>
        </View>
    );

    const PendingRoute = () => (
        <FlatList
            data={pendingRequests}
            renderItem={renderPendingItem}
            keyExtractor={(item, index) => item.id ? `${item.id}-${index}` : index.toString()}
            ListEmptyComponent={
                <View className="items-center mt-20 p-4">
                    <Text className="text-4xl mb-4">🔕</Text>
                    <Text className="text-gray-500 text-center text-lg">No pending requests.</Text>
                </View>
            }
        />
    );

    const SentRoute = () => (
        <FlatList
            data={sentRequests}
            renderItem={renderSentItem}
            keyExtractor={(item, index) => item.id ? `${item.id}-${index}` : index.toString()}
            ListEmptyComponent={
                <View className="items-center mt-20 p-4">
                    <Text className="text-4xl mb-4">🕊️</Text>
                    <Text className="text-gray-500 text-center text-lg">No sent requests.</Text>
                </View>
            }
        />
    );

    const ConnectedRoute = () => (
        <FlatList
            data={connectedFriends}
            renderItem={renderConnectedItem}
            keyExtractor={(item, index) => item.id ? `${item.id}-${index}` : index.toString()}
            ListEmptyComponent={
                <View className="items-center mt-20 p-4">
                    <Text className="text-4xl mb-4">👥</Text>
                    <Text className="text-gray-500 text-center text-lg">No connections yet.</Text>
                </View>
            }
        />
    );

    const renderScene = SceneMap({
        pending: PendingRoute,
        sent: SentRoute,
        connected: ConnectedRoute,
    });

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white">
            <View className="p-4 border-b border-orange-100 bg-orange-50 flex-row items-center">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="#ea580c" />
                </TouchableOpacity>
                <Text className="text-2xl font-bold text-orange-600">Requests & Friends</Text>
            </View>

            <TabView
                navigationState={{ index, routes }}
                renderScene={renderScene}
                onIndexChange={setIndex}
                initialLayout={{ width: layout.width }}
                renderTabBar={(props: any) => (
                    <TabBar
                        {...props}
                        indicatorStyle={{ backgroundColor: '#ea580c' }}
                        style={{ backgroundColor: 'white' }}
                        activeColor="#ea580c"
                        inactiveColor="gray"
                        renderLabel={({ route, focused, color }: any) => (
                            <Text style={{ color, margin: 8, fontWeight: 'bold' }}>
                                {route.title}
                            </Text>
                        )}
                    />
                )}
            />
        </SafeAreaView>
    );
};

export default RequestScreen;

