import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TouchableOpacity, Image, ActivityIndicator, Alert, Dimensions, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { Video, ResizeMode } from 'expo-av';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { GestureDetector, Gesture } from 'react-native-gesture-handler';
import Animated, { useSharedValue, useAnimatedStyle, withSpring, runOnJS } from 'react-native-reanimated';
import ViewShot, { captureRef } from 'react-native-view-shot';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

const CreateStoryScreen = ({ navigation }: any) => {
    const [image, setImage] = useState<string | null>(null);
    const [isVideo, setIsVideo] = useState(false);
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState<any>(null);
    const [fitMode, setFitMode] = useState<'cover' | 'contain'>('cover'); // Default to fill/cover

    const viewShotRef = useRef<ViewShot>(null);

    // Reanimated Shared Values
    const scale = useSharedValue(1);
    const savedScale = useSharedValue(1);
    const translateX = useSharedValue(0);
    const translateY = useSharedValue(0);
    const savedTranslateX = useSharedValue(0);
    const savedTranslateY = useSharedValue(0);

    useEffect(() => {
        AsyncStorage.getItem('user').then(u => {
            if (u) setUser(JSON.parse(u));
        });
        pickImage();
    }, []);

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.All,
            allowsEditing: false, // We handle editing
            quality: 1,
        });

        if (!result.canceled) {
            const asset = result.assets[0];
            setImage(asset.uri);

            // Check if it's a video
            if (asset.type === 'video' || asset.uri.toLowerCase().endsWith('.mp4') || asset.uri.toLowerCase().endsWith('.mov')) {
                setIsVideo(true);
            } else {
                setIsVideo(false);
            }

            resetTransform();
        } else if (!image) {
            navigation.goBack();
        }
    };

    const resetTransform = () => {
        scale.value = 1;
        savedScale.value = 1;
        translateX.value = 0;
        translateY.value = 0;
        savedTranslateX.value = 0;
        savedTranslateY.value = 0;
    };

    const panGesture = Gesture.Pan()
        .onUpdate((e) => {
            translateX.value = savedTranslateX.value + e.translationX;
            translateY.value = savedTranslateY.value + e.translationY;
        })
        .onEnd(() => {
            savedTranslateX.value = translateX.value;
            savedTranslateY.value = translateY.value;
        });

    const pinchGesture = Gesture.Pinch()
        .onUpdate((e) => {
            scale.value = savedScale.value * e.scale;
        })
        .onEnd(() => {
            savedScale.value = scale.value;
        });

    const composedGesture = Gesture.Simultaneous(panGesture, pinchGesture);

    const animatedStyle = useAnimatedStyle(() => ({
        transform: [
            { translateX: translateX.value },
            { translateY: translateY.value },
            { scale: scale.value }
        ]
    }));

    const toggleFitMode = () => {
        setFitMode(prev => prev === 'cover' ? 'contain' : 'cover');
        resetTransform();
    };

    const handleUpload = async () => {
        if (!image || !user?.id) return;
        setLoading(true);

        try {
            let uploadUri = image;
            let filename = 'story.jpg';
            let type = 'image/jpeg';

            // If it's an image, we capture the edits. If video, we upload raw file.
            if (!isVideo) {
                const capturedUri = await captureRef(viewShotRef, {
                    format: 'jpg',
                    quality: 0.8,
                    result: 'tmpfile'
                });
                uploadUri = capturedUri;
            } else {
                // For video, use original URI
                filename = 'story.mp4';
                type = 'video/mp4';

                // Try to preserve original filename/extension if possible
                const originalName = image.split('/').pop();
                if (originalName) {
                    filename = originalName;
                    const ext = originalName.split('.').pop();
                    if (ext) type = `video/${ext}`;
                }
            }

            const formData = new FormData();
            formData.append('user_id', user.id);
            formData.append('story', {
                uri: uploadUri,
                name: filename,
                type: type
            } as any);

            const res = await api.post('/story_upload.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("Success", "Story uploaded successfully");
                navigation.goBack();
            } else {
                Alert.alert("Error", res.data.message || "Failed to upload");
            }

        } catch (e: any) {
            console.error(e);
            Alert.alert("Error", "Upload failed");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.iconBtn}>
                    <Ionicons name="close" size={28} color="white" />
                </TouchableOpacity>
                <View style={{ flexDirection: 'row', gap: 15 }}>
                    <TouchableOpacity onPress={toggleFitMode} style={styles.iconBtn}>
                        <Ionicons name={fitMode === 'cover' ? "resize" : "expand"} size={24} color="white" />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={pickImage} style={styles.iconBtn}>
                        <Ionicons name="image-outline" size={24} color="white" />
                    </TouchableOpacity>
                </View>
            </View>

            {/* Editor Area */}
            <View style={styles.editorContainer}>
                <ViewShot ref={viewShotRef} style={{ flex: 1, backgroundColor: 'black', overflow: 'hidden' }} options={{ result: 'tmpfile' }}>
                    <GestureDetector gesture={composedGesture}>
                        <Animated.View style={[{ flex: 1 }, animatedStyle]}>
                            {image && (
                                isVideo ? (
                                    <Video
                                        source={{ uri: image }}
                                        style={{
                                            width: '100%',
                                            height: '100%',
                                        }}
                                        resizeMode={fitMode === 'cover' ? ResizeMode.COVER : ResizeMode.CONTAIN}
                                        shouldPlay={true}
                                        isLooping={true}
                                        isMuted={true} // Muted in preview
                                    />
                                ) : (
                                    <Image
                                        source={{ uri: image }}
                                        style={{
                                            width: '100%',
                                            height: '100%',
                                        }}
                                        resizeMode={fitMode}
                                    />
                                )
                            )}
                        </Animated.View>
                    </GestureDetector>
                </ViewShot>

                {/* Grid Overlay (Optional visual guide) */}
                <View style={styles.gridOverlay} pointerEvents="none" />
            </View>

            {/* Footer */}
            <View style={styles.footer}>
                <TouchableOpacity onPress={handleUpload} disabled={loading} style={styles.uploadBtn}>
                    {loading ? <ActivityIndicator color="white" /> : (
                        <>
                            <Text style={styles.btnText}>Your Story</Text>
                            <Ionicons name="chevron-forward" size={20} color="white" />
                        </>
                    )}
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: 'black' },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingTop: 10,
        zIndex: 50
    },
    iconBtn: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: 'rgba(0,0,0,0.5)',
        alignItems: 'center',
        justifyContent: 'center'
    },
    editorContainer: {
        flex: 1,
        marginTop: 10,
        marginBottom: 80, // space for footer
        borderRadius: 15,
        overflow: 'hidden',
        marginHorizontal: 0, // full width
    },
    gridOverlay: {
        ...StyleSheet.absoluteFillObject,
        borderWidth: 0,
        borderColor: 'rgba(255,255,255,0.1)'
    },
    footer: {
        position: 'absolute',
        bottom: 20,
        right: 20,
        left: 20,
        flexDirection: 'row',
        justifyContent: 'flex-end',
        alignItems: 'center'
    },
    uploadBtn: {
        flexDirection: 'row',
        backgroundColor: 'white',
        paddingVertical: 12,
        paddingHorizontal: 20,
        borderRadius: 30,
        alignItems: 'center',
        gap: 5
    },
    btnText: {
        color: 'black',
        fontWeight: 'bold',
        fontSize: 16
    }
});

export default CreateStoryScreen;
