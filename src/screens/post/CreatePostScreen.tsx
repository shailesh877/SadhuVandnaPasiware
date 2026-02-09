import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, Image, ScrollView, Alert, ActivityIndicator, KeyboardAvoidingView, Platform, Dimensions } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

// FIX logic to match ProfileScreen: uploads are at root, not inside Api folder
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const { height } = Dimensions.get('window');

const CreatePostScreen = ({ navigation }: any) => {
    const [content, setContent] = useState('');
    const [images, setImages] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        const load = async () => {
            const u = await AsyncStorage.getItem('user');
            if (u) setUser(JSON.parse(u));
        };
        load();
    }, []);

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.All,
            allowsMultipleSelection: true,
            selectionLimit: 10,
            allowsEditing: false,
            // aspect: [4, 3], // Aspect ratio doesn't apply to videos or multiple selection well
            quality: 0.8,
        });

        if (!result.canceled) {
            const newUris = result.assets.map(asset => asset.uri);
            setImages(prev => [...prev, ...newUris]);
        }
    };

    const removeImage = (index: number) => {
        setImages(prev => prev.filter((_, i) => i !== index));
    };

    const handlePost = async () => {
        if (!content && images.length === 0) {
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('user_id', user?.id);
            formData.append('description', content);

            images.forEach((uri, index) => {
                const filename = uri.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                let type = match ? `image/${match[1]}` : `image/jpeg`;

                // Simple check for video extension to set correct mime type
                if (filename?.toLowerCase().endsWith('.mp4') || filename?.toLowerCase().endsWith('.mov')) {
                    type = 'video/mp4';
                }

                formData.append('media[]', { uri, name: filename, type } as any);
            });

            const res = await api.post('/create_post.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'success') {
                navigation.goBack();
                Alert.alert("Success", "Post shared successfully");
            } else {
                Alert.alert("Error", res.data.message || "Failed to share post");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            {/* Header */}
            <View className="flex-row justify-between items-center px-4 py-3 border-b border-gray-100">
                <View className="flex-row items-center">
                    <TouchableOpacity onPress={() => navigation.goBack()} className="mr-4">
                        <Ionicons name="arrow-back" size={24} color="black" />
                    </TouchableOpacity>
                    <Text className="text-xl text-gray-900 font-normal">Create Post</Text>
                </View>
                <TouchableOpacity
                    className={`px-5 py-2 rounded-md ${content || images.length > 0 ? 'bg-orange-600' : 'bg-gray-200'}`}
                    disabled={(!content && images.length === 0) || loading}
                    onPress={handlePost}
                >
                    {loading ? <ActivityIndicator size="small" color="#fff" /> :
                        <Text className={`font-bold text-base ${content || images.length > 0 ? 'text-white' : 'text-gray-500'}`}>POST</Text>}
                </TouchableOpacity>
            </View>

            <ScrollView className="flex-1 p-4">
                <View className="flex-row mb-4 items-center">
                    <View className="w-12 h-12 rounded-full overflow-hidden bg-gray-200 mr-3">
                        {user?.profile_photo ? (
                            <Image source={{ uri: `${PHOTO_URL}${user.profile_photo}` }} className="w-full h-full" />
                        ) : (
                            <View className="w-full h-full justify-center items-center bg-orange-200">
                                <Text className="text-orange-800 font-bold text-lg">{user?.name?.[0]}</Text>
                            </View>
                        )}
                    </View>
                    <View>
                        <Text className="font-bold text-gray-900 text-lg">{user?.name || 'User'}</Text>
                        <View className="bg-gray-100 px-2 py-0.5 rounded-md self-start border border-gray-300 mt-1 flex-row items-center">
                            <Ionicons name="earth" size={12} color="gray" />
                            <Text className="text-xs text-gray-600 ml-1">Public</Text>
                        </View>
                    </View>
                </View>

                <TextInput
                    className="text-xl text-gray-900 mb-6"
                    placeholder={`What's on your mind?`}
                    placeholderTextColor="#666"
                    multiline
                    value={content}
                    onChangeText={setContent}
                    textAlignVertical="top"
                    autoFocus
                    style={{ minHeight: height * 0.15 }}
                />

                {images.length > 0 && (
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mb-20">
                        {images.map((img, index) => (
                            <View key={index} className="relative mr-3 rounded-xl overflow-hidden border border-gray-200">
                                <Image source={{ uri: img }} className="w-64 h-48" resizeMode="cover" />
                                {img.toLowerCase().endsWith('.mp4') || img.toLowerCase().endsWith('.mov') ? (
                                    <View className="absolute inset-0 items-center justify-center bg-black/30">
                                        <Ionicons name="play-circle" size={40} color="white" />
                                    </View>
                                ) : null}
                                <TouchableOpacity
                                    className="absolute top-2 right-2 bg-black/60 p-1.5 rounded-full"
                                    onPress={() => removeImage(index)}
                                >
                                    <Ionicons name="close" size={20} color="white" />
                                </TouchableOpacity>
                            </View>
                        ))}
                    </ScrollView>
                )}
            </ScrollView>

            {/* Bottom Actions */}
            <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={10}>
                <View className="border-t border-gray-200 p-2">
                    <TouchableOpacity
                        className="flex-row items-center py-4 px-2 active:bg-gray-100 rounded-lg"
                        onPress={pickImage}
                    >
                        <Ionicons name="images" size={28} color="#45bd62" />
                        <Text className="ml-4 font-medium text-gray-700 text-base">Photo/Video</Text>
                    </TouchableOpacity>
                </View>
            </KeyboardAvoidingView>

        </SafeAreaView>
    );
};

export default CreatePostScreen;
