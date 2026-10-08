import React, { useEffect, useState, useRef } from 'react';
import { View, Text, ScrollView, TouchableOpacity, Image, TextInput, ActivityIndicator, KeyboardAvoidingView, Platform, Alert, FlatList } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../../services/api';
import PostCard, { PostType } from '../../components/PostCard';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const PostDetailScreen = ({ route, navigation }: any) => {
    const { post, postId, focusedComment } = route.params;
    const [comments, setComments] = useState<any[]>([]);
    const [newComment, setNewComment] = useState('');
    const [loadingComments, setLoadingComments] = useState(true);
    const [loadingPost, setLoadingPost] = useState(!post && !!postId);
    const [user, setUser] = useState<any>(null);
    const [currentPost, setCurrentPost] = useState<PostType | null>(post || null);

    useEffect(() => {
        loadUser();
        if (post) {
            fetchComments(post.id);
        } else if (postId) {
            fetchSinglePost(postId);
        }
    }, [postId]);

    const fetchSinglePost = async (id: string) => {
        try {
            setLoadingPost(true);
            const res = await api.get(`get_posts.php?post_id=${id}`);
            if (res.data.status === 'success' && res.data.data && res.data.data.length > 0) {
                const item = res.data.data[0];
                const transformedPost: PostType = {
                    id: String(item.id),
                    user: {
                        id: String(item.user_id),
                        name: item.name || 'User',
                        avatar: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/50'
                    },
                    content: item.description || item.status || '',
                    media: item.media || [],
                    likes: parseInt(item.likes) || 0,
                    comments: Array.isArray(item.comments) ? item.comments.length : (parseInt(item.comments) || 0),
                    timeAgo: item.date || '',
                    isLiked: !!item.user_liked,
                    link: item.link
                };
                setCurrentPost(transformedPost);
                fetchComments(String(item.id));
            } else {
                Alert.alert("Error", "Post not found");
                navigation.goBack();
            }
        } catch (e) {
            console.error(e);
            Alert.alert("Error", "Failed to load post");
        } finally {
            setLoadingPost(false);
        }
    };

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) setUser(JSON.parse(u));
    };

    const fetchComments = async (id: string) => {
        try {
            const res = await api.get(`get_posts.php?action=fetch_comments&id=${id}`);
            if (Array.isArray(res.data)) {
                setComments(res.data);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoadingComments(false);
        }
    };

    const handleSendComment = async () => {
        if (!newComment.trim() || !currentPost) return;
        if (!user) { Alert.alert("Login Required"); return; }

        try {
            const payload = {
                id: currentPost.id,
                user_id: user.id,
                action: 'comment',
                comment: newComment
            };
            await api.post('like_comment_action.php', payload);
            setNewComment('');
            fetchComments(currentPost.id);

            // Optimistically update comment count on post
            setCurrentPost(prev => prev ? ({ ...prev, comments: prev.comments + 1 }) : null);
        } catch (e) {
            Alert.alert("Error", "Failed to post comment");
        }
    };

    const renderComment = ({ item }: { item: any }) => (
        <View className="flex-row p-3 border-b border-gray-100 bg-white">
            <Image
                source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/50' }}
                className="w-8 h-8 rounded-full bg-gray-200 mt-1"
            />
            <View className="ml-3 flex-1">
                <View className="bg-gray-100 rounded-2xl px-3 py-2 self-start">
                    <Text className="font-bold text-gray-900 text-xs">{item.name}</Text>
                    <Text className="text-gray-800 text-sm mt-0.5">{item.comment}</Text>
                </View>
                <Text className="text-gray-400 text-[10px] ml-2 mt-1">{item.date}</Text>
            </View>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center border-b border-gray-200 p-2">
                <TouchableOpacity onPress={() => navigation.goBack()} className="p-2">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="font-bold text-lg ml-2">{currentPost?.user?.name || 'Post'}'s Post</Text>
            </View>

            {loadingPost ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                </View>
            ) : currentPost ? (
                <FlatList
                    data={comments}
                    keyExtractor={(item, index) => index.toString()}
                    renderItem={renderComment}
                    ListHeaderComponent={
                        <View className="mb-2">
                            <PostCard
                                post={currentPost}
                                shouldPlay={true}
                                onUserPress={() => navigation.navigate('PublicProfile', { userId: currentPost.user?.id })}
                            />
                            <Text className="p-3 font-bold text-gray-500 text-sm">Comments</Text>
                        </View>
                    }
                    ListEmptyComponent={
                        !loadingComments ? (
                            <Text className="text-center text-gray-400 py-10">No comments yet. Be the first!</Text>
                        ) : (
                            <ActivityIndicator className="py-10" color="orange" />
                        )
                    }
                    contentContainerStyle={{ paddingBottom: 80 }}
                />
            ) : null}

            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                className="absolute bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-2"
            >
                <View className="flex-row items-center bg-gray-100 rounded-full px-4 py-1">
                    <TextInput
                        className="flex-1 py-2 text-gray-700 max-h-24"
                        placeholder="Write a comment..."
                        value={newComment}
                        onChangeText={setNewComment}
                        multiline
                    />
                    <TouchableOpacity onPress={handleSendComment} disabled={!newComment.trim()}>
                        <Ionicons name="send" size={20} color={newComment.trim() ? '#ea580c' : '#bdc3c7'} />
                    </TouchableOpacity>
                </View>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

export default PostDetailScreen;
