import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Dimensions, ScrollView, RefreshControl, Linking, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import PostImage from '../../components/PostImage';
import PostCard from '../../components/PostCard';
import { useLanguage } from '../../context/LanguageContext';

const { width } = Dimensions.get('window');

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
const POST_IMAGE_URL = `${BASE_URL_ROOT}/uploads/posts/`;

interface User {
    id: string;
    name: string;
    profile_photo?: string;
}

const HomeScreen = () => {
    const { t } = useLanguage();
    const [posts, setPosts] = useState<any[]>([]);
    const [stories, setStories] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [user, setUser] = useState<User | null>(null);
    const navigation = useNavigation<any>();



    useFocusEffect(
        React.useCallback(() => {
            const init = async () => {
                const uStr = await AsyncStorage.getItem('user');
                let u = null;
                if (uStr) {
                    u = JSON.parse(uStr);
                    setUser(u);
                }
                fetchData(u?.id);
            };
            init();
        }, [])
    );

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

    const fetchData = async (userId?: string) => {
        try {
            const uid = userId || user?.id || 0;
            const [postsRes, storiesRes] = await Promise.all([
                api.get('/get_posts.php?user_id=' + uid),
                api.get('/fetch_stories.php?user_id=' + uid)
            ]);

            if (postsRes.data.status === 'success') setPosts(postsRes.data.data);

            if (storiesRes.data.status === 'success') {
                const myStories = storiesRes.data.my_stories || [];
                const others = storiesRes.data.others || [];

                const list = [];
                // If I have stories, show "You" bubble
                if (myStories.length > 0) {
                    list.push({
                        id: 'mine',
                        name: 'You',
                        profile_photo: user?.profile_photo,
                        stories: myStories,
                        isMine: true
                    });
                }
                // Add others
                setStories([...list, ...others]);
            }
        } catch (error: any) {
            console.error(error);
            if (error.response && error.response.status === 404) {
                // Alert.alert("Connection Error", "API Not Found"); 
            }
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const onRefresh = () => {
        setRefreshing(true);
        fetchData();
    };

    const renderStoryItem = ({ item }: { item: any }) => {
        // 1. Add Story Button
        if (item.isAdd) {
            return (
                <TouchableOpacity
                    className="items-center mr-4 w-18"
                    onPress={() => navigation.navigate('CreateStory')}
                >
                    <View className="w-16 h-16 rounded-full border-2 border-dashed border-orange-300 items-center justify-center bg-orange-50 mb-1">
                        <Ionicons name="add" size={30} color="#ea580c" />
                    </View>
                    <Text className="text-[10px] text-gray-700 font-medium">Add</Text>
                </TouchableOpacity>
            );
        }

        // 2. Story Bubble
        const hasUnseen = item.isMine ? false : (parseInt(item.unseen_count) > 0);
        const borderColor = item.isMine
            ? 'border-blue-400'
            : (hasUnseen ? 'border-pink-500' : 'border-gray-300');

        return (
            <TouchableOpacity
                className="items-center mr-4 w-18"
                onPress={() => {
                    navigation.navigate('StoryViewer', {
                        stories: item.stories,
                        initialIndex: 0,
                        userId: item.isMine ? user?.id : item.user_id,
                        userName: item.name,
                        userPhoto: item.profile_photo,
                    });
                }}
            >
                <View className={`w-16 h-16 rounded-full border-2 p-0.5 ${borderColor}`}>
                    <Image
                        source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/100' }}
                        className="w-full h-full rounded-full bg-gray-200"
                    />
                </View>
                <Text className="text-[10px] text-gray-700 mt-1 text-center font-medium" numberOfLines={1}>
                    {item.name}
                </Text>
            </TouchableOpacity>
        );
    };

    const [viewableItems, setViewableItems] = useState<any[]>([]);

    const onViewableItemsChanged = React.useRef(({ viewableItems }: any) => {
        if (viewableItems && viewableItems.length > 0) {
            setViewableItems(viewableItems.map((item: any) => item.key));
        }
    }).current;

    const viewabilityConfig = React.useRef({
        itemVisiblePercentThreshold: 50,
    }).current;

    const renderHeader = () => (
        <>
            {/* Create Post Input Trigger */}
            <View className="bg-white p-4 mb-2 flex-row items-center">
                <TouchableOpacity onPress={() => user?.id && navigation.navigate('PublicProfile', { userId: user.id })}>
                    <Image
                        source={{ uri: user?.profile_photo ? `${PHOTO_URL}${user?.profile_photo}` : 'https://via.placeholder.com/100' }}
                        className="w-10 h-10 rounded-full bg-gray-200 mr-3"
                    />
                </TouchableOpacity>
                <TouchableOpacity
                    className="flex-1 bg-gray-100 rounded-full px-4 py-2 border border-gray-200"
                    onPress={() => navigation.navigate('CreatePost')}
                >
                    <Text className="text-gray-500">{t('whatsOnYourMind')}</Text>
                </TouchableOpacity>
            </View>

            {/* Stories Rail */}
            <View className="bg-white py-4 px-4 mb-4 border-y border-gray-100">
                <Text className="font-bold text-gray-800 mb-3 ml-1">{t('stories')}</Text>
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

    const renderPostItem = ({ item }: { item: any }) => (
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
                    isLiked: item.user_liked
                }}
                currentUserId={user?.id}
                onUserPress={() => navigation.navigate('PublicProfile', { userId: item.user_id })}
                onDeletePress={async () => {
                    try {
                        const fd = new FormData();
                        fd.append('post_id', item.id);
                        fd.append('user_id', user?.id || '');
                        const res = await api.post('/delete_post.php', fd);
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
                shouldPlay={viewableItems.includes(item.id)}
            />
        </View>
    );

    if (loading) {
        return (
            <View className="flex-1 justify-center items-center bg-orange-50">
                <ActivityIndicator size="large" color="#ea580c" />
            </View>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row justify-between items-center px-4 py-3 border-b border-orange-100 bg-white shadow-sm z-10">
                <View className="flex-row items-center">
                    <Image source={require('../../../assets/logo.png')} className="w-8 h-8 mr-2" />
                    <Text className="text-xl font-bold text-orange-600">{t('appName')}</Text>
                </View>
                <View className="flex-row items-center gap-4">
                    <TouchableOpacity onPress={() => navigation.navigate('CreatePost')}>
                        <Ionicons name="add-circle-outline" size={28} color="#ea580c" />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => navigation.navigate('Requests')}>
                        <Ionicons name="people-outline" size={26} color="black" />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => navigation.navigate('Notifications')}>
                        <Ionicons name="notifications-outline" size={24} color="black" />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => navigation.navigate('Profile')}>
                        {user?.profile_photo ? (
                            <Image source={{ uri: `${PHOTO_URL}${user.profile_photo}` }} className="w-8 h-8 rounded-full border border-orange-300" />
                        ) : (
                            <View className="w-8 h-8 rounded-full bg-orange-200 items-center justify-center border border-orange-300">
                                <Text className="text-orange-700 font-bold">{user?.name?.[0] || 'U'}</Text>
                            </View>
                        )}
                    </TouchableOpacity>
                </View>
            </View>

            <FlatList
                data={posts}
                renderItem={renderPostItem}
                keyExtractor={(item) => item.id.toString()}
                ListHeaderComponent={renderHeader}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#ea580c']} />}
                onViewableItemsChanged={onViewableItemsChanged}
                viewabilityConfig={viewabilityConfig}
                contentContainerStyle={{ paddingBottom: 20, backgroundColor: '#f9fafb' }}
                ListEmptyComponent={
                    <View className="items-center justify-center py-20">
                        <Ionicons name="newspaper-outline" size={48} color="#ccc" />
                        <Text className="text-gray-500 mt-4">{t('noPosts')}</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

export default HomeScreen;
