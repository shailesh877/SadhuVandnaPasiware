import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import PostImage from '../../components/PostImage';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const POST_IMAGE_URL = `${BASE_URL_ROOT}/uploads/posts/`;

const MyPostsScreen = ({ navigation }: any) => {
    const [posts, setPosts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchMyPosts();
    }, []);

    const fetchMyPosts = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                const res = await api.get(`/get_posts.php?filter_user_id=${user.id}&user_id=${user.id}`);
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
        Alert.alert(
            "Delete Post",
            "Are you sure you want to delete this post?",
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Delete",
                    style: "destructive",
                    onPress: async () => {
                        try {
                            const u = await AsyncStorage.getItem('user');
                            if (!u) return;
                            const user = JSON.parse(u);

                            const formData = new FormData();
                            formData.append('user_id', user.id);
                            formData.append('post_id', postId);

                            const res = await api.post('/delete_post.php', formData);
                            if (res.data.status === 'success') {
                                setPosts(prev => prev.filter(p => p.id !== postId));
                                Alert.alert("Success", "Post deleted");
                            } else {
                                Alert.alert("Error", res.data.message || "Failed to delete");
                            }
                        } catch (error) {
                            Alert.alert("Error", "Network request failed");
                        }
                    }
                }
            ]
        );
    };

    const renderPostItem = ({ item }: { item: any }) => (
        <View className="bg-white mb-4 p-4 border-b border-gray-100">
            <View className="flex-row items-center mb-3 justify-between">
                <View className="flex-row items-center">
                    <View>
                        <Text className="font-bold text-gray-900 text-base">{item.name}</Text>
                        <Text className="text-gray-500 text-xs">{item.date}</Text>
                    </View>
                </View>
                <TouchableOpacity onPress={() => handleDelete(item.id)} className="p-2">
                    <Ionicons name="trash-outline" size={20} color="red" />
                </TouchableOpacity>
            </View>

            <Text className="text-gray-800 text-base leading-6 mb-3">{item.description}</Text>

            {item.image && (
                <View className="mb-3">
                    <PostImage uri={`${POST_IMAGE_URL}${item.image}`} />
                </View>
            )}
        </View>
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
                keyExtractor={item => item.id.toString()}
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
