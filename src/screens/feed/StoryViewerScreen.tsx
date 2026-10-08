import React, { useState, useEffect, useRef } from 'react';
import { View, Text, Image, TouchableOpacity, Dimensions, Modal, Animated, StyleSheet, StatusBar, ScrollView, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Video, ResizeMode, Audio, InterruptionModeIOS, InterruptionModeAndroid } from 'expo-av';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const { width, height } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', ''); // Ensure correct root for uploads
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const StoryViewerScreen = ({ navigation, route }: any) => {
    const { stories: initialStories, initialIndex = 0, userId: storyOwnerId, userName, userPhoto } = route.params;

    const [stories, setStories] = useState(initialStories || []);
    const [currentIndex, setCurrentIndex] = useState(initialIndex);
    const [loading, setLoading] = useState(true);
    const [progress] = useState(new Animated.Value(0));
    const [paused, setPaused] = useState(false);
    const [viewerId, setViewerId] = useState<string | null>(null);
    const soundRef = useRef<Audio.Sound | null>(null);
    const [isMusicPlaying, setIsMusicPlaying] = useState(false);

    const videoRef = useRef<Video>(null);
    const timerRef = useRef<any>(null);

    useEffect(() => {
        AsyncStorage.getItem('user').then(u => {
            if (u) setViewerId(JSON.parse(u).id);
        });
        setLoading(false);

        return () => {
            console.log("[StoryViewer] Cleaning up sound on unmount");
            if (soundRef.current) {
                const s = soundRef.current;
                soundRef.current = null;
                s.stopAsync()
                    .then(() => s.unloadAsync())
                    .catch(() => {});
            }
        };
    }, []);

    useEffect(() => {
        const story = stories[currentIndex];
        console.log(`[StoryViewer] Story ${currentIndex} music_url:`, story?.music_url);
        if (story?.music_url) {
            loadAndPlayMusic(story.music_url);
        } else {
            stopMusic();
        }
    }, [currentIndex]);

    const loadAndPlayMusic = async (url: string) => {
        try {
            if (soundRef.current) {
                await soundRef.current.unloadAsync();
                soundRef.current = null;
            }
            const { sound: newSound, status } = await Audio.Sound.createAsync(
                { uri: url },
                { shouldPlay: !paused, isLooping: true }
            );
            console.log(`[StoryViewer] Music loaded. Status:`, status.isLoaded);
            soundRef.current = newSound;
            setIsMusicPlaying(true);
        } catch (e: any) {
            console.error("Error loading music:", e);
            Alert.alert("Music Error", "Failed to load: " + e.message);
        }
    };

    const stopMusic = async () => {
        if (soundRef.current) {
            const s = soundRef.current;
            soundRef.current = null;
            setIsMusicPlaying(false);
            try {
                await s.stopAsync();
                await s.unloadAsync();
            } catch (e) {}
        }
    };

    useEffect(() => {
        if (!stories[currentIndex]) {
            navigation.goBack();
            return;
        }
        startProgress();
    }, [currentIndex]);

    const markAsViewed = async (storyId: number) => {
        if (!viewerId) return;
        try {
            const formData = new FormData();
            formData.append('user_id', viewerId!);
            formData.append('story_id', String(storyId));
            
            await api.post('story_view.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } catch (e) { console.error(e); }
    };

    const startProgress = (fixedDuration?: number) => {
        // If it's a video, we don't use the timer-based progress unless it fails to load or something
        if (stories[currentIndex]?.type === 'video') return;

        const storyDuration = stories[currentIndex]?.duration ? parseInt(stories[currentIndex].duration) * 1000 : 10000;
        const duration = fixedDuration || storyDuration;

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
            const formData = new FormData();
            formData.append('user_id', viewerId!);
            formData.append('story_id', String(story.id));

            const res = await api.post('delete_story.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            console.log("[DELETE STORY RESPONSE]", res.data);
            
            if (res.data.status === 'success') {
                const newStories = stories.filter((s: any) => s.id !== story.id);
                if (newStories.length > 0) {
                    setStories(newStories);
                    setCurrentIndex((prev: number) => (prev >= newStories.length ? 0 : prev));
                } else {
                    navigation.goBack();
                }
            } else {
                import('react-native').then(({ Alert }) => {
                    Alert.alert("Delete Failed", res.data.message || "Could not delete story");
                });
            }
        } catch (e) {
            console.error(e);
            import('react-native').then(({ Alert }) => {
                Alert.alert("Error", "Network error while deleting");
            });
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
        if (soundRef.current) {
            if (paused || showViewers) {
                soundRef.current.pauseAsync();
            } else {
                soundRef.current.playAsync();
            }
        }
    }, [paused, showViewers]);

    useEffect(() => {
        if (viewerId && stories[currentIndex]) {
            if (String(storyOwnerId) !== String(viewerId)) {
                markAsViewed(stories[currentIndex].id);
            } else {
                fetchViewers(stories[currentIndex].id);
            }
        }
    }, [currentIndex, viewerId]);

    const fetchViewers = async (storyId: string) => {
        try {
            const res = await api.get(`fetch_story_viewers.php?story_id=${storyId}`);
            if (res.data.status === 'success') {
                setViewers(res.data.data);
            }
        } catch (e) { console.error(e); }
    };

    const formatViewerTime = (dateString: string) => {
        if (!dateString) return '';
        try {
            // Simple split for MySQL datetime "YYYY-MM-DD HH:mm:ss"
            const parts = dateString.split(' ');
            if (parts.length === 2) {
                const timeParts = parts[1].split(':');
                let hours = parseInt(timeParts[0], 10);
                const minutes = timeParts[1];
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                return `${hours}:${minutes} ${ampm}`;
            }
            return dateString;
        } catch {
            return dateString;
        }
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
                                    <Text style={styles.viewerTime}>{formatViewerTime(item.date)}</Text>
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

                {currentStory.music_title && (
                    <View style={styles.musicLabel}>
                        <Ionicons name="musical-notes" size={14} color="white" />
                        <Text style={styles.musicLabelText} numberOfLines={1}>
                            {currentStory.music_title} - {currentStory.music_artist}
                        </Text>
                    </View>
                )}
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
                            if (status.isLoaded) {
                                if (status.durationMillis && status.positionMillis) {
                                    // Update progress animated value directly
                                    progress.setValue(status.positionMillis / status.durationMillis);
                                }
                                if (status.didJustFinish) nextStory();
                            }
                        }}
                        onError={(e) => {
                            console.error("[StoryViewer Video] Error:", e, "URI:", mediaUrl);
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
    musicLabel: {
        position: 'absolute',
        top: 100,
        left: 20,
        right: 20,
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.3)',
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 15,
        alignSelf: 'flex-start'
    },
    musicLabelText: { color: 'white', fontSize: 12, marginLeft: 6, fontWeight: '500' },
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
