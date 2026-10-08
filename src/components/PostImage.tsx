import React, { useState, useEffect, useRef } from 'react';
import { Image, View, ActivityIndicator, Dimensions, Text, Animated } from 'react-native';
import { Video, ResizeMode } from 'expo-av';

interface PostImageProps {
    uri: string;
}

const { width } = Dimensions.get('window');

const PostImage: React.FC<PostImageProps> = ({ uri }) => {
    const [aspectRatio, setAspectRatio] = useState<number>(4 / 3); // Default
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const imageOpacity = useRef(new Animated.Value(0)).current;

    useEffect(() => {
        if (!uri) {
            setLoading(false);
            return;
        }

        // Skip size calculation for videos
        const isVideo = uri.toLowerCase().match(/\.(mp4|mov|avi|wmv|flv|mkv)$/i);
        if (isVideo) {
            setLoading(false);
            return;
        }

        Image.getSize(
            uri,
            (w, h) => {
                if (w && h) {
                    setAspectRatio(w / h);
                }
                setLoading(false);
            },
            (err) => {
                // console.warn("Failed to get image size", uri);
                setLoading(false);
                setError(true);
            }
        );
    }, [uri]);

    const handleLoad = () => {
        Animated.timing(imageOpacity, {
            toValue: 1,
            duration: 300,
            useNativeDriver: true,
        }).start();
    };

    if (error) {
        return (
            <View className="w-full h-64 bg-gray-200 rounded-xl justify-center items-center">
                <Text className="text-gray-500 text-xs">Image not available</Text>
            </View>
        );
    }

    // Safely check for video extension
    const isVideo = uri ? uri.toLowerCase().match(/\.(mp4|mov|avi|wmv|flv|mkv)$/i) : null;

    if (isVideo) {
        return (
            <View className="w-full bg-black rounded-xl overflow-hidden relative" style={{ aspectRatio: 4 / 3 }}>
                <Video
                    source={{ uri }}
                    rate={1.0}
                    volume={1.0}
                    isMuted={false}
                    resizeMode={ResizeMode.CONTAIN}
                    shouldPlay={false}
                    useNativeControls
                    style={{ width: '100%', height: '100%' }}
                    posterSource={{ uri: 'https://via.placeholder.com/300x200?text=Video' }}
                    usePoster
                />
            </View>
        );
    }

    return (
        <View className="w-full bg-gray-50 rounded-xl overflow-hidden relative">
            <Animated.Image
                source={{ uri }}
                style={{ width: '100%', aspectRatio, opacity: imageOpacity }}
                resizeMode="cover"
                onLoad={handleLoad}
                onError={() => setError(true)}
            />
            {loading && (
                <View className="absolute inset-0 justify-center items-center bg-gray-100">
                    <ActivityIndicator color="#ea580c" />
                </View>
            )}
        </View>
    );
};

export default PostImage;
