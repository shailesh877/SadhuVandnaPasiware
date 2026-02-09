import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, Image, KeyboardAvoidingView, Platform, ActivityIndicator, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const CommentsScreen = ({ route, navigation }: any) => {
    const { postId } = route.params;
    const [comments, setComments] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [newComment, setNewComment] = useState('');
    const [sending, setSending] = useState(false);
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        loadUser();
        fetchComments();
    }, []);

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) setUser(JSON.parse(u));
    };

    const fetchComments = async () => {
        try {
            // Updated to match `get_posts.php` logic
            const formData = new FormData();
            formData.append('action', 'fetch_comments');
            formData.append('id', postId); // PHP expects 'id' as post_id

            const res = await api.post('/get_posts.php', formData);

            if (Array.isArray(res.data)) {
                setComments(res.data);
            } else if (res.data.status === 'success' && Array.isArray(res.data.data)) {
                setComments(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleSend = async () => {
        if (!newComment.trim()) return;
        if (!user) {
            Alert.alert("Login Required", "Please login to comment.");
            return;
        }

        setSending(true);
        try {
            const formData = new FormData();
            formData.append('action', 'comment');
            formData.append('id', postId);
            formData.append('user_id', user.id);
            formData.append('comment', newComment);

            const res = await api.post('/get_posts.php', formData);
            if (res.data.ok || res.data.status === 'success' || res.data.ok === true) {
                setNewComment('');
                fetchComments(); // Refresh list
            } else {
                Alert.alert("Error", "Failed to post comment");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setSending(false);
        }
    };

    const renderItem = ({ item }: { item: any }) => (
        <View className="flex-row mb-4">
            <Image
                source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/50' }}
                className="w-10 h-10 rounded-full bg-gray-200 mr-3"
            />
            <View className="flex-1 bg-gray-100 rounded-xl p-3">
                <View className="flex-row justify-between items-center mb-1">
                    <Text className="font-bold text-gray-800 text-sm">{item.name}</Text>
                    <Text className="text-xs text-gray-500">{item.date}</Text>
                </View>
                <Text className="text-gray-700 leading-5">{item.comment}</Text>
            </View>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-lg font-bold text-gray-800">Comments</Text>
            </View>

            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 10 : 0}
                className="flex-1"
                enabled={Platform.OS === 'ios'}
            >
                {loading ? (
                    <ActivityIndicator size="large" color="#ea580c" className="mt-10" />
                ) : (
                    <FlatList
                        data={comments}
                        renderItem={renderItem}
                        keyExtractor={(item, index) => index.toString()}
                        contentContainerStyle={{ padding: 16 }}
                        ListEmptyComponent={
                            <View className="items-center mt-10">
                                <Text className="text-gray-400">No comments yet. Be the first!</Text>
                            </View>
                        }
                    />
                )}

                <View className="p-3 bg-white border-t border-gray-100 flex-row items-center">
                    <TextInput
                        className="flex-1 bg-gray-100 rounded-full px-4 py-3 mr-2 text-base text-gray-800"
                        placeholder="Write a comment..."
                        placeholderTextColor="#9ca3af"
                        value={newComment}
                        onChangeText={setNewComment}
                        returnKeyType="send"
                        onSubmitEditing={handleSend}
                    />
                    <TouchableOpacity
                        onPress={handleSend}
                        disabled={!newComment.trim() || sending}
                        className={`p-3 rounded-full ${newComment.trim() ? "bg-orange-600" : "bg-gray-200"}`}
                    >
                        {sending ? (
                            <ActivityIndicator color="white" size="small" />
                        ) : (
                            <Ionicons name="send" size={20} color={newComment.trim() ? "white" : "gray"} />
                        )}
                    </TouchableOpacity>
                </View>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

export default CommentsScreen;
