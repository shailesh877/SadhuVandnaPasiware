import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Alert, useWindowDimensions } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useIsFocused, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { TabView, SceneMap, TabBar } from 'react-native-tab-view';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const RequestScreen = () => {
    const layout = useWindowDimensions();
    const navigation = useNavigation<any>();
    const isFocused = useIsFocused();

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
        if (isFocused) {
            fetchData();
        }
    }, [isFocused]);

    const fetchData = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const parsedUser = JSON.parse(u);
                setUser(parsedUser);

                const formData = new FormData();
                formData.append('action', 'fetch_my_requests');
                formData.append('user_id', parsedUser.id);

                const res = await api.post('/api_matrimony.php', formData);

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

        // Determine params
        const form = new FormData();
        form.append('user_id', user.id);

        if (action === 'accept') {
            form.append('action', 'accept_request');
            form.append('sender_id', item.sender_profile_id || item.sender_id); // In pending list, sender_id is the sender's profile id.
        } else if (action === 'reject') {
            form.append('action', 'reject_request');
            form.append('sender_id', item.sender_profile_id || item.sender_id);
        } else if (action === 'remove') {
            form.append('action', 'remove_connection');
            // For connected, we need the OTHER profile id.
            // If I am sender, other is receiver. If I am receiver, other is sender.
            // Connected list has `user_id` of the OTHER person effectively? 
            // Previous code: item.user_id was passed to Chat.
            // But we need PROFILE ID.
            // In API `fetch_my_requests`:
            // Connected query: `p.*, mp.full_name...`
            // `mp` is the OTHER person's profile. `mp.id` is OTHER PROFILE ID.
            // In `fetch_my_requests` (Api/api_matrimony.php), the SELECT includes `id` from `tbl_proposals` usually?
            // Wait, standard SQL `SELECT *` might clash.
            // But usually we join.
            // Let's blindly use `item.user_id` if logic fails, but better to use `item.sender_id` or `item.receiver_id`.
            // ACTUALLY: In `api_connect.php`, `remove_connection` takes `other_id`.
            // The connected list items come from `tbl_marriage_profiles` of the friend.
            // `mp.id` is the Friend's Profile ID.
            // But `fetch_my_requests` SELECTs `p.*`? It might overlap.
            // Ideally `mp.id` should be aliased.
            // Let's assume `item.sender_profile_id` or similar exists if I aliased it,
            // OR checks: `user_id` in item is User ID.
            // We need Profile ID.
            // Let's pass `item.id`? No `item.id` might be proposal ID.

            // Re-reading `fetch_my_requests` in `api_matrimony.php`:
            // `SELECT p.*, mp.full_name ...`
            // If `mp` is the friend's profile table, then `mp.id` is friend's profile ID.
            // But `p.id` is proposal ID.
            // Conflict on `id`.
            // Usually `mysqli_fetch_assoc` overwrites if duplicate keys.
            // `mp.id` usually comes AFTER `p.id` in join if `mp` is second?
            // `SELECT p.*, mp.full_name ...` -> `mp.id` is NOT selected unless `mp.*` or specific.
            // `fetch_my_requests` does NOT select `mp.id`.
            // It selects `mp.user_id`. (Line 183/187 of `api_matrimony.php`).
            // So we have Friend's User ID.
            // `api_connect.php` expects Profile IDs for accept/reject.
            // But for `remove`, can we modify `api_connect.php` to accept User ID? 
            // No, `getProfileId` is for ME.
            // Wait, for `remove`, I can look up profile ID from User ID?
            // Yes.

            // Let's use `item.user_id` (Friend's User ID) and update `api_connect.php` to handle it?
            // OR update `RequestScreen` to pass `item.sender_id` / `item.receiver_id` carefully.

            // Simplify: `api_connect.php` `remove_connection` can take `other_profile_id`.
            // In connected list, `item.sender_id` and `item.receiver_id` are available (from p.*).
            // One of them is ME. One is Friend.
            // `user.id` is MY User ID. My Profile ID is `myProfileId` (which I don't have in RequestScreen state directly, but can infer).

            // Heuristic: Pass both `item.sender_id` and `item.receiver_id` to `api_connect`.
            // The API can check which one is NOT me.
            // Too complex.

            // Let's just use `item.sender_profile_id` if available.
            // `api_matrimony.php` line 176: `mp.id as sender_profile_id` (Received).
            // For Connected loop (183/187), it does NOT alias `mp.id`.
            // Warning: `connected` items might not have the Friend's Profile ID easily accessible if `id` is overwritten.

            // However, we have `item.user_id` (Friend's User ID).
            // I will update `api_connect.php` to support `other_user_id` for "remove".
            // Implementation detail for next step.

            // For now, I'll pass reference `action` and `id` (proposal id) to `delete_proposal` in `api_matrimony` which was working?
            // User asked to "api bnao". I should use the new one.
            // I'll update `api_connect.php` to support `other_user_id` for remove.
            form.append('other_user_id', item.user_id); // Friend's User ID
        }

        try {
            await api.post('/api_connect.php', form);
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
                        const formData = new FormData();
                        formData.append('action', 'cancel_request'); // Updated action name
                        formData.append('user_id', user.id); // Need User ID for Auth
                        formData.append('receiver_id', receiverId); // Profile ID

                        await api.post('/api_connect.php', formData); // Use new API
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
                                id: item.friend_profile_id, // Use the profile ID from API
                                full_name: item.full_name,
                                photo: item.photo,
                                user_id: item.user_id
                            }
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

