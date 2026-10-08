import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { View, Text, TextInput, TouchableOpacity, Image, ScrollView, Alert, ActivityIndicator, KeyboardAvoidingView, Platform, Dimensions, Modal, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import { Video, ResizeMode, Audio } from 'expo-av';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

const { width, height } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const CreatePostScreen = ({ navigation, route }: any) => {
    const params = route.params || {};
    // Node.js server for audio streams (Agora/ytdl)
    const NODE_SERVER = __DEV__ ? 'http://192.168.31.124:3000' : 'https://www.sadhuvandna.co.in';
    const [content, setContent] = useState('');
    const [images, setImages] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState<any>(null);
    const [selectedMusic, setSelectedMusic] = useState<any>(null);
    const [startTime, setStartTime] = useState(0);
    const [musicModalVisible, setMusicModalVisible] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<any[]>([]);
    const [searching, setSearching] = useState(false);
    const [playingId, setPlayingId] = useState<string | null>(null);
    const [isPreviewPlaying, setIsPreviewPlaying] = useState(true);
    const [musicDuration, setMusicDuration] = useState(30);
    const musicDurationRef = useRef(30);
    const startTimeRef = useRef(0);
    const [playbackTime, setPlaybackTime] = useState(0);
    const [recordingSpeed, setRecordingSpeed] = useState(1);
    const [isScrolling, setIsScrolling] = useState(false);

    const YOUTUBE_API_KEY = 'AIzaSyB7wJ4Dl0-ClqgDwqrTLOJJYsAweo1QX5w';
    const [isAudioLoading, setIsAudioLoading] = useState(false);
    const soundRef = useRef<Audio.Sound | null>(null);
    const scrollRef = useRef<ScrollView>(null);

    const WAVEFORM_DATA = useRef(Array.from({ length: 151 }).map((_, i) => ({
        height: 15 + (Math.sin(i * 0.3) * 12) + (Math.cos(i * 0.7) * 8) + 10,
        id: i
    }))).current;

    useEffect(() => {
        if (musicModalVisible && searchResults.length === 0) {
            searchMusic('');
        }
    }, [musicModalVisible]);

    useEffect(() => {
        const load = async () => {
            const u = await AsyncStorage.getItem('user');
            if (u) setUser(JSON.parse(u));
        };
        load();
    }, []);

    useEffect(() => {
        if (params.videoUri) {
            setImages([params.videoUri]);
        }
        if (params.preSelectedMusic) {
            setSelectedMusic(params.preSelectedMusic);
            loadAndPlayAudio(params.preSelectedMusic, params.isReel ? false : true);
        }
        if (params.isReel && params.recordedDuration) {
            setMusicDuration(params.recordedDuration);
            musicDurationRef.current = params.recordedDuration;
        }
        if (params.recordingSpeed) {
            setRecordingSpeed(params.recordingSpeed);
        }
    }, [params.videoUri, params.preSelectedMusic, params.recordedDuration, params.recordingSpeed, params.filterId]);

    useEffect(() => { musicDurationRef.current = musicDuration; }, [musicDuration]);
    useEffect(() => { startTimeRef.current = startTime; }, [startTime]);

    // Audio Management
    useEffect(() => {
        return () => {
            if (soundRef.current) {
                soundRef.current.unloadAsync();
            }
        };
    }, []);

    const onPlaybackStatusUpdate = (status: any) => {
        if (status.isLoaded) {
            if (status.isPlaying) {
                const currentPos = status.positionMillis / 1000;
                setPlaybackTime(currentPos);

                if (selectedMusic && !isScrolling) {
                    const start = startTimeRef.current;
                    const duration = musicDurationRef.current;
                    const endPos = start + duration;

                    // Only loop if we are actually past the segment and duration is valid
                    if (duration > 0 && currentPos >= endPos) {
                        soundRef.current?.setPositionAsync(start * 1000);
                        console.log(`[Audio] Looping back to: ${start}`);
                    }

                    // Sync waveform scroll
                    if (scrollRef.current) {
                        scrollRef.current.scrollTo({ x: currentPos * 12, animated: false });
                    }
                }
            }
        }
    };

    useEffect(() => {
        if (selectedMusic) {
            startTimeRef.current = 0;
            setStartTime(0);

            // For Reels, don't auto-play on enter to avoid background sound confusion
            const shouldAutoPlay = params.isReel ? false : true;
            setIsPreviewPlaying(shouldAutoPlay);
            loadAndPlayAudio(selectedMusic, shouldAutoPlay);
        } else {
            if (soundRef.current) {
                soundRef.current.unloadAsync();
                soundRef.current = null;
            }
        }
    }, [selectedMusic?.id]);

    const loadAndPlayAudio = async (musicObj: any, autoPlay: boolean = true) => {
        try {
            setIsAudioLoading(true);
            if (autoPlay) setIsPreviewPlaying(true);

            const streamUrl = musicObj.file_url;
            console.log(`[Audio] Loading: ${streamUrl} | AutoPlay: ${autoPlay}`);

            if (soundRef.current) {
                await soundRef.current.unloadAsync();
                soundRef.current = null;
            }

            if (!streamUrl) throw new Error("No audio URL found");

            const { sound } = await Audio.Sound.createAsync(
                { uri: streamUrl },
                {
                    shouldPlay: autoPlay,
                    positionMillis: startTimeRef.current * 1000,
                    volume: 1.0,
                },
                onPlaybackStatusUpdate
            );

            soundRef.current = sound;
            console.log("[Audio] Success");
        } catch (error: any) {
            console.error("Audio Load Error:", error.message);
        } finally {
            setIsAudioLoading(false);
        }
    };

    useEffect(() => {
        if (soundRef.current) {
            if (isPreviewPlaying) {
                soundRef.current.playAsync();
            } else {
                soundRef.current.pauseAsync();
            }
        }
    }, [isPreviewPlaying]);

    const searchMusic = async (overrideQuery?: string) => {
        const query = overrideQuery !== undefined ? overrideQuery : searchQuery;
        try {
            setSearching(true);
            const response = await axios.get(`${API_BASE_URL}get_music.php?search=${query}`);
            if (response.data.status === 'success') {
                setSearchResults(response.data.data);
            }
        } catch (error) {
            console.error("Search Error:", error);
            setSearchResults([]);
        } finally {
            setSearching(false);
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.All,
            allowsEditing: false,
            quality: 0.8,
        });

        if (!result.canceled) {
            setImages(prev => [...prev, ...result.assets.map(a => a.uri)]);
        }
    };

    const removeImage = (index: number) => {
        setImages(prev => prev.filter((_, i) => i !== index));
    };

    const handlePost = async () => {
        if (!content && images.length === 0) return;
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('user_id', user?.id);
            formData.append('status', content);
            if (selectedMusic || params.filterId) {
                formData.append('link', JSON.stringify({
                    id: selectedMusic?.id || '',
                    title: selectedMusic?.title || '',
                    artist: selectedMusic?.artist || '',
                    thumbnail: selectedMusic?.thumbnail || '',
                    music_url: selectedMusic?.file_url || '',
                    startTime: startTime,
                    duration: musicDuration,
                    type: selectedMusic ? 'custom_music' : 'none',
                    filterId: params.filterId || 'none'
                }));
            }
            images.forEach((uri, index) => {
                const filename = uri.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                let type = match ? `image/${match[1]}` : `image/jpeg`;
                if (filename?.toLowerCase().endsWith('.mp4')) type = 'video/mp4';
                formData.append('media[]', { uri, name: filename, type } as any);
            });
            const res = await api.post('create_post.php', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            if (res.data.status === 'success') {
                navigation.goBack();
                Alert.alert("Success", "Post shared successfully");
            } else {
                Alert.alert("Error", res.data.message || "Failed to share post");
            }
        } catch (error) {
            Alert.alert("Error", "Network request failed");
        } finally {
            setLoading(false);
        }
    };

    const PREVIEW_JS = ''; // No longer used

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }} edges={['top']}>
            <View style={styles.header}>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={{ marginRight: 16 }}>
                        <Ionicons name="arrow-back" size={24} color="black" />
                    </TouchableOpacity>
                    <Text style={{ fontSize: 20, color: '#111827', fontWeight: '400' }}>Create Post</Text>
                </View>
                <TouchableOpacity
                    style={[styles.postButton, (content || images.length > 0) ? styles.postButtonActive : styles.postButtonDisabled]}
                    disabled={(!content && images.length === 0) || loading}
                    onPress={handlePost}
                >
                    {loading ? <ActivityIndicator size="small" color="#fff" /> :
                        <Text style={[styles.postButtonText, (content || images.length > 0) ? { color: 'white' } : { color: '#6b7280' }]}>POST</Text>}
                </TouchableOpacity>
            </View>

            <ScrollView style={{ flex: 1, padding: 16 }}>
                <View style={{ flexDirection: 'row', marginBottom: 16, alignItems: 'center' }}>
                    <View style={styles.userAvatar}>
                        {user?.profile_photo ? (
                            <Image source={{ uri: `${PHOTO_URL}${user.profile_photo}` }} style={{ width: '100%', height: '100%' }} />
                        ) : (
                            <View style={styles.userAvatarPlaceholder}><Text style={styles.userAvatarText}>{user?.name?.[0]}</Text></View>
                        )}
                    </View>
                    <View>
                        <Text style={{ fontWeight: 'bold', color: '#111827', fontSize: 18 }}>{user?.name || 'User'}</Text>
                        <View style={styles.publicBadge}><Ionicons name="earth" size={12} color="gray" /><Text style={styles.publicBadgeText}>Public</Text></View>
                    </View>
                </View>

                <TextInput
                    style={styles.contentInput}
                    placeholder={`What's on your mind?`}
                    placeholderTextColor="#666"
                    multiline
                    value={content}
                    onChangeText={setContent}
                    textAlignVertical="top"
                />

                {selectedMusic && !params.isReel && (
                    <View style={{ marginBottom: 24 }}>
                        <View style={styles.musicCard}>
                            <Image source={require('../../../assets/logo.png')} style={styles.musicThumbnail} />
                            <View style={{ flex: 1 }}>
                                <Text style={{ fontWeight: 'bold', color: '#111827' }} numberOfLines={1}>{selectedMusic.title}</Text>
                                <Text style={{ color: '#6b7280', fontSize: 12 }}>{selectedMusic.artist}</Text>
                            </View>
                            <TouchableOpacity onPress={() => { setSelectedMusic(null); setStartTime(0); setPlaybackTime(0); }}><Ionicons name="close-circle" size={24} color="gray" /></TouchableOpacity>
                        </View>

                        <View style={styles.trimmerContainer}>
                            <View style={styles.trimmerHeader}>
                                <View style={{ flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 16 }}>
                                    <View style={{ backgroundColor: '#1f2937', padding: 2, borderRadius: 10 }}>
                                        <Image source={require('../../../assets/logo.png')} style={{ width: 40, height: 40, borderRadius: 8 }} />
                                    </View>
                                    <View style={{ flex: 1, marginLeft: 12 }}>
                                        <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 14 }} numberOfLines={1}>{selectedMusic.title}</Text>
                                        <Text style={{ color: '#9ca3af', fontSize: 11 }}>{selectedMusic.artist}</Text>
                                    </View>
                                </View>
                                <View style={styles.timeBadge}><Text style={styles.timeBadgeText}>{Math.floor(playbackTime / 60)}:{(Math.floor(playbackTime % 60)).toString().padStart(2, '0')}</Text></View>
                            </View>

                            <View style={styles.waveformArea}>
                                <ScrollView
                                    ref={scrollRef}
                                    horizontal
                                    showsHorizontalScrollIndicator={false}
                                    snapToInterval={12}
                                    decelerationRate="fast"
                                    onScrollBeginDrag={() => setIsScrolling(true)}
                                    onScroll={(e) => {
                                        if (isScrolling) {
                                            const sec = Math.floor(e.nativeEvent.contentOffset.x / 12);
                                            if (sec !== startTime) {
                                                setStartTime(sec);
                                                setPlaybackTime(sec);
                                            }
                                        }
                                    }}
                                    onMomentumScrollEnd={(e) => {
                                        setIsScrolling(false);
                                        const sec = Math.floor(e.nativeEvent.contentOffset.x / 12);
                                        soundRef.current?.setPositionAsync(sec * 1000);
                                    }}
                                    onScrollEndDrag={(e) => {
                                        setIsScrolling(false);
                                        const sec = Math.floor(e.nativeEvent.contentOffset.x / 12);
                                        soundRef.current?.setPositionAsync(sec * 1000);
                                    }}
                                    onScrollAnimationEnd={() => setIsScrolling(false)}
                                    scrollEventThrottle={32}
                                    contentContainerStyle={{ paddingHorizontal: width / 2 - 6 }}
                                >
                                    {WAVEFORM_DATA.map((item, i) => {
                                        const isInSegment = i >= startTime && i < startTime + musicDuration;
                                        const isAlreadyPlayed = i < Math.floor(playbackTime);

                                        let barColor = '#374151'; // Gray for out of segment
                                        let barOpacity = 0.3;

                                        if (isInSegment) {
                                            if (isAlreadyPlayed) {
                                                barColor = '#4b5563'; // Dimmed gray for played segment
                                                barOpacity = 0.5;
                                            } else {
                                                barColor = '#f97316'; // Vivid orange for upcoming segment
                                                barOpacity = 1;
                                            }
                                        }

                                        return (
                                            <View key={i} style={{
                                                width: 4, height: item.height,
                                                backgroundColor: barColor,
                                                marginHorizontal: 4, borderRadius: 2, alignSelf: 'center',
                                                opacity: barOpacity
                                            }} />
                                        );
                                    })}
                                </ScrollView>
                                <View style={styles.centerCursor}><View style={styles.cursorDotTop} /><View style={styles.cursorDotBottom} /></View>
                            </View>

                            <View style={styles.trimmerFooter}>
                                <View style={{ flexDirection: 'column', width: '100%', paddingHorizontal: 4 }}>
                                    <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 16 }}>
                                        <Text style={{ color: '#9ca3af', fontSize: 11, fontWeight: 'bold', marginRight: 12 }}>DURATION:</Text>
                                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                                            {[15, 30, 45, 60, 90].map(d => (
                                                <TouchableOpacity
                                                    key={d}
                                                    onPress={() => setMusicDuration(d)}
                                                    style={[styles.durationBadge, musicDuration === d ? { backgroundColor: '#ea580c' } : { backgroundColor: '#1f2937' }]}
                                                >
                                                    <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 11 }}>{d}s</Text>
                                                </TouchableOpacity>
                                            ))}
                                        </ScrollView>
                                    </View>

                                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                        <TouchableOpacity onPress={() => setIsPreviewPlaying(!isPreviewPlaying)} style={[styles.playButton, isPreviewPlaying ? { backgroundColor: '#ea580c' } : { backgroundColor: '#374151' }]}>
                                            <Ionicons name={isPreviewPlaying ? "pause" : "play"} size={22} color="white" />
                                        </TouchableOpacity>
                                        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ flex: 1, marginLeft: 16 }}>
                                            {[0, 15, 30, 45, 60, 120, 180].map(val => (
                                                <TouchableOpacity key={val} style={[styles.presetButton, startTime === val ? styles.presetButtonActive : styles.presetButtonInactive]}
                                                    onPress={() => {
                                                        setStartTime(val);
                                                        setPlaybackTime(val);
                                                        scrollRef.current?.scrollTo({ x: val * 12, animated: true });
                                                        soundRef.current?.setPositionAsync(val * 1000);
                                                    }}
                                                ><Text style={[styles.presetText, startTime === val ? { color: 'white' } : { color: '#9ca3af' }]}>{val === 0 ? 'START' : val < 60 ? `${val}s` : `${Math.floor(val / 60)}m`}</Text></TouchableOpacity>
                                            ))}
                                        </ScrollView>
                                    </View>
                                </View>
                            </View>

                            <View style={styles.hiddenPreview}>
                                {isAudioLoading && <ActivityIndicator size="large" color="#ea580c" />}
                            </View>
                        </View>
                    </View>
                )}

                {images.length > 0 && (
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 80 }}>
                        {images.map((img, index) => (
                            <View key={index} style={styles.imageItemContainer}>
                                {img.toLowerCase().endsWith('.mp4') ? (
                                    <Video 
                                        source={{ uri: img }} 
                                        style={{ width: '100%', height: '100%' }} 
                                        resizeMode={ResizeMode.COVER} 
                                        shouldPlay={false} 
                                        useNativeControls 
                                        rate={recordingSpeed} 
                                        isMuted={false}
                                        volume={1.0}
                                    />
                                ) : (
                                    <Image source={{ uri: img }} style={{ width: '100%', height: '100%' }} resizeMode="cover" />
                                )}
                                <TouchableOpacity style={styles.removeImageBtn} onPress={() => removeImage(index)}><Ionicons name="close" size={20} color="white" /></TouchableOpacity>
                            </View>
                        ))}
                    </ScrollView>
                )}
            </ScrollView>

            <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
                <View style={styles.bottomActions}>
                    <TouchableOpacity style={styles.actionItem} onPress={pickImage}><Ionicons name="images" size={28} color="#45bd62" /><Text style={styles.actionText}>Photo/Video</Text></TouchableOpacity>
                    <TouchableOpacity style={[styles.actionItem, { borderTopWidth: 1, borderTopColor: '#f9fafb' }]} onPress={() => setMusicModalVisible(true)}><Ionicons name="musical-notes" size={28} color="#ea580c" /><Text style={styles.actionText}>Music Library</Text></TouchableOpacity>
                </View>
            </KeyboardAvoidingView>

            <Modal visible={musicModalVisible} animationType="slide">
                <View style={{ flex: 1, backgroundColor: 'white' }}>
                    <View style={styles.modalHeader}><Text style={{ fontSize: 20, fontWeight: 'bold' }}>Search Music</Text><TouchableOpacity onPress={() => setMusicModalVisible(false)}><Text style={{ color: '#6b7280', fontWeight: 'bold' }}>Cancel</Text></TouchableOpacity></View>
                    <View style={styles.searchBarContainer}>
                        <TextInput style={styles.searchInput} placeholder="Search songs..." placeholderTextColor="#666" value={searchQuery} onChangeText={setSearchQuery} onSubmitEditing={() => searchMusic()} />
                        <TouchableOpacity style={styles.searchBtn} onPress={() => searchMusic()}><Ionicons name="search" size={20} color="white" /></TouchableOpacity>
                    </View>
                    <ScrollView style={{ flex: 1, paddingHorizontal: 16 }}>
                        {searching ? (<ActivityIndicator size="large" color="#ea580c" style={{ marginTop: 40 }} />) : (
                            searchResults.map((song) => (
                                <View key={song.id} style={styles.searchItem}>
                                    <Image source={require('../../../assets/logo.png')} style={styles.searchThumbnail} />
                                    <View style={{ flex: 1 }}><Text style={{ fontWeight: 'bold', color: '#111827' }} numberOfLines={1}>{song.title}</Text><Text style={{ color: '#6b7280', fontSize: 12 }}>{song.artist}</Text></View>
                                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                        <TouchableOpacity
                                            style={[styles.previewBtn, playingId === song.id ? { backgroundColor: '#ffedd5' } : { backgroundColor: '#f3f4f6' }]}
                                            onPress={async () => {
                                                if (playingId === song.id) {
                                                    setPlayingId(null);
                                                    await soundRef.current?.stopAsync();
                                                } else {
                                                    setPlayingId(song.id);
                                                    await loadAndPlayAudio(song);
                                                }
                                            }}
                                        >
                                            <Ionicons name={playingId === song.id ? "stop-circle" : "play"} size={24} color="#ea580c" />
                                        </TouchableOpacity>
                                        <TouchableOpacity onPress={() => {
                                            setSelectedMusic(song);
                                            setPlayingId(null);
                                            setMusicModalVisible(false);
                                        }}>
                                            <Ionicons name="add-circle" size={32} color="#ea580c" />
                                        </TouchableOpacity>
                                    </View>
                                </View>
                            ))
                        )}
                    </ScrollView>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
    postButton: { paddingHorizontal: 20, paddingVertical: 8, borderRadius: 8 },
    postButtonActive: { backgroundColor: '#ea580c' },
    postButtonDisabled: { backgroundColor: '#f3f4f6' },
    postButtonText: { fontWeight: 'bold', fontSize: 16 },
    userAvatar: { width: 48, height: 48, borderRadius: 24, overflow: 'hidden', backgroundColor: '#f3f4f6', marginRight: 12 },
    userAvatarPlaceholder: { width: '100%', height: '100%', justifyContent: 'center', alignItems: 'center', backgroundColor: '#ffedd5' },
    userAvatarText: { color: '#9a3412', fontWeight: 'bold', fontSize: 18 },
    publicBadge: { backgroundColor: '#f3f4f6', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, alignSelf: 'flex-start', marginTop: 4, flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: '#e5e7eb' },
    publicBadgeText: { fontSize: 12, color: '#4b5563', marginLeft: 4 },
    contentInput: { fontSize: 20, color: '#111827', marginBottom: 24, minHeight: height * 0.15 },
    musicCard: { padding: 12, backgroundColor: '#f9fafb', borderRadius: 16, flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: '#f3f4f6', marginBottom: 12 },
    musicThumbnail: { width: 48, height: 48, borderRadius: 12, marginRight: 12 },
    trimmerContainer: { backgroundColor: '#111827', borderRadius: 24, overflow: 'hidden', elevation: 10 },
    trimmerHeader: { padding: 16, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderBottomWidth: 1, borderBottomColor: '#1f2937', backgroundColor: 'rgba(31, 41, 55, 0.5)' },
    timeBadge: { backgroundColor: '#ea580c', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
    timeBadgeText: { color: 'white', fontWeight: 'bold', fontSize: 12 },
    waveformArea: { paddingVertical: 40, backgroundColor: 'black', position: 'relative', alignItems: 'center', justifyContent: 'center' },
    centerCursor: { position: 'absolute', left: '50%', top: 20, bottom: 20, width: 2, backgroundColor: 'white', borderRadius: 1, marginLeft: -1, zIndex: 20 },
    cursorDotTop: { position: 'absolute', top: -6, left: -5, width: 12, height: 12, borderRadius: 6, backgroundColor: 'white', borderWidth: 2, borderColor: '#ea580c' },
    cursorDotBottom: { position: 'absolute', bottom: -6, left: -5, width: 12, height: 12, borderRadius: 6, backgroundColor: 'white', borderWidth: 2, borderColor: '#ea580c' },
    trimmerFooter: { backgroundColor: '#111827', padding: 12, flexDirection: 'row', alignItems: 'center', borderTopWidth: 1, borderTopColor: '#1f2937' },
    playButton: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center' },
    presetButton: { paddingHorizontal: 12, paddingVertical: 8, borderRadius: 12, marginRight: 8 },
    presetButtonActive: { backgroundColor: '#ea580c' },
    presetButtonInactive: { backgroundColor: '#1f2937' },
    presetText: { fontWeight: 'bold', fontSize: 11 },
    durationBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 10, marginRight: 8 },
    imageItemContainer: { width: 256, height: 192, marginRight: 12, borderRadius: 20, overflow: 'hidden', borderWidth: 1, borderColor: '#e5e7eb', position: 'relative' },
    removeImageBtn: { position: 'absolute', top: 8, right: 8, backgroundColor: 'rgba(0,0,0,0.6)', padding: 6, borderRadius: 20, zIndex: 10 },
    bottomActions: { borderTopWidth: 1, borderTopColor: '#f3f4f6', padding: 8, backgroundColor: 'white' },
    actionItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 16, paddingHorizontal: 12 },
    actionText: { marginLeft: 16, fontWeight: '500', color: '#374151', fontSize: 16 },
    modalHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
    searchBarContainer: { padding: 16, flexDirection: 'row' },
    searchInput: { flex: 1, backgroundColor: '#f3f4f6', padding: 12, borderRadius: 12, marginRight: 8, color: '#111827' },
    searchBtn: { backgroundColor: '#ea580c', width: 48, height: 48, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
    searchItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f9fafb' },
    searchThumbnail: { width: 56, height: 56, borderRadius: 12, marginRight: 16, backgroundColor: '#f3f4f6' },
    previewBtn: { marginRight: 12, padding: 8, borderRadius: 24 },
    hiddenPreview: { height: 10, width: 10, position: 'absolute', bottom: -100, right: -100, zIndex: -1, opacity: 0.1 }
});

export default CreatePostScreen;
