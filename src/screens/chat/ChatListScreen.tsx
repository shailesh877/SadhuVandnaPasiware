import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Modal, TextInput, Alert, LayoutAnimation, Platform, UIManager } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL_COMMUNITY = `${BASE_URL_ROOT}/uploads/photo/`;

// Enable LayoutAnimation for Android
if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
    UIManager.setLayoutAnimationEnabledExperimental(true);
}

const ChatListScreen = ({ navigation }: any) => {
    const [friends, setFriends] = useState<any[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(true);
    const activePlatform = 'community';
    const { t } = useLanguage();

    const [joinModalVisible, setJoinModalVisible] = useState(false);
    const [joinCode, setJoinCode] = useState('');
    const [joining, setJoining] = useState(false);
    const [userId, setUserId] = useState<number | null>(null);
    const [showMenu, setShowMenu] = useState(false);

    useEffect(() => {
        const unsubscribe = navigation.addListener('focus', () => {
            fetchFriends();
        });
        fetchFriends(); // Initial fetch
        return unsubscribe;
    }, [navigation, activePlatform]);

    const fetchFriends = async () => {
        setLoading(true);
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                setUserId(user.id);
                const res = await api.get(`/get_active_chats.php?user_id=${user.id}&platform=${activePlatform}`);
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

    const handleJoinGroup = async () => {
        if (!joinCode.trim() || !userId) return;
        setJoining(true);
        try {
            const res = await api.post('/join_group_via_link.php', {
                invite_code: joinCode.trim(),
                user_id: userId
            });

            if (res.data.status === 'success') {
                Alert.alert('Success', res.data.message);
                setJoinModalVisible(false);
                setJoinCode('');
                fetchFriends();
                
                // Navigate to the newly joined group
                const group = res.data.group;
                const receiverObj = {
                    id: group.id,
                    name: group.name,
                    isGroup: true,
                    full_name: group.name
                };
                navigation.navigate('Chat', { receiver: receiverObj, platform: activePlatform, isGroup: true });
            } else {
                Alert.alert('Error', res.data.message);
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'Network error');
        } finally {
            setJoining(false);
        }
    };

    const toggleMenu = () => {
        LayoutAnimation.configureNext(LayoutAnimation.Presets.easeInEaseOut);
        setShowMenu(!showMenu);
    };

    const renderItem = ({ item }: { item: any }) => {
        const receiverObj = {
            id: item.partner_id,
            name: item.full_name,
            photo: item.profile_photo || item.photo,
            isGroup: item.isGroup || false,
            ...item
        };

        const currentPhotoUrl = PHOTO_URL_COMMUNITY;

        return (
            <TouchableOpacity
                style={{ flexDirection: 'row', alignItems: 'center', paddingVertical: 12, paddingHorizontal: 16, backgroundColor: 'white', borderBottomWidth: 1, borderBottomColor: '#f9fafb' }}
                onPress={() => navigation.navigate('Chat', { receiver: receiverObj, platform: activePlatform, isGroup: item.isGroup })}
                activeOpacity={0.7}
            >
                <View style={{ position: 'relative', marginRight: 14 }}>
                    <Image
                        source={{ uri: (item.profile_photo) ? `${currentPhotoUrl}${encodeURIComponent(item.profile_photo)}` : 'https://via.placeholder.com/100' }}
                        style={{ width: 50, height: 50, borderRadius: 25, backgroundColor: '#f3f4f6', borderWidth: 1, borderColor: '#f3f4f6' }}
                    />
                    {!item.isGroup && item.is_online && (
                        <View 
                            style={{ position: 'absolute', bottom: 1, right: 1, width: 12, height: 12, borderRadius: 6, backgroundColor: '#10b981', borderWidth: 2, borderColor: 'white' }} 
                        />
                    )}
                </View>
                <View style={{ flex: 1 }}>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 3 }}>
                        <Text style={{ fontSize: 15, fontWeight: '600', color: '#111827' }} numberOfLines={1}>
                            {item.full_name}
                        </Text>
                        <Text style={{ fontSize: 11, fontWeight: '500', color: '#9ca3af' }}>
                            {item.time}
                        </Text>
                    </View>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Text style={{ fontSize: 13, color: '#6b7280', flex: 1, marginRight: 8 }} numberOfLines={1}>
                            {item.last_message || 'Media'}
                        </Text>
                        {item.unread > 0 && (
                            <View style={{ backgroundColor: '#ea580c', borderRadius: 9, minWidth: 18, height: 18, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4, shadowColor: '#ea580c', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 3, elevation: 1 }}>
                                <Text style={{ color: 'white', fontSize: 9, fontWeight: 'bold' }}>{item.unread}</Text>
                            </View>
                        )}
                    </View>
                </View>
            </TouchableOpacity>
        );
    };

    // Filter friends list based on search query
    const filteredFriends = friends.filter(friend => 
        friend.full_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        friend.last_message?.toLowerCase().includes(searchQuery.toLowerCase())
    );

    if (loading && friends.length === 0) {
        return <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'white' }}><ActivityIndicator color="#ea580c" size="large" /></View>;
    }

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }} edges={['top']}>
            {/* Header Toolbar */}
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingTop: 12, paddingBottom: 10, backgroundColor: 'white', zIndex: 50 }}>
                <Text style={{ fontSize: 24, fontWeight: '800', color: '#111827', letterSpacing: -0.5 }}>{t('messages') || "Messages"}</Text>
                <TouchableOpacity onPress={toggleMenu} style={{ padding: 4 }} activeOpacity={0.7}>
                    <Ionicons name="ellipsis-vertical" size={20} color="#374151" />
                </TouchableOpacity>
                {showMenu && (
                    <View style={{ position: 'absolute', top: 48, right: 16, backgroundColor: 'white', borderRadius: 16, borderWidth: 1, borderColor: '#f3f4f6', shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.08, shadowRadius: 8, elevation: 5, paddingVertical: 6, width: 150, zIndex: 100 }}>
                        <TouchableOpacity 
                            style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f9fafb' }}
                            onPress={() => {
                                toggleMenu();
                                navigation.navigate('CreateGroup');
                            }}
                        >
                            <Ionicons name="add-circle-outline" size={18} color="#ea580c" style={{ marginRight: 8 }} />
                            <Text style={{ color: '#374151', fontSize: 13, fontWeight: '600' }}>Create Group</Text>
                        </TouchableOpacity>
                        <TouchableOpacity 
                            style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12 }}
                            onPress={() => {
                                toggleMenu();
                                setJoinModalVisible(true);
                            }}
                        >
                            <Ionicons name="enter-outline" size={18} color="#ea580c" style={{ marginRight: 8 }} />
                            <Text style={{ color: '#374151', fontSize: 13, fontWeight: '600' }}>Join Group</Text>
                        </TouchableOpacity>
                    </View>
                )}
            </View>

            {/* Search Filter Bar */}
            <View style={{ paddingHorizontal: 16, paddingBottom: 12, backgroundColor: 'white', borderBottomWidth: 1, borderBottomColor: '#f3f4f6' }}>
                <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#f3f4f6', borderRadius: 20, paddingHorizontal: 12, height: 38 }}>
                    <Ionicons name="search" size={16} color="#9ca3af" style={{ marginRight: 8 }} />
                    <TextInput
                        style={{ flex: 1, fontSize: 13, color: '#374151', padding: 0 }}
                        placeholder={t('search') || "Search messages..."}
                        placeholderTextColor="#9ca3af"
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                        autoCorrect={false}
                    />
                    {searchQuery.length > 0 && (
                        <TouchableOpacity onPress={() => setSearchQuery('')}>
                            <Ionicons name="close-circle" size={16} color="#9ca3af" />
                        </TouchableOpacity>
                    )}
                </View>
            </View>

            <FlatList
                data={filteredFriends}
                renderItem={renderItem}
                keyExtractor={(item, index) => item.partner_id ? `${item.isGroup ? 'group_' : 'user_'}${item.partner_id}` : index.toString()}
                contentContainerStyle={{ paddingVertical: 4 }}
                ListEmptyComponent={
                    <View style={{ alignItems: 'center', marginTop: 60, paddingHorizontal: 20 }}>
                        <Ionicons name="chatbubbles-outline" size={60} color="#fed7aa" />
                        <Text style={{ color: '#9ca3af', fontSize: 15, fontWeight: '600', marginTop: 12, textAlign: 'center' }}>
                            {searchQuery.length > 0 ? `No results found for "${searchQuery}"` : `No chats found`}
                        </Text>
                    </View>
                }
            />

            {/* Join Group Modal */}
            <Modal visible={joinModalVisible} transparent={true} animationType="fade" onRequestClose={() => setJoinModalVisible(false)}>
                <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'center', padding: 24 }}>
                    <View style={{ backgroundColor: 'white', borderRadius: 24, padding: 24, shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.1, shadowRadius: 12, elevation: 5 }}>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                            <Text style={{ fontSize: 20, fontWeight: 'bold', color: '#111827' }}>Join Group</Text>
                            <TouchableOpacity onPress={() => setJoinModalVisible(false)}>
                                <Ionicons name="close" size={24} color="#6b7280" />
                            </TouchableOpacity>
                        </View>
                        
                        <Text style={{ color: '#6b7280', fontSize: 13, fontWeight: '500', marginBottom: 16 }}>
                            Enter the 8-character invite code to join a group.
                        </Text>
                        
                        <View style={{ backgroundColor: '#f9fafb', borderRadius: 16, padding: 12, borderWidth: 1, borderColor: '#e5e7eb', marginBottom: 20 }}>
                            <TextInput
                                style={{ fontSize: 20, fontWeight: 'bold', textAlign: 'center', letterSpacing: 4, color: '#ea580c' }}
                                placeholder="8A2B3C..."
                                placeholderTextColor="#fed7aa"
                                value={joinCode}
                                onChangeText={(val) => setJoinCode(val.toUpperCase())}
                                maxLength={8}
                                autoCapitalize="characters"
                                autoCorrect={false}
                            />
                        </View>

                        <TouchableOpacity 
                            style={{ backgroundColor: joinCode.length === 8 ? '#ea580c' : '#e5e7eb', paddingVertical: 14, borderRadius: 24, flexDirection: 'row', justifyContent: 'center', alignItems: 'center' }}
                            disabled={joinCode.length !== 8 || joining}
                            onPress={handleJoinGroup}
                        >
                            {joining ? (
                                <ActivityIndicator color="white" />
                            ) : (
                                <>
                                    <Ionicons name="enter-outline" size={18} color="white" style={{ marginRight: 6 }} />
                                    <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 14 }}>Join Group</Text>
                                </>
                            )}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

export default ChatListScreen;
