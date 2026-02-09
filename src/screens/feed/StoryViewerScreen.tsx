import React, { useState, useEffect, useRef } from 'react';
import { View, Text, Image, TouchableOpacity, Dimensions, Modal, Animated, StyleSheet, StatusBar, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Video, ResizeMode } from 'expo-av';
import { useRoute, useNavigation } from '@react-navigation/native';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const { width, height } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', ''); // Ensure correct root for uploads
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const StoryViewerScreen = () => {
    const route = useRoute<any>();
    const navigation = useNavigation();
    const { stories: initialStories, initialIndex = 0, userId: storyOwnerId, userName, userPhoto } = route.params;

    const [stories, setStories] = useState(initialStories || []);
    const [currentIndex, setCurrentIndex] = useState(initialIndex);
    const [loading, setLoading] = useState(true);
    const [progress] = useState(new Animated.Value(0));
    const [paused, setPaused] = useState(false);
    const [videoLoaded, setVideoLoaded] = useState(false);
    const [viewerId, setViewerId] = useState<string | null>(null);

    const videoRef = useRef<Video>(null);
    const timerRef = useRef<any>(null);

    useEffect(() => {
        AsyncStorage.getItem('user').then(u => {
            if (u) setViewerId(JSON.parse(u).id);
        });
        setLoading(false);
    }, []);

    useEffect(() => {
        if (!stories[currentIndex]) {
            navigation.goBack();
            return;
        }
        startProgress();
        markAsViewed(stories[currentIndex].id);
    }, [currentIndex]);

    const markAsViewed = async (storyId: number) => {
        if (!viewerId) return;
        try {
            const fd = new FormData();
            fd.append('user_id', viewerId);
            fd.append('story_id', String(storyId));
            await api.post('/story_view.php', fd);
        } catch (e) { console.error(e); }
    };

    const startProgress = (duration = 10000) => {
        // If it's a video, we don't use the timer-based progress unless it fails to load or something
        // But for now, let's keep it for images.
        if (stories[currentIndex]?.type === 'video') return;

        progress.setValue(0);
        Animated.timing(progress, {
            toValue: 1,
            duration: duration,
            useNativeDriver: false,
        }).start(({ finished }) => {
            if (finished) nextStory();
        });
    };

    const nextStory = () => {
        if (currentIndex < stories.length - 1) {
            setCurrentIndex(currentIndex + 1);
        } else {
            navigation.goBack();
        }
    };

    const prevStory = () => {
        if (currentIndex > 0) {
            setCurrentIndex(currentIndex - 1);
        } else {
            setCurrentIndex(0);
            startProgress();
        }
    };

    const handlePress = (evt: any) => {
        const x = evt.nativeEvent.locationX;
        if (x < width / 3) {
            prevStory();
        } else {
            nextStory();
        }
    };

    const deleteStory = async () => {
        const story = stories[currentIndex];
        try {
            const fd = new FormData();
            fd.append('user_id', viewerId!);
            fd.append('story_id', story.id);
            const res = await api.post('/delete_story.php', fd);
            if (res.data.status === 'success') {
                const newStories = stories.filter((s: any) => s.id !== story.id);
                if (newStories.length > 0) {
                    setStories(newStories);
                    setCurrentIndex((prev: number) => (prev >= newStories.length ? 0 : prev));
                } else {
                    navigation.goBack();
                }
            }
        } catch (e) {
            console.error(e);
        }
    };

    const currentStory = stories[currentIndex];
    if (!currentStory) return null;

    const mediaUrl = `${BASE_URL_ROOT}/${currentStory.media}`;
    const isVideo = currentStory.type === 'video';

    // State for viewers
    const [viewers, setViewers] = useState<any[]>([]);
    const [showViewers, setShowViewers] = useState(false);

    useEffect(() => {
        if (String(storyOwnerId) === String(viewerId) && stories[currentIndex]) {
            fetchViewers(stories[currentIndex].id);
        }
    }, [currentIndex, viewerId]);

    const fetchViewers = async (storyId: string) => {
        try {
            const res = await api.get(`/fetch_story_viewers.php?story_id=${storyId}`);
            if (res.data.status === 'success') {
                setViewers(res.data.data);
            }
        } catch (e) { console.error(e); }
    };

    const renderViewersModal = () => (
        <Modal
            animationType="slide"
            transparent={true}
            visible={showViewers}
            onRequestClose={() => setShowViewers(false)}
        >
            <View style={styles.modalOverlay}>
                <View style={styles.modalContent}>
                    <View style={styles.modalHeader}>
                        <Text style={styles.modalTitle}>Story Viewers ({viewers.length})</Text>
                        <TouchableOpacity onPress={() => setShowViewers(false)}>
                            <Ionicons name="close" size={24} color="black" />
                        </TouchableOpacity>
                    </View>
                    <ScrollView contentContainerStyle={{ padding: 16 }}>
                        {viewers.map((item, index) => (
                            <TouchableOpacity
                                key={index}
                                style={styles.viewerItem}
                                onPress={() => {
                                    setShowViewers(false);
                                    (navigation as any).navigate('PublicProfile', { userId: item.user_id });
                                }}
                            >
                                <Image
                                    source={{ uri: item.profile_photo ? `${BASE_URL_ROOT}/uploads/photo/${item.profile_photo}` : 'https://via.placeholder.com/50' }}
                                    style={styles.viewerAvatar}
                                />
                                <View style={{ marginLeft: 12 }}>
                                    <Text style={styles.viewerName}>{item.name}</Text>
                                    <Text style={styles.viewerTime}>{new Date(item.date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
                                </View>
                            </TouchableOpacity>
                        ))}
                        {viewers.length === 0 && <Text style={{ textAlign: 'center', marginTop: 20, color: 'gray' }}>No views yet.</Text>}
                    </ScrollView>
                </View>
            </View>
        </Modal>
    );

    return (
        <View style={styles.container}>
            <StatusBar hidden />

            {/* Progress Bar */}
            <View style={styles.progressContainer}>
                {stories.map((s: any, i: number) => (
                    <View key={i} style={styles.progressBarBackground}>
                        {i === currentIndex ? (
                            <Animated.View style={[styles.progressBarFill, {
                                width: progress.interpolate({
                                    inputRange: [0, 1],
                                    outputRange: ['0%', '100%']
                                })
                            }]} />
                        ) : (
                            <View style={[styles.progressBarFill, { width: i < currentIndex ? '100%' : '0%' }]} />
                        )}
                    </View>
                ))}
            </View>

            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity
                    style={styles.userInfo}
                    onPress={() => {
                        // Navigate to profile. If mine, go to Profile tab? Or just PublicProfile with my ID
                        (navigation as any).navigate('PublicProfile', { userId: storyOwnerId });
                    }}
                >
                    <Image
                        source={{ uri: userPhoto ? `${PHOTO_URL}${userPhoto}` : 'https://via.placeholder.com/40' }}
                        style={styles.avatar}
                    />
                    <Text style={styles.userName}>{userName || 'Story'}</Text>
                    <Text style={styles.time}>{new Date(currentStory.date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
                </TouchableOpacity>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    {String(storyOwnerId) === String(viewerId) && (
                        <TouchableOpacity onPress={deleteStory} style={{ padding: 8 }}>
                            <Ionicons name="trash" size={20} color="white" />
                        </TouchableOpacity>
                    )}
                    <TouchableOpacity onPress={() => navigation.goBack()} style={{ padding: 8 }}>
                        <Ionicons name="close" size={28} color="white" />
                    </TouchableOpacity>
                </View>
            </View>

            {/* Media */}
            <TouchableOpacity activeOpacity={1} onPress={handlePress} style={styles.mediaContainer}>
                {isVideo ? (
                    <Video
                        ref={videoRef}
                        source={{ uri: mediaUrl }}
                        rate={1.0}
                        volume={1.0}
                        isMuted={false}
                        resizeMode={ResizeMode.CONTAIN}
                        shouldPlay={!paused && !showViewers}
                        style={styles.media}
                        onPlaybackStatusUpdate={status => {
                            if (status.isLoaded && status.didJustFinish) nextStory();
                        }}
                    />
                ) : (
                    <Image source={{ uri: mediaUrl }} style={styles.media} resizeMode="contain" />
                )}
            </TouchableOpacity>

            {/* Viewers Footer (Only for Owner) */}
            {String(storyOwnerId) === String(viewerId) && (
                <TouchableOpacity
                    style={styles.footer}
                    onPress={() => {
                        setPaused(true);
                        setShowViewers(true);
                    }}
                >
                    <Ionicons name="eye" size={24} color="white" />
                    <Text style={{ color: 'white', marginLeft: 8, fontWeight: 'bold' }}>{viewers.length} Views</Text>
                    <Ionicons name="chevron-up" size={24} color="white" style={{ marginLeft: 'auto' }} />
                </TouchableOpacity>
            )}

            {renderViewersModal()}
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: 'black' },
    progressContainer: { flexDirection: 'row', paddingTop: 40, paddingHorizontal: 10, height: 44, zIndex: 10 },
    progressBarBackground: { flex: 1, height: 2, backgroundColor: 'rgba(255,255,255,0.3)', marginHorizontal: 2, borderRadius: 2 },
    progressBarFill: { height: 2, backgroundColor: 'white', borderRadius: 2 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 15, position: 'absolute', top: 50, left: 0, right: 0, zIndex: 10 },
    userInfo: { flexDirection: 'row', alignItems: 'center' },
    avatar: { width: 32, height: 32, borderRadius: 16, marginRight: 10 },
    userName: { color: 'white', fontWeight: 'bold', marginRight: 10 },
    time: { color: 'rgba(255,255,255,0.7)', fontSize: 12 },
    mediaContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    media: { width: width, height: height * 0.8 },
    footer: { position: 'absolute', bottom: 40, left: 0, right: 0, paddingHorizontal: 20, flexDirection: 'row', alignItems: 'center', justifyContent: 'center' },

    // Modal Styles
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: 'white', borderTopLeftRadius: 20, borderTopRightRadius: 20, maxHeight: '60%' },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: '#eee' },
    modalTitle: { fontSize: 18, fontWeight: 'bold' },
    viewerItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 16 },
    viewerAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#eee' },
    viewerName: { fontWeight: 'bold', fontSize: 14 },
    viewerTime: { color: 'gray', fontSize: 12 }
});

export default StoryViewerScreen;
