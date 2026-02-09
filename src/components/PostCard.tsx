import React, { useState, useRef, useEffect } from 'react';
import { View, Text, Image, TouchableOpacity, Dimensions, ScrollView, Share, Alert, Linking } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import PostImage from './PostImage';
import { WEBSITE_URL } from '../services/api';
import { Video, ResizeMode } from 'expo-av';
import api from '../services/api';

// Define a type for Post data
export interface PostType {
    id: string;
    user: {
        id: string;
        name: string;
        avatar: string;
        location?: string;
    };
    content: string;
    image?: string;
    video?: string;
    media?: string[];
    likes: number;
    comments: number;
    timeAgo: string;
    isLiked?: boolean;
}

const { width } = Dimensions.get('window');
const POST_MEDIA_PATH = `${WEBSITE_URL}/uploads/posts/`;

const VideoItem = ({ uri, shouldPlay, forwardedRef }: any) => {
    const [duration, setDuration] = useState(0);

    const formatTime = (millis: number) => {
        const totalSeconds = Math.round(millis / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    };

    return (
        <View style={{ width: '100%', height: 300 }}>
            <Video
                ref={forwardedRef}
                source={{ uri }}
                style={{ width: '100%', height: '100%' }}
                resizeMode={ResizeMode.CONTAIN}
                isLooping
                useNativeControls
                shouldPlay={shouldPlay}
                onLoad={(status: any) => {
                    if (status.isLoaded && status.durationMillis) {
                        setDuration(status.durationMillis);
                    }
                }}
            />
            {duration > 0 && (
                <View className="absolute bottom-2 right-2 bg-black/60 px-2 py-1 rounded">
                    <Text className="text-white text-xs font-bold">{formatTime(duration)}</Text>
                </View>
            )}
        </View>
    );
};

const PostCard = ({
    post,
    onUserPress,
    currentUserId,
    onDeletePress,
    shouldPlay = false
}: {
    post: PostType,
    onUserPress?: () => void,
    currentUserId?: string,
    onDeletePress?: () => void,
    shouldPlay?: boolean
}) => {
    const [liked, setLiked] = useState(post.isLiked);
    const [likeCount, setLikeCount] = useState(post.likes);
    const videoRef = useRef<Video>(null);
    const navigation = useNavigation<any>(); // Hook for navigation

    const toggleLike = async () => {
        const newStatus = !liked;
        setLiked(newStatus);
        setLikeCount(prev => newStatus ? prev + 1 : prev - 1);

        try {
            const fd = new FormData();
            fd.append('id', post.id);
            fd.append('user_id', currentUserId || '');
            fd.append('action', 'like');
            await api.post('/like_comment_action.php', fd);
        } catch (e) {
            console.error("Like failed", e);
            setLiked(!newStatus);
            setLikeCount(prev => !newStatus ? prev + 1 : prev - 1);
        }
    };

    const handleShare = async () => {
        try {
            const postUrl = `${WEBSITE_URL}/view_post.php?id=${post.id}`;
            const message = `Check out this post by ${post.user.name} on Sadhu Vandana:\n\n"${post.content?.substring(0, 150)}${post.content && post.content.length > 150 ? '...' : ''}"\n\nView Full Post: ${postUrl}\n\nDownload App: ${WEBSITE_URL}`;
            await Share.share({ message, url: postUrl }); // url param helps on iOS
        } catch (error: any) {
            Alert.alert(error.message);
        }
    };

    const handlePostPress = () => {
        navigation.navigate('PostDetail', { post });
    };

    // Prepare media list
    let mediaList: string[] = [];
    if (post.media && post.media.length > 0) {
        mediaList = post.media.map(file => file.startsWith('http') ? file : `${POST_MEDIA_PATH}${file}`);
    } else if (post.image) {
        mediaList = [post.image.startsWith('http') ? post.image : `${POST_MEDIA_PATH}${post.image}`];
    }

    const isVideo = (uri: string) => {
        return uri.toLowerCase().endsWith('.mp4') || uri.toLowerCase().endsWith('.mov');
    };

    const handleOptions = () => {
        if (!currentUserId) return;
        if (String(post.user.id) === String(currentUserId)) {
            Alert.alert("Manage Post", "What would you like to do?", [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Delete Post",
                    style: "destructive",
                    onPress: () => {
                        Alert.alert("Confirm Delete", "Are you sure you want to delete this post?", [
                            { text: "Cancel", style: "cancel" },
                            { text: "Delete", style: "destructive", onPress: onDeletePress }
                        ]);
                    }
                }
            ]);
        } else {
            Alert.alert("Post Options", "What would you like to do?", [
                { text: "Cancel", style: "cancel" },
                { text: "Report Post", onPress: () => Alert.alert("Reported", "Thanks for letting us know.") }
            ]);
        }
    };

    // Function to render content with clickable links
    const renderContent = (text: string) => {
        if (!text) return null;

        // Regex to detect URLs
        const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)|([a-zA-Z0-9][a-zA-Z0-9-]+\.[a-zA-Z]{2,}[^\s]*)/g;
        const parts = [];
        let lastIndex = 0;
        let match;

        while ((match = urlRegex.exec(text)) !== null) {
            const url = match[0];
            const index = match.index;

            // Add text before the URL
            if (index > lastIndex) {
                parts.push(
                    <Text key={`text-${lastIndex}`} className="text-gray-800">
                        {text.substring(lastIndex, index)}
                    </Text>
                );
            }

            // Add clickable URL
            const fullUrl = url.startsWith('http') ? url : `https://${url}`;
            parts.push(
                <Text
                    key={`link-${index}`}
                    className="text-blue-600 underline"
                    onPress={(e) => {
                        e.stopPropagation();
                        Linking.openURL(fullUrl).catch(err =>
                            Alert.alert('Error', 'Could not open link')
                        );
                    }}
                >
                    {url}
                </Text>
            );

            lastIndex = index + url.length;
        }

        // Add remaining text
        if (lastIndex < text.length) {
            parts.push(
                <Text key={`text-${lastIndex}`} className="text-gray-800">
                    {text.substring(lastIndex)}
                </Text>
            );
        }

        return parts.length > 0 ? <Text className="text-base leading-5 mb-2">{parts}</Text> : <Text className="text-gray-800 text-base leading-5 mb-2">{text}</Text>;
    };

    return (
        <View className="bg-white mb-4 shadow-sm border-b border-gray-100 pb-2">
            {/* Header */}
            <TouchableOpacity className="flex-row items-center p-3" onPress={() => onUserPress && onUserPress()}>
                <Image
                    source={{ uri: post.user.avatar || 'https://via.placeholder.com/50' }}
                    className="w-10 h-10 rounded-full bg-gray-200"
                />
                <View className="ml-3 flex-1">
                    <Text className="font-bold text-gray-900 text-base">{post.user.name}</Text>
                    <Text className="text-gray-500 text-xs">{post.timeAgo}</Text>
                </View>
                <TouchableOpacity className="p-2" onPress={handleOptions}>
                    <Ionicons name="ellipsis-horizontal" size={20} color="gray" />
                </TouchableOpacity>
            </TouchableOpacity>

            {/* Content Body - Clickable to open Detail */}
            <View className="px-3 pb-2">
                {post.content ? (
                    <View>{renderContent(post.content)}</View>
                ) : null}
            </View>

            <TouchableOpacity activeOpacity={0.9} onPress={handlePostPress}>

                {/* Media Carousel */}
                {mediaList.length > 0 && (
                    <View>
                        <ScrollView
                            horizontal
                            pagingEnabled
                            showsHorizontalScrollIndicator={false}
                            contentContainerStyle={{ alignItems: 'center' }}
                        >
                            {mediaList.map((uri, index) => (
                                <View key={index} style={{ width: width, minHeight: 200, backgroundColor: '#f0f0f0', alignItems: 'center', justifyContent: 'center' }}>
                                    {isVideo(uri) ? (
                                        <VideoItem
                                            uri={uri}
                                            shouldPlay={index === 0 && shouldPlay}
                                            forwardedRef={index === 0 ? videoRef : null}
                                        />
                                    ) : (
                                        <PostImage uri={uri} />
                                    )}
                                </View>
                            ))}
                        </ScrollView>
                        {mediaList.length > 1 && (
                            <View className="absolute bottom-2 right-2 bg-black/50 px-2 py-1 rounded-full">
                                <Text className="text-white text-xs font-bold">1/{mediaList.length}</Text>
                            </View>
                        )}
                    </View>
                )}
            </TouchableOpacity>

            {/* Actions */}
            <View className="flex-row items-center justify-between px-4 py-3 border-t border-gray-50 mt-2">
                <View className="flex-row items-center space-x-6">
                    <TouchableOpacity
                        className="flex-row items-center space-x-2 mr-6"
                        onPress={toggleLike}
                    >
                        <Ionicons
                            name={liked ? "heart" : "heart-outline"}
                            size={24}
                            color={liked ? "#ea580c" : "black"}
                        />
                        <Text className={`ml-1 ${liked ? 'text-orange-600 font-medium' : 'text-gray-700'}`}>
                            {likeCount}
                        </Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        className="flex-row items-center space-x-2"
                        onPress={handlePostPress}
                    >
                        <Ionicons name="chatbubble-outline" size={23} color="black" />
                        <Text className="text-gray-700 ml-1">{post.comments}</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        className="flex-row items-center space-x-2 ml-4"
                        onPress={handleShare}
                    >
                        <Ionicons name="paper-plane-outline" size={23} color="black" />
                    </TouchableOpacity>
                </View>

                <TouchableOpacity>
                    <Ionicons name="bookmark-outline" size={22} color="black" />
                </TouchableOpacity>
            </View>
        </View>
    );
};

export default PostCard;
