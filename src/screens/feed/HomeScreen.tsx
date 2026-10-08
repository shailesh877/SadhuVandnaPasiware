import React, { useEffect, useState, useCallback, useRef } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Dimensions, ScrollView, RefreshControl, Linking, Alert, Modal, Pressable, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import PostImage from '../../components/PostImage';
import PostCard from '../../components/PostCard';
import { useLanguage } from '../../context/LanguageContext';
import { Video, ResizeMode, Audio, InterruptionModeIOS, InterruptionModeAndroid } from 'expo-av';

const { width } = Dimensions.get('window');

const BASE_URL_ROOT = API_BASE_URL.replace(/\/Api\/?$/, '').replace(/\/$/, '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
const POST_IMAGE_URL = `${BASE_URL_ROOT}/uploads/posts/`;

interface User {
    id: string;
    name: string;
    profile_photo?: string;
}

const isVideo = (uri: string) => {
    return uri.toLowerCase().endsWith('.mp4') || uri.toLowerCase().endsWith('.mov');
};

const HomeScreen = () => {
    const { t } = useLanguage();
    const [posts, setPosts] = useState<any[]>([]);
    const [stories, setStories] = useState<any[]>([]);
    const [reels, setReels] = useState<any[]>([]);
    const [suggestedUsers, setSuggestedUsers] = useState<any[]>([]);
    const [followedUsers, setFollowedUsers] = useState<Set<string>>(new Set());
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [user, setUser] = useState<User | null>(null);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [page, setPage] = useState(0);
    const [hasMore, setHasMore] = useState(true);
    const [isFetchingMore, setIsFetchingMore] = useState(false);
    const [notificationCount, setNotificationCount] = useState(0);
    const navigation = useNavigation<any>();


    useEffect(() => {
        const init = async () => {
            try {
                // Audio mode setup - static once per mount
                await Audio.setAudioModeAsync({
                    playsInSilentModeIOS: true,
                    staysActiveInBackground: false,
                    shouldDuckAndroid: true,
                    playThroughEarpieceAndroid: false,
                    interruptionModeIOS: InterruptionModeIOS.DoNotMix,
                    interruptionModeAndroid: InterruptionModeAndroid.DoNotMix,
                });
            } catch (e) {
                console.error("Init Error:", e);
            }
        };
        init();
    }, []);

    useFocusEffect(
        useCallback(() => {
            const initUser = async () => {
                try {
                    const uStr = await AsyncStorage.getItem('user');
                    let u = null;
                    if (uStr) {
                        u = JSON.parse(uStr);
                        setUser(u);
                    }
                    fetchData(u?.id);
                    fetchNotificationCount(u?.id);
                } catch (err) {
                    console.error("Init Error:", err);
                    fetchData();
                    fetchNotificationCount();
                }
            };
            initUser();
        }, [])
    );

    const fetchNotificationCount = async (userId?: string) => {
        const uid = userId || user?.id;
        if (!uid) return;
        try {
            const res = await api.get(`fetch_notifications.php?action=count&user_id=${uid}`);
            if (res.data.status === 'success') {
                setNotificationCount(res.data.unread_count);
            }
        } catch (e) {
            console.error("Badge fetch error:", e);
        }
    };

    const getImageUrl = (photo: string | undefined | null, type: 'photo' | 'post' = 'photo') => {
        if (!photo) return 'https://via.placeholder.com/100';
        if (photo.startsWith('http')) return photo;
        const baseUrl = type === 'photo' ? PHOTO_URL : POST_IMAGE_URL;
        return `${baseUrl}${photo}`;
    };

    const handleLinkPress = async (url: string) => {
        try {
            const supported = await Linking.canOpenURL(url);
            if (supported) {
                await Linking.openURL(url);
            } else {
                Alert.alert("Error", "Cannot open this link");
            }
        } catch (error) {
            console.error(error);
        }
    };

    const fetchData = async (userId?: string, isLoadMore = false) => {
        if (isLoadMore) {
            if (isFetchingMore || !hasMore) return;
            setIsFetchingMore(true);
        } else {
            setLoading(true);
            setPage(0);
            setHasMore(true);
        }

        try {
            const uid = userId || user?.id || 0;
            const currentPage = isLoadMore ? page + 1 : 0;
            const offset = currentPage * 20;

            const [postsRes, storiesRes, reelsRes, suggestRes] = await Promise.all([
                api.get(`get_posts.php?user_id=${uid}&limit=20&offset=${offset}`),
                isLoadMore ? Promise.resolve({ data: { status: 'skip' } }) : api.get('fetch_stories.php?user_id=' + uid),
                isLoadMore ? Promise.resolve({ data: { status: 'skip' } }) : api.get('get_posts.php?limit=15&user_id=' + uid),
                isLoadMore ? Promise.resolve({ data: { status: 'skip' } }) : api.get(`api_follow.php?action=fetch_suggestions&current_user_id=${uid}&limit=10`)
            ]);

            // Handle Reels (only on first load)
            if (!isLoadMore && reelsRes.data.status === 'success' && Array.isArray(reelsRes.data.data)) {
                const videoPosts = reelsRes.data.data.filter((p: any) =>
                    p.media && p.media.some((m: string) => isVideo(m))
                );
                setReels(videoPosts);
            }

            // Handle Posts
            if (postsRes.data.status === 'success') {
                const newPosts = postsRes.data.data;
                if (newPosts.length < 20) setHasMore(false);

                if (isLoadMore) {
                    setPosts(prev => [...prev, ...newPosts]);
                    setPage(currentPage);
                } else {
                    const fetchedPosts = [...newPosts];

                    // 1. Insert Suggestions Shelf after the first post (index 1)
                    if (suggestRes.data.status === 'success' && suggestRes.data.data?.length > 0) {
                        fetchedPosts.splice(1, 0, { id: 'suggest_users_shelf', type: 'suggest_users_shelf' });
                    }

                    // 2. Insert Reels Shelf after 4 posts (around index 5)
                    // (Note: Since Suggestions is at 1, the 4th post is at 4, so Reels goes to 5)
                    const shelfReelsLocal = isLoadMore ? [] : (reelsRes.data.status === 'success' ? (reelsRes.data.data?.filter((p: any) => p.media && p.media.some((m: string) => isVideo(m))) || []) : []);
                    if (shelfReelsLocal.length > 0 && fetchedPosts.length > 0) {
                        const targetIndex = fetchedPosts.length > 5 ? 5 : fetchedPosts.length;
                        fetchedPosts.splice(targetIndex, 0, { id: 'reels_shelf', type: 'reels_shelf' });
                    }
                    setPosts(fetchedPosts);
                    // Ensure first actual post is active for autoplay
                    if (fetchedPosts.length > 0) {
                        const firstRealPost = fetchedPosts.find(p => p.type !== 'reels_shelf' && p.type !== 'suggest_users_shelf');
                        if (firstRealPost) {
                            setActivePostId(firstRealPost.id?.toString());
                        } else {
                            setActivePostId(fetchedPosts[0].id?.toString());
                        }
                    }
                }
            }

            // Handle Stories (only on first load)
            if (!isLoadMore && storiesRes.data.status === 'success') {
                const myStories = storiesRes.data.my_stories || [];
                const others = storiesRes.data.others || [];
                const list = [];
                if (myStories.length > 0) {
                    list.push({
                        id: 'mine',
                        name: 'You',
                        profile_photo: user?.profile_photo,
                        stories: myStories,
                        isMine: true
                    });
                }
                setStories([...list, ...others]);
            }

            // Handle Suggested Users (only on first load)
            if (!isLoadMore && suggestRes.data.status === 'success' && Array.isArray(suggestRes.data.data)) {
                setSuggestedUsers(suggestRes.data.data);
            }
        } catch (error: any) {
            console.error("Fetch Data Error:", error);
        } finally {
            setLoading(false);
            setRefreshing(false);
            setIsFetchingMore(false);
        }
    };

    const onRefresh = () => {
        setRefreshing(true);
        fetchData();
        fetchNotificationCount();
    };

    const handlePlusPress = () => {
        setShowUploadModal(true);
    };

    const handleFollowUser = async (targetId: string) => {
        if (!user?.id) return;
        try {
            const formData = new FormData();
            formData.append('current_user_id', user.id);
            formData.append('user_id', targetId);
            await api.post('api_follow.php?action=follow', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            setFollowedUsers(prev => new Set([...prev, targetId]));
        } catch (e) {
            console.error('Follow error:', e);
        }
    };

    const renderStoryItem = ({ item }: { item: any }) => {
        // 1. Add Story Button
        if (item.isAdd) {
            return (
                <TouchableOpacity
                    style={{ alignItems: 'center', marginRight: 16 }}
                    onPress={() => navigation.navigate('CreateStory')}
                    activeOpacity={0.8}
                >
                    <View style={{ width: 56, height: 56, borderRadius: 28, position: 'relative', marginBottom: 6 }}>
                        <Image
                            source={{ uri: user?.profile_photo ? `${PHOTO_URL}${user?.profile_photo}` : 'https://via.placeholder.com/100' }}
                            style={{ width: '100%', height: '100%', borderRadius: 28, backgroundColor: '#f3f4f6', borderWidth: 1, borderColor: '#e5e7eb' }}
                        />
                        {/* Floating plus badge overlay */}
                        <View style={{ position: 'absolute', bottom: -2, right: -2, backgroundColor: '#ea580c', width: 20, height: 20, borderRadius: 10, alignItems: 'center', justifyContent: 'center', borderWidth: 2, borderColor: 'white' }}>
                            <Ionicons name="add" size={14} color="white" />
                        </View>
                    </View>
                    <Text style={{ fontSize: 10, color: '#4b5563', fontWeight: 'bold' }}>{t('add') || 'Add'}</Text>
                </TouchableOpacity>
            );
        }

        // 2. Story Bubble
        const hasUnseen = item.isMine ? false : (parseInt(item.unseen_count) > 0);
        return (
            <TouchableOpacity
                style={{ alignItems: 'center', marginRight: 16 }}
                onPress={() => {
                    navigation.navigate('StoryViewer', {
                        stories: item.stories,
                        initialIndex: 0,
                        userId: item.isMine ? user?.id : item.user_id,
                        userName: item.name,
                        userPhoto: item.profile_photo,
                    });
                }}
                activeOpacity={0.8}
            >
                <View style={{
                    width: 56,
                    height: 56,
                    borderRadius: 28,
                    borderWidth: 2,
                    borderColor: hasUnseen ? '#ea580c' : '#e5e7eb',
                    padding: 2.5,
                    marginBottom: 6,
                    justifyContent: 'center',
                    alignItems: 'center'
                }}>
                    <Image
                        source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/100' }}
                        style={{ width: '100%', height: '100%', borderRadius: 28, backgroundColor: '#f3f4f6' }}
                    />
                </View>
                <Text style={{ fontSize: 10, color: '#374151', fontWeight: 'bold', maxWidth: 60, textAlign: 'center' }} numberOfLines={1}>
                    {item.isMine ? 'You' : item.name}
                </Text>
            </TouchableOpacity>
        );
    };

    const [activePostId, setActivePostId] = useState<string | null>(null);

    const onViewableItemsChanged = React.useRef(({ viewableItems }: any) => {
        if (viewableItems && viewableItems.length > 0) {
            // Find the item currently at the top of the viewport
            const firstPost = viewableItems.find((vi: any) => 
                vi.item && 
                vi.item.type !== 'reels_shelf' && 
                vi.item.type !== 'suggest_users_shelf'
            );
            
            if (firstPost) {
                const activeId = firstPost.key ? firstPost.key.toString() : null;
                setActivePostId(activeId);
            } else if (viewableItems[0]) {
                setActivePostId(viewableItems[0].key?.toString());
            }
        }
    }).current;

    const viewabilityConfig = React.useRef({
        itemVisiblePercentThreshold: 60,
        minimumViewTime: 200,
    }).current;

    const renderHeader = () => (
        <>
            <View style={{ backgroundColor: 'white', paddingHorizontal: 16, paddingVertical: 12, marginBottom: 8, flexDirection: 'row', alignItems: 'center', borderBottomWidth: 1, borderBottomColor: '#f3f4f6' }}>
                <TouchableOpacity onPress={() => user?.id && navigation.navigate('PublicProfile', { userId: user.id })} activeOpacity={0.8}>
                    <Image
                        source={{ uri: user?.profile_photo ? `${PHOTO_URL}${user?.profile_photo}` : 'https://via.placeholder.com/100' }}
                        style={{ width: 38, height: 38, borderRadius: 19, marginRight: 12, borderWidth: 1, borderColor: '#ffedd5', backgroundColor: '#f9fafb' }}
                    />
                </TouchableOpacity>
                <TouchableOpacity
                    style={{ flex: 1, backgroundColor: '#f3f4f6', borderRadius: 20, paddingHorizontal: 16, height: 38, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}
                    onPress={() => navigation.navigate('CreatePost')}
                    activeOpacity={0.7}
                >
                    <Text style={{ fontSize: 13, color: '#9ca3af', fontWeight: '500', flex: 1 }}>{t('whatsOnYourMind') || "What's on your mind?"}</Text>
                    <Ionicons name="image-outline" size={18} color="#10b981" />
                </TouchableOpacity>
            </View>

            {/* Stories Rail */}
            <View style={{ backgroundColor: 'white', paddingVertical: 14, paddingHorizontal: 16, marginBottom: 8, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' }}>
                <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#1f2937', marginBottom: 12, marginLeft: 4 }}>
                    {t('stories') || 'Stories'}
                </Text>
                <FlatList
                    data={[{ id: 'add', isAdd: true }, ...stories]}
                    renderItem={renderStoryItem}
                    keyExtractor={(item, index) => item.id ? item.id.toString() : index.toString()}
                    horizontal
                    showsHorizontalScrollIndicator={false}
                />
            </View>
        </>
    );

    const renderSuggestShelf = () => (
        <View className="bg-white py-4 mb-2 border-y border-gray-100">
            <View className="flex-row justify-between items-center px-5 mb-3.5">
                <View className="flex-row items-center">
                    <Ionicons name="people" size={18} color="#ea580c" />
                    <Text className="font-bold text-gray-800 text-sm ml-2">People You May Know</Text>
                </View>
                <TouchableOpacity onPress={() => navigation.push('FollowList', { userId: user?.id, action: 'fetch_all_members', title: 'All Members' })} activeOpacity={0.7}>
                    <Text className="text-orange-600 font-bold text-xs">See All</Text>
                </TouchableOpacity>
            </View>
            <FlatList
                horizontal
                showsHorizontalScrollIndicator={false}
                data={suggestedUsers}
                keyExtractor={(item) => `suggest-${item.id}`}
                contentContainerStyle={{ paddingLeft: 20, paddingRight: 8 }}
                renderItem={({ item }) => {
                    const isFollowed = followedUsers.has(item.id?.toString());
                    return (
                        <TouchableOpacity
                            onPress={() => navigation.navigate('PublicProfile', { userId: item.id })}
                            className="mr-3 w-32 bg-white rounded-2xl border border-gray-100 items-center py-4 px-2 shadow-sm"
                            activeOpacity={0.8}
                        >
                            {item.photo ? (
                                <Image
                                    source={{ uri: `${PHOTO_URL}${item.photo}` }}
                                    className="w-14 h-14 rounded-full bg-gray-100 mb-2.5 border border-orange-100"
                                />
                            ) : (
                                <View className="w-14 h-14 rounded-full bg-orange-50 items-center justify-center mb-2.5 border border-orange-100">
                                    <Text className="text-orange-600 font-bold text-lg">{item.full_name?.[0] || item.name?.[0] || '?'}</Text>
                                </View>
                            )}
                            <Text className="text-gray-800 font-semibold text-xs text-center px-1" numberOfLines={1}>{item.full_name || item.name}</Text>
                            {item.city ? <Text className="text-gray-400 text-[10px] mt-0.5" numberOfLines={1}>{item.city}</Text> : null}
                            <TouchableOpacity
                                onPress={() => handleFollowUser(item.id?.toString())}
                                className={`mt-3 px-4 py-1.5 rounded-full ${isFollowed ? 'bg-gray-100 border border-gray-200' : 'bg-orange-600'}`}
                                activeOpacity={0.7}
                            >
                                <Text className={`text-[10px] font-bold ${isFollowed ? 'text-gray-500' : 'text-white'}`}>
                                    {isFollowed ? 'Following' : 'Follow'}
                                </Text>
                            </TouchableOpacity>
                        </TouchableOpacity>
                    );
                }}
            />
        </View>
    );

    const renderReelsShelf = () => (
        <View className="bg-white py-4 mb-2 border-y border-gray-100">
            <View className="flex-row justify-between items-center px-5 mb-3.5">
                <View className="flex-row items-center">
                    <Ionicons name="videocam" size={18} color="#ea580c" />
                    <Text className="font-bold text-gray-800 text-sm ml-2">Reels</Text>
                </View>
                <TouchableOpacity onPress={() => navigation.navigate('Reels')} activeOpacity={0.7}>
                    <Text className="text-orange-600 font-bold text-xs">View All</Text>
                </TouchableOpacity>
            </View>
            <FlatList
                horizontal
                showsHorizontalScrollIndicator={false}
                data={[{ id: 'create_reel', isCreate: true }, ...reels]}
                keyExtractor={(item) => `shelf-${item.id}`}
                contentContainerStyle={{ paddingLeft: 20, paddingRight: 8 }}
                renderItem={({ item }) => {
                    if (item.isCreate) {
                        return (
                            <TouchableOpacity
                                onPress={() => navigation.navigate('CreateReel')}
                                className="mr-3 w-32 h-52 rounded-2xl bg-orange-50/50 items-center justify-center border border-orange-100 border-dashed"
                                activeOpacity={0.7}
                            >
                                <View className="bg-orange-100 w-10 h-10 rounded-full items-center justify-center mb-2">
                                    <Ionicons name="add" size={24} color="#ea580c" />
                                </View>
                                <Text className="text-orange-700 font-bold text-xs">Create</Text>
                            </TouchableOpacity>
                        );
                    }
                    return (
                        <TouchableOpacity
                            onPress={() => navigation.navigate('Reels', { initialReelId: item.id })}
                            className="mr-3 w-32 h-52 rounded-2xl overflow-hidden bg-gray-900 relative shadow-md"
                            activeOpacity={0.8}
                        >
                            <Video
                                source={{ uri: getImageUrl(item.image || item.media?.[0], 'post') }}
                                style={{ width: '100%', height: '100%', opacity: 0.9 }}
                                resizeMode={ResizeMode.COVER}
                                shouldPlay={false}
                                useNativeControls={false}
                            />
                            {/* Play Icon Overlay for Reels */}
                            <View style={StyleSheet.absoluteFill} className="items-center justify-center">
                                <View className="bg-white/20 border border-white/20 p-2 rounded-full">
                                    <Ionicons name="play-circle-outline" size={28} color="white" />
                                </View>
                            </View>
                            {/* Simple Bottom Overlay */}
                            <View className="absolute bottom-0 left-0 right-0 p-2.5 bg-black/40 justify-center">
                                <Text className="text-white text-[10px] font-semibold" numberOfLines={1}>{item.name}</Text>
                                <View className="flex-row items-center mt-1">
                                    <Ionicons name="heart" size={8} color="white" />
                                    <Text className="text-white text-[9px] font-bold ml-1">{item.likes || 0}</Text>
                                </View>
                            </View>
                            <View className="absolute top-2 right-2 bg-black/30 p-1.5 rounded-full">
                                <Ionicons name="videocam" size={12} color="white" />
                            </View>
                        </TouchableOpacity>
                    );
                }}
            />
        </View>
    );

    const renderPostItem = ({ item }: { item: any }) => {
        if (item.type === 'reels_shelf') {
            return renderReelsShelf();
        }
        if (item.type === 'suggest_users_shelf') {
            return renderSuggestShelf();
        }
        return (
            <View className="w-full">
                <PostCard
                    post={{
                        id: item.id,
                        user: {
                            id: item.user_id,
                            name: item.name,
                            avatar: getImageUrl(item.profile_photo, 'photo'),
                        },
                        content: item.description,
                        media: item.media,
                        image: item.image,
                        likes: parseInt(item.likes) || 0,
                        comments: item.comments?.length || 0,
                        timeAgo: item.date,
                        isLiked: item.user_liked,
                        link: item.link
                    }}
                    currentUserId={user?.id}
                    onUserPress={() => navigation.navigate('PublicProfile', { userId: item.user_id })}
                    onDeletePress={async () => {
                        try {
                            const formData = new FormData();
                            formData.append('post_id', item.id);
                            formData.append('user_id', user?.id || '');
                            const res = await api.post('remove_post.php', formData, {
                                headers: { 'Content-Type': 'multipart/form-data' }
                            });
                            if (res.data.status === 'success') {
                                setPosts(prev => prev.filter(p => p.id !== item.id));
                                Alert.alert(t('success'), "Post deleted");
                            } else {
                                Alert.alert(t('error'), res.data.message || "Failed to delete");
                            }
                        } catch (error) {
                            console.error(error);
                            Alert.alert(t('error'), "Something went wrong");
                        }
                    }}
                    shouldPlay={String(activePostId) === String(item.id)}
                />
            </View>
        );
    };

    if (loading) {
        return (
            <View className="flex-1 justify-center items-center bg-orange-50">
                <ActivityIndicator size="large" color="#ea580c" />
            </View>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-[#f9fafb]" edges={['top']}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 10, backgroundColor: 'white', borderBottomWidth: 1, borderBottomColor: '#f3f4f6', shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.02, shadowRadius: 6, elevation: 1, zIndex: 10 }}>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    <Image source={require('../../../assets/logo.png')} style={{ width: 32, height: 32, marginRight: 8 }} />
                    <Text style={{ fontSize: 18, fontWeight: '800', color: '#111827', letterSpacing: -0.3 }}>LinkUp</Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                    <TouchableOpacity 
                        onPress={() => navigation.navigate('Search')} 
                        activeOpacity={0.7}
                        style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#f3f4f6', alignItems: 'center', justifyContent: 'center' }}
                    >
                        <Ionicons name="search-outline" size={18} color="#374151" />
                    </TouchableOpacity>
                    <TouchableOpacity 
                        onPress={handlePlusPress} 
                        activeOpacity={0.7}
                        style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#ea580c12', alignItems: 'center', justifyContent: 'center' }}
                    >
                        <Ionicons name="add" size={20} color="#ea580c" />
                    </TouchableOpacity>
                    <TouchableOpacity 
                        onPress={() => navigation.navigate('Requests')} 
                        activeOpacity={0.7}
                        style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#f3f4f6', alignItems: 'center', justifyContent: 'center' }}
                    >
                        <Ionicons name="people-outline" size={18} color="#374151" />
                    </TouchableOpacity>
                    <TouchableOpacity 
                        onPress={() => navigation.navigate('Notifications')} 
                        activeOpacity={0.7}
                        style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#f3f4f6', alignItems: 'center', justifyContent: 'center', position: 'relative' }}
                    >
                        <Ionicons name="notifications-outline" size={18} color="#374151" />
                        {notificationCount > 0 && (
                            <View style={{ position: 'absolute', top: -3, right: -3, backgroundColor: '#ef4444', borderRadius: 8, minWidth: 16, height: 16, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4, borderWidth: 1.5, borderColor: 'white' }}>
                                <Text style={{ color: 'white', fontSize: 8, fontWeight: 'bold' }}>
                                    {notificationCount > 9 ? '9+' : notificationCount}
                                </Text>
                            </View>
                        )}
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => navigation.navigate('Profile')} activeOpacity={0.7}>
                        {user?.profile_photo ? (
                            <Image source={{ uri: `${PHOTO_URL}${user?.profile_photo}` }} style={{ width: 34, height: 34, borderRadius: 17, borderWidth: 1.5, borderColor: '#ffedd5', backgroundColor: '#f3f4f6' }} />
                        ) : (
                            <View style={{ width: 34, height: 34, borderRadius: 17, alignItems: 'center', justifyContent: 'center', borderWidth: 1.5, borderColor: '#ffedd5', backgroundColor: '#fff7ed' }}>
                                <Text style={{ color: '#ea580c', fontWeight: 'bold', fontSize: 12 }}>{user?.name?.[0] || 'U'}</Text>
                            </View>
                        )}
                    </TouchableOpacity>
                </View>
            </View>

            <FlatList
                data={posts}
                renderItem={renderPostItem}
                keyExtractor={(item) => item.id.toString()}
                extraData={activePostId}
                ListHeaderComponent={renderHeader}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#ea580c']} tintColor="#ea580c" />}
                onViewableItemsChanged={onViewableItemsChanged}
                viewabilityConfig={viewabilityConfig}
                windowSize={3}
                initialNumToRender={3}
                maxToRenderPerBatch={2}
                removeClippedSubviews={true}
                contentContainerStyle={{ paddingBottom: 100, backgroundColor: '#f9fafb' }}
                ListEmptyComponent={
                    <View className="items-center justify-center py-20">
                        <Ionicons name="newspaper-outline" size={48} color="#ccc" />
                        <Text className="text-gray-500 mt-4">{t('noPosts')}</Text>
                    </View>
                }
                onEndReached={() => fetchData(undefined, true)}
                onEndReachedThreshold={0.5}
                ListFooterComponent={isFetchingMore ? (
                    <View style={{ paddingVertical: 20 }}>
                        <ActivityIndicator color="#ea580c" />
                    </View>
                ) : null}
            />
            
            {/* Professional Floating Action Button */}
            <TouchableOpacity 
                onPress={() => navigation.navigate('SupportChat')}
                activeOpacity={0.85}
                style={{ 
                    position: 'absolute', 
                    bottom: 24, 
                    right: 20, 
                    backgroundColor: '#ea580c', 
                    width: 50, 
                    height: 50, 
                    borderRadius: 25, 
                    justifyContent: 'center', 
                    alignItems: 'center', 
                    shadowColor: '#ea580c', 
                    shadowOffset: { width: 0, height: 4 }, 
                    shadowOpacity: 0.3, 
                    shadowRadius: 8, 
                    elevation: 6, 
                    zIndex: 999 
                }}
            >
                <Ionicons name="chatbubble-ellipses" size={22} color="white" />
            </TouchableOpacity>

            {/* Professional Upload Modal */}
            <Modal
                visible={showUploadModal}
                transparent={true}
                animationType="slide"
                onRequestClose={() => setShowUploadModal(false)}
            >
                <Pressable
                    className="flex-1 bg-black/50 justify-end"
                    onPress={() => setShowUploadModal(false)}
                >
                    <View className="bg-white rounded-t-3xl p-6 pb-10 shadow-2xl">
                        <View className="items-center mb-6">
                            <View className="w-12 h-1.5 bg-gray-200 rounded-full mb-6" />
                            <Text className="text-xl font-bold text-gray-800">Create New</Text>
                            <Text className="text-gray-500 text-sm mt-1">What would you like to share today?</Text>
                        </View>

                        <View className="gap-3">
                            <TouchableOpacity
                                className="flex-row items-center p-4 bg-orange-50 rounded-2xl border border-orange-100"
                                onPress={() => { setShowUploadModal(false); navigation.navigate('CreatePost'); }}
                            >
                                <View className="w-12 h-12 bg-white rounded-xl items-center justify-center shadow-sm">
                                    <Ionicons name="create" size={24} color="#ea580c" />
                                </View>
                                <View className="ml-4 flex-1">
                                    <Text className="text-base font-bold text-gray-800">Create Post</Text>
                                    <Text className="text-gray-500 text-xs">Share your photos and thoughts</Text>
                                </View>
                                <Ionicons name="chevron-forward" size={20} color="#ccc" />
                            </TouchableOpacity>

                            <TouchableOpacity
                                className="flex-row items-center p-4 bg-purple-50 rounded-2xl border border-purple-100"
                                onPress={() => { setShowUploadModal(false); navigation.navigate('CreateReel'); }}
                            >
                                <View className="w-12 h-12 bg-white rounded-xl items-center justify-center shadow-sm">
                                    <Ionicons name="videocam" size={24} color="#8b5cf6" />
                                </View>
                                <View className="ml-4 flex-1">
                                    <Text className="text-base font-bold text-gray-800">Create Reel</Text>
                                    <Text className="text-gray-500 text-xs">Record and share short videos</Text>
                                </View>
                                <Ionicons name="chevron-forward" size={20} color="#ccc" />
                            </TouchableOpacity>

                            <TouchableOpacity
                                className="flex-row items-center p-4 bg-blue-50 rounded-2xl border border-blue-100"
                                onPress={() => { setShowUploadModal(false); navigation.navigate('CreateStory'); }}
                            >
                                <View className="w-12 h-12 bg-white rounded-xl items-center justify-center shadow-sm">
                                    <Ionicons name="camera" size={24} color="#3b82f6" />
                                </View>
                                <View className="ml-4 flex-1">
                                    <Text className="text-base font-bold text-gray-800">Add Story</Text>
                                    <Text className="text-gray-500 text-xs">Share a moment for 24 hours</Text>
                                </View>
                                <Ionicons name="chevron-forward" size={20} color="#ccc" />
                            </TouchableOpacity>

                            <TouchableOpacity
                                className="mt-4 p-4 items-center"
                                onPress={() => setShowUploadModal(false)}
                            >
                                <Text className="text-gray-400 font-bold">Cancel</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </Pressable>
            </Modal>
        </SafeAreaView>
    );
};

export default HomeScreen;
