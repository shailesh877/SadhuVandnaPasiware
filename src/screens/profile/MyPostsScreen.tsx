import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import PostImage from '../../components/PostImage';
import PostCard from '../../components/PostCard';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
const POST_IMAGE_URL = `${BASE_URL_ROOT}/uploads/posts/`;

const getImageUrl = (photo: string | null, baseUrl: string) => {
    if (!photo) return '';
    return photo.startsWith('http') ? photo : `${baseUrl}${photo}`;
};

const MyPostsScreen = ({ navigation }: any) => {
    const [posts, setPosts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [activePostId, setActivePostId] = useState<string | null>(null);

    const onViewableItemsChanged = React.useRef(({ viewableItems }: any) => {
        if (viewableItems && viewableItems.length > 0) {
            setActivePostId(viewableItems[0].key);
        } else {
            setActivePostId(null);
        }
    }).current;

    const viewabilityConfig = React.useRef({
        itemVisiblePercentThreshold: 70,
    }).current;

    useEffect(() => {
        fetchMyPosts();
    }, []);

    const fetchMyPosts = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                const res = await api.get(`get_posts.php?filter_user_id=${user.id}&user_id=${user.id}`);
                if (res.data.status === 'success') {
                    setPosts(res.data.data);
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (postId: string) => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (!u) return;
            const user = JSON.parse(u);

            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('user_id', user.id);

            const res = await api.post('remove_post.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (res.data.status === 'success') {
                setPosts(prev => prev.filter(p => p.id !== postId));
                Alert.alert("Success", "Post deleted");
            } else {
                Alert.alert("Error", res.data.message || "Failed to delete");
            }
        } catch (error) {
            Alert.alert("Error", "Network request failed");
        }
    };

    const renderPostItem = ({ item }: { item: any }) => (
        <PostCard
            post={{
                id: item.id,
                user: {
                    id: item.user_id,
                    name: item.name,
                    avatar: getImageUrl(item.profile_photo, PHOTO_URL),
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
            currentUserId={String(posts[0]?.user_id || '')}
            onDeletePress={() => handleDelete(String(item.id))}
            shouldPlay={activePostId === item.id}
        />
    );

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">My Posts</Text>
            </View>

            <FlatList
                data={posts}
                renderItem={renderPostItem}
                keyExtractor={item => item.id?.toString() || Math.random().toString()}
                onViewableItemsChanged={onViewableItemsChanged}
                viewabilityConfig={viewabilityConfig}
                contentContainerStyle={{ paddingBottom: 20 }}
                ListEmptyComponent={
                    <View className="items-center mt-20 p-4">
                        <Text className="text-4xl mb-4">📝</Text>
                        <Text className="text-gray-500 text-center text-lg">You haven't posted anything yet.</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

export default MyPostsScreen;
