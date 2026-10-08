import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TouchableOpacity, Image, ActivityIndicator, Alert, Dimensions, StyleSheet, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { Video, ResizeMode, Audio } from 'expo-av';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { GestureDetector, Gesture } from 'react-native-gesture-handler';
import Animated, { useSharedValue, useAnimatedStyle, withSpring, runOnJS } from 'react-native-reanimated';
import ViewShot, { captureRef } from 'react-native-view-shot';
import { FlatList, Modal, TextInput } from 'react-native';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

const CreateStoryScreen = ({ navigation }: any) => {
    const [image, setImage] = useState<string | null>(null);
    const [isVideo, setIsVideo] = useState(false);
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState<any>(null);
    const [fitMode, setFitMode] = useState<'cover' | 'contain'>('cover'); // Default to fill/cover
    const [duration, setDuration] = useState(10);
    const [showMusicPicker, setShowMusicPicker] = useState(false);
    const [musicList, setMusicList] = useState<any[]>([]);
    const [selectedMusic, setSelectedMusic] = useState<any>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [musicLoading, setMusicLoading] = useState(false);
    const [sound, setSound] = useState<Audio.Sound | null>(null);

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

        return () => {
            if (sound) {
                sound.unloadAsync();
            }
        };
    }, []);

    const fetchMusic = async (query = '') => {
        setMusicLoading(true);
        try {
            const res = await api.get(`get_music.php?search=${query}`);
            if (res.data.status === 'success') {
                setMusicList(res.data.data);
            }
        } catch (e) {
            console.error("Fetch Music Error:", e);
        } finally {
            setMusicLoading(false);
        }
    };

    const playPreview = async (music: any) => {
        try {
            if (sound) {
                await sound.unloadAsync();
            }
            const { sound: newSound } = await Audio.Sound.createAsync(
                { uri: music.file_url },
                { shouldPlay: true, isLooping: true }
            );
            setSound(newSound);
            setSelectedMusic(music);
        } catch (e) {
            console.error("Play Preview Error:", e);
        }
    };

    const stopPreview = async () => {
        if (sound) {
            await sound.stopAsync();
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images', 'videos'],
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
            formData.append('user_id', String(user.id));
            formData.append('duration', String(duration));
            
            let finalUri = uploadUri;
            if (Platform.OS === 'android' && !finalUri.startsWith('file://') && !finalUri.startsWith('content://')) {
                finalUri = 'file://' + finalUri;
            }

            formData.append('story', {
                uri: finalUri,
                name: filename,
                type: type
            } as any);

            if (selectedMusic) {
                formData.append('music_id', selectedMusic.id);
            }

            console.log("[STORY UPLOAD] Sending to server:", { finalUri, filename, type, userId: user.id });

            const res = await api.post('story_upload.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
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
                    <TouchableOpacity onPress={() => { setShowMusicPicker(true); fetchMusic(); }} style={styles.iconBtn}>
                        <Ionicons name="musical-notes-outline" size={24} color={selectedMusic ? "#ea580c" : "white"} />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={toggleFitMode} style={styles.iconBtn}>
                        <Ionicons name={fitMode === 'cover' ? "resize" : "expand"} size={24} color="white" />
                    </TouchableOpacity>
                    {/* Duration Selector */}
                    {!isVideo && (
                        <View style={styles.headerDurationContainer}>
                            {[5, 10, 15].map(d => (
                                <TouchableOpacity key={d} onPress={() => setDuration(d)} style={[styles.durationItem, duration === d && styles.activeDurationItem]}>
                                    <Text style={[styles.durationText, duration === d && styles.activeDurationText]}>{d}s</Text>
                                </TouchableOpacity>
                            ))}
                        </View>
                    )}
                    <TouchableOpacity onPress={pickImage} style={styles.iconBtn}>
                        <Ionicons name="image-outline" size={24} color="white" />
                    </TouchableOpacity>
                </View>
            </View>

            {/* Selected Music Badge */}
            {selectedMusic && (
                <View style={styles.musicBadge}>
                    <Ionicons name="musical-notes" size={16} color="white" />
                    <Text style={styles.musicBadgeText}>{selectedMusic.title} - {selectedMusic.artist}</Text>
                    <TouchableOpacity onPress={() => { setSelectedMusic(null); sound?.unloadAsync(); }}>
                        <Ionicons name="close-circle" size={18} color="white" />
                    </TouchableOpacity>
                </View>
            )}

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

            {/* Music Picker Modal */}
            <Modal visible={showMusicPicker} animationType="slide" transparent={true}>
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Choose Music</Text>
                            <TouchableOpacity onPress={() => { setShowMusicPicker(false); stopPreview(); }}>
                                <Ionicons name="close" size={28} color="black" />
                            </TouchableOpacity>
                        </View>
                        
                        <View style={styles.searchContainer}>
                            <Ionicons name="search" size={20} color="#666" />
                            <TextInput
                                style={styles.searchInput}
                                placeholder="Search songs, artists..."
                                value={searchQuery}
                                onChangeText={(txt) => { setSearchQuery(txt); fetchMusic(txt); }}
                            />
                        </View>

                        {musicLoading ? <ActivityIndicator style={{ marginTop: 20 }} color="#ea580c" /> : (
                            <FlatList
                                data={musicList}
                                keyExtractor={(item) => item.id.toString()}
                                renderItem={({ item }) => (
                                    <TouchableOpacity 
                                        style={[styles.musicItem, selectedMusic?.id === item.id && styles.selectedMusicItem]}
                                        onPress={() => playPreview(item)}
                                    >
                                        <View style={styles.musicIconCircle}>
                                            <Ionicons name="musical-note" size={20} color="#ea580c" />
                                        </View>
                                        <View style={{ flex: 1, marginLeft: 12 }}>
                                            <Text style={styles.musicTitle}>{item.title}</Text>
                                            <Text style={styles.musicArtist}>{item.artist}</Text>
                                        </View>
                                        {selectedMusic?.id === item.id && <Ionicons name="checkmark-circle" size={24} color="#ea580c" />}
                                    </TouchableOpacity>
                                )}
                                ListEmptyComponent={<Text style={styles.emptyText}>No music found</Text>}
                                contentContainerStyle={{ paddingBottom: 20 }}
                            />
                        )}

                        <TouchableOpacity 
                            style={styles.doneBtn} 
                            onPress={() => { setShowMusicPicker(false); stopPreview(); }}
                        >
                            <Text style={styles.doneBtnText}>Done</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
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
    headerDurationContainer: {
        flexDirection: 'row',
        backgroundColor: 'rgba(0,0,0,0.5)',
        borderRadius: 20,
        padding: 2,
        alignItems: 'center'
    },
    durationItem: {
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 15,
    },
    activeDurationItem: {
        backgroundColor: '#ea580c',
    },
    durationText: { color: 'white', fontSize: 11, fontWeight: 'bold' },
    activeDurationText: { color: 'white' },
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
    },
    musicBadge: {
        position: 'absolute',
        top: 120,
        alignSelf: 'center',
        backgroundColor: 'rgba(0,0,0,0.6)',
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 20,
        gap: 8,
        zIndex: 60
    },
    musicBadgeText: { color: 'white', fontSize: 13, fontWeight: 'bold' },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: 'white', borderTopLeftRadius: 25, borderTopRightRadius: 25, height: '70%', padding: 20 },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
    modalTitle: { fontSize: 20, fontWeight: 'bold' },
    searchContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f0f0f0', borderRadius: 10, paddingHorizontal: 12, marginBottom: 15 },
    searchInput: { flex: 1, paddingVertical: 10, marginLeft: 8, fontSize: 16 },
    musicItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
    selectedMusicItem: { backgroundColor: '#fff7ed' },
    musicIconCircle: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#ffedd5', alignItems: 'center', justifyContent: 'center' },
    musicTitle: { fontSize: 16, fontWeight: 'bold', color: '#333' },
    musicArtist: { fontSize: 13, color: '#666' },
    emptyText: { textAlign: 'center', marginTop: 30, color: '#999' },
    doneBtn: { backgroundColor: '#ea580c', paddingVertical: 15, borderRadius: 15, alignItems: 'center', marginTop: 10 },
    doneBtnText: { color: 'white', fontWeight: 'bold', fontSize: 16 }
});

export default CreateStoryScreen;
