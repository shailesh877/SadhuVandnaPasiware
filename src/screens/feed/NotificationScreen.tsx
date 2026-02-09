import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, RefreshControl, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');

const NotificationScreen = () => {
    const navigation = useNavigation<any>();
    const [notifications, setNotifications] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    useEffect(() => {
        fetchNotifications();
    }, []);

    const fetchNotifications = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (!u) return;
            const user = JSON.parse(u);

            const res = await api.get(`/fetch_notifications.php?user_id=${user.id}`);
            if (res.data.status === 'success') {
                setNotifications(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handlePress = (item: any) => {
        if (item.type === 'message') {
            // Need to pass receiver object with id, name, photo
            // We have partial info. For Chat, receiver param expects {id, full_name, photo}
            // item.data.sender_id is the profile_id. 
            // We can construct a minimal object or fetch details. 
            // For now, let's try constructing.
            navigation.navigate('Chat', {
                receiver: {
                    id: item.data.sender_id,
                    full_name: item.title,
                    photo: item.image
                }
            });
        } else if (item.type === 'request') {
            navigation.navigate('RequestScreen');
        } else if (item.type === 'like' || item.type === 'comment') {
            // Ideally go to Post Detail. For now, go to Public Profile of the interactor?
            // Or just alert. 
            // Let's try to navigate to PublicProfile of the user who liked/commented.
            // But we don't have their USER_ID in the data directly? 
            // Wait, fetch_notifications.php for Likes selects `l.user_id`. YES.
            // item.type is 'like', we assume data has post_id. 
            // But to nav to user, we need the user_id of the liker.
            // Re-checking PHP... `l.user_id` is selected but not put in `data` array?
            // Ah, I put `post_id` in `data`. I should put `user_id` too?
            // Actually `l.user_id` is select in the query. But where is it mapped?
            // It's not mapped to 'data'. It's lost! 
            // I should update PHP to include `user_id` in `data`.
        }
    };

    const getIcon = (type: string) => {
        switch (type) {
            case 'message': return <Ionicons name="chatbubble-ellipses" size={24} color="#3b82f6" />;
            case 'request': return <Ionicons name="person-add" size={24} color="#ea580c" />;
            case 'like': return <Ionicons name="heart" size={24} color="#ef4444" />;
            case 'comment': return <Ionicons name="chatbubble" size={24} color="#10b981" />;
            default: return <Ionicons name="notifications" size={24} color="gray" />;
        }
    };

    const renderItem = ({ item }: { item: any }) => (
        <TouchableOpacity
            style={styles.card}
            activeOpacity={0.7}
            onPress={() => handlePress(item)}
        >
            <View style={styles.iconContainer}>
                {getIcon(item.type)}
            </View>
            <Image
                source={{ uri: item.image ? `${BASE_URL_ROOT}/uploads/photo/${item.image}` : 'https://via.placeholder.com/50' }}
                style={styles.avatar}
            />
            <View style={{ flex: 1 }}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                    <Text style={styles.title}>{item.title}</Text>
                    <Text style={styles.date}>{new Date(item.date).toLocaleDateString()}</Text>
                </View>
                <Text style={styles.body} numberOfLines={2}>{item.body}</Text>
            </View>
        </TouchableOpacity>
    );

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }} edges={['top']}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={{ padding: 8 }}>
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text style={styles.headerTitle}>Notifications</Text>
                <View style={{ width: 40 }} />
            </View>

            {loading ? (
                <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
                    <ActivityIndicator color="#ea580c" size="large" />
                </View>
            ) : (
                <FlatList
                    data={notifications}
                    renderItem={renderItem}
                    keyExtractor={item => item.id}
                    contentContainerStyle={{ padding: 16 }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchNotifications(); }} colors={['#ea580c']} />}
                    ListEmptyComponent={
                        <View style={{ alignItems: 'center', marginTop: 50 }}>
                            <Text style={{ color: 'gray' }}>No notifications yet.</Text>
                        </View>
                    }
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 8, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
    headerTitle: { fontSize: 20, fontWeight: 'bold' },
    card: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', padding: 12, borderRadius: 12, marginBottom: 12, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 2, elevation: 2, borderWidth: 1, borderColor: '#f3f4f6' },
    iconContainer: { marginRight: 12, width: 32, alignItems: 'center' },
    avatar: { width: 48, height: 48, borderRadius: 24, marginRight: 12, backgroundColor: '#f3f4f6' },
    title: { fontWeight: 'bold', fontSize: 16, color: '#1f2937' },
    date: { fontSize: 10, color: '#9ca3af' },
    body: { fontSize: 14, color: '#4b5563', marginTop: 2 }
});

export default NotificationScreen;
