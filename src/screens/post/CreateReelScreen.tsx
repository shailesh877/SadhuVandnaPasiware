import React, { useState, useRef, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Dimensions, ActivityIndicator, Alert, ScrollView, Animated, Modal, TextInput, Image, SafeAreaView } from 'react-native';
import { CameraView, useCameraPermissions, useMicrophonePermissions } from 'expo-camera';
import { Audio } from 'expo-av';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import api from '../../services/api';
import * as ImagePicker from 'expo-image-picker';

const { width, height } = Dimensions.get('window');

const FILTERS = [
    { id: 'none', name: 'Original', color: 'transparent' },
    { id: 'cinematic', name: 'Cinematic', color: 'rgba(50, 20, 0, 0.15)', vignette: true },
    { id: 'bw_grain', name: 'Vintage', color: 'rgba(0,0,0,0.4)', grain: true },
    { id: 'cyberpunk', name: 'Cyber', dualTone: ['rgba(255,0,255,0.15)', 'rgba(0,255,255,0.1)'] },
    { id: 'sunset', name: 'Sunset', color: 'rgba(255, 69, 0, 0.15)', vignette: true },
    { id: 'cool_night', name: 'Cool', color: 'rgba(0, 0, 139, 0.1)', dualTone: ['rgba(0,0,50,0.2)', 'rgba(0,100,255,0.05)'] },
    { id: 'retro_sepia', name: 'Retro', color: 'rgba(112, 66, 20, 0.25)', grain: true, vignette: true },
];

const VignetteOverlay = () => (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
        <View style={[styles.vignettePart, { top: -20, left: -20, right: -20, height: 150, borderBottomLeftRadius: 100, borderBottomRightRadius: 100 }]} />
        <View style={[styles.vignettePart, { bottom: -20, left: -20, right: -20, height: 150, borderTopLeftRadius: 100, borderTopRightRadius: 100 }]} />
        <View style={[styles.vignettePart, { left: -20, top: 0, bottom: 0, width: 80, borderTopRightRadius: 50, borderBottomRightRadius: 50 }]} />
        <View style={[styles.vignettePart, { right: -20, top: 0, bottom: 0, width: 80, borderTopLeftRadius: 50, borderBottomLeftRadius: 50 }]} />
    </View>
);

const FilmGrain = () => (
    <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(255,255,255,0.03)' }]} pointerEvents="none">
        <View style={[StyleSheet.absoluteFill, { opacity: 0.1, backgroundColor: '#000' }]} />
    </View>
);

const GridLines = () => (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
        <View style={{ position: 'absolute', top: '33.33%', left: 0, right: 0, height: 1, backgroundColor: 'rgba(255,255,255,0.3)' }} />
        <View style={{ position: 'absolute', top: '66.66%', left: 0, right: 0, height: 1, backgroundColor: 'rgba(255,255,255,0.3)' }} />
        <View style={{ position: 'absolute', left: '33.33%', top: 0, bottom: 0, width: 1, backgroundColor: 'rgba(255,255,255,0.3)' }} />
        <View style={{ position: 'absolute', left: '66.66%', top: 0, bottom: 0, width: 1, backgroundColor: 'rgba(255,255,255,0.3)' }} />
    </View>
);

const CreateReelScreen = () => {
    const navigation = useNavigation<any>();
    const insets = useSafeAreaInsets();
    const [permission, requestPermission] = useCameraPermissions();
    const [micPermission, requestMicPermission] = useMicrophonePermissions();
    const [isRecording, setIsRecording] = useState(false);
    const [facing, setFacing] = useState<'front' | 'back'>('back');
    const [selectedFilter, setSelectedFilter] = useState(FILTERS[0]);
    const [selectedMusic, setSelectedMusic] = useState<any>(null);
    const [recordingProgress] = useState(new Animated.Value(0));
    const [showFilters, setShowFilters] = useState(false);
    const [selectedSpeed, setSelectedSpeed] = useState(1); // 0.5, 1, 1.5, 2
    const [showSpeedSelector, setShowSpeedSelector] = useState(false);
    const [flash, setFlash] = useState<any>('off');
    const [zoom, setZoom] = useState(0);
    const [timer, setTimer] = useState(0); // 0, 3, 10
    const [maxDuration, setMaxDuration] = useState(30); // 15, 30, 60
    const [showGrid, setShowGrid] = useState(false);
    const [countdown, setCountdown] = useState(0);

    // Music Modal State
    const [musicModalVisible, setMusicModalVisible] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<any[]>([]);
    const [searching, setSearching] = useState(false);

    const cameraRef = useRef<any>(null);
    const soundRef = useRef<Audio.Sound | null>(null);
    const recordingStartTimeRef = useRef<number>(0);
    const countdownIntervalRef = useRef<any>(null);

    useEffect(() => {
        return () => {
            if (soundRef.current) {
                soundRef.current.unloadAsync();
            }
        };
    }, []);

    useEffect(() => {
        const unsubscribe = navigation.addListener('blur', () => {
            if (soundRef.current) {
                soundRef.current.stopAsync();
                soundRef.current.unloadAsync();
                soundRef.current = null;
            }
        });
        return unsubscribe;
    }, [navigation]);

    useEffect(() => {
        if (!permission || !permission.granted) requestPermission();
        if (!micPermission || !micPermission.granted) requestMicPermission();
    }, [permission, micPermission]);

    const handleGrantPermissions = async () => {
        await requestPermission();
        await requestMicPermission();
    };

    useEffect(() => {
        if (musicModalVisible && searchResults.length === 0) {
            searchMusic('');
        }
    }, [musicModalVisible]);

    const searchMusic = async (q: string) => {
        setSearchQuery(q);
        setSearching(true);
        try {
            const res = await api.get(`get_music.php?search=${q}`);
            if (res.data.status === 'success') {
                setSearchResults(res.data.data);
            }
        } catch (error) {
            console.error("Search error:", error);
        } finally {
            setSearching(false);
        }
    };

    const startRecording = async () => {
        if (!cameraRef.current) return;
        try {
            // 2. Handle Countdown
            if (timer > 0) {
                setCountdown(timer);
                countdownIntervalRef.current = setInterval(() => {
                    setCountdown(prev => {
                        if (prev <= 1) {
                            if (countdownIntervalRef.current) clearInterval(countdownIntervalRef.current);
                            countdownIntervalRef.current = null;
                            actuallyStartRecording();
                            return 0;
                        }
                        return prev - 1;
                    });
                }, 1000);
            } else {
                actuallyStartRecording();
            }
        } catch (error) {
            console.error("Recording error:", error);
            stopRecording();
        }
    };

    const actuallyStartRecording = async () => {
        if (!cameraRef.current) return;
        try {
            setIsRecording(true);
            recordingStartTimeRef.current = Date.now();

            // 1. Play Music
            if (selectedMusic) {
                if (soundRef.current) {
                    await soundRef.current.unloadAsync();
                }
                const { sound } = await Audio.Sound.createAsync(
                    { uri: selectedMusic.file_url },
                    {
                        shouldPlay: true,
                        positionMillis: (selectedMusic.startTime || 0) * 1000,
                        rate: selectedSpeed, // Play faster if recording fast
                        shouldCorrectPitch: true
                    }
                );
                soundRef.current = sound;
            }

            // 2. Animate progress
            recordingProgress.setValue(0);
            Animated.timing(recordingProgress, {
                toValue: 1,
                duration: maxDuration * 1000,
                useNativeDriver: false,
            }).start();

            // 3. Start Recording
            const video = await cameraRef.current.recordAsync({
                maxDuration: maxDuration,
                quality: '720p',
            });

            if (video) {
                const duration = Math.floor((Date.now() - recordingStartTimeRef.current) / 1000);
                handleFinishRecording(video.uri, Math.min(duration, maxDuration));
            }
        } catch (error) {
            console.error("Recording error:", error);
            stopRecording();
        }
    };

    const stopRecording = async () => {
        if (countdownIntervalRef.current) {
            clearInterval(countdownIntervalRef.current);
            countdownIntervalRef.current = null;
            setCountdown(0);
        }

        if (!isRecording) return;
        setIsRecording(false);
        recordingProgress.stopAnimation();

        if (cameraRef.current) {
            cameraRef.current.stopRecording();
        }

        if (soundRef.current) {
            await soundRef.current.stopAsync();
            await soundRef.current.unloadAsync();
            soundRef.current = null;
        }
    };

    const handleFinishRecording = (uri: string, duration: number) => {
        navigation.navigate('CreatePost', {
            videoUri: uri,
            preSelectedMusic: selectedMusic,
            isReel: true,
            recordedDuration: duration,
            recordingSpeed: selectedSpeed,
            filterId: selectedFilter.id
        });
    };

    const pickFromGallery = async () => {
        try {
            const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.Videos,
                allowsEditing: true,
                quality: 1,
            });

            if (!result.canceled && result.assets && result.assets.length > 0) {
                const asset = result.assets[0];
                handleFinishRecording(asset.uri, asset.duration || 0);
            }
        } catch (error) {
            Alert.alert("Error", "Could not pick video from gallery");
        }
    };

    const selectMusic = (music: any) => {
        setSelectedMusic(music);
        setMusicModalVisible(false);
    };

    if (!permission || !micPermission) return <View style={styles.loading}><ActivityIndicator size="large" color="#ea580c" /></View>;

    if (!permission.granted || !micPermission.granted) {
        return (
            <View style={styles.container}>
                <Ionicons name="videocam-off-outline" size={64} color="#ea580c" style={{ marginBottom: 20 }} />
                <Text style={styles.text}>Camera and Microphone access are required to make Reels</Text>
                <TouchableOpacity onPress={handleGrantPermissions} style={styles.button}>
                    <Text style={styles.buttonText}>Grant Permissions</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <CameraView
                style={styles.camera}
                facing={facing}
                ref={cameraRef}
                mode="video"
                flash={flash}
                enableTorch={flash === 'on'}
                zoom={zoom}
            >
                {/* Top Progress Bar */}
                {isRecording && (
                    <View style={[styles.progressBarContainer, { top: insets.top }]}>
                        <Animated.View style={[styles.progressBar, {
                            width: recordingProgress.interpolate({ inputRange: [0, 1], outputRange: ['0%', '100%'] })
                        }]} />
                    </View>
                )}

                {/* Visual Filter Layer */}
                <View style={[StyleSheet.absoluteFill, { backgroundColor: selectedFilter.color || 'transparent' }]} pointerEvents="none" />

                {selectedFilter.dualTone && (
                    <>
                        <View style={[StyleSheet.absoluteFill, { backgroundColor: selectedFilter.dualTone[0], opacity: 0.5 }]} pointerEvents="none" />
                        <View style={[StyleSheet.absoluteFill, { backgroundColor: selectedFilter.dualTone[1], opacity: 0.3 }]} pointerEvents="none" />
                    </>
                )}

                {selectedFilter.vignette && <VignetteOverlay />}
                {selectedFilter.grain && <FilmGrain />}
                {showGrid && <GridLines />}

                {countdown > 0 && (
                    <View style={styles.countdownOverlay}>
                        <Text style={styles.countdownText}>{countdown}</Text>
                    </View>
                )}

                {/* Top Toolbar */}
                <View style={[styles.topControls, { paddingTop: insets.top + 10 }]}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.iconBtn}>
                        <Ionicons name="close" size={30} color="white" />
                    </TouchableOpacity>

                    <TouchableOpacity style={styles.musicSelector} onPress={() => setMusicModalVisible(true)}>
                        <Ionicons name="musical-notes" size={20} color="white" />
                        <Text style={styles.musicText} numberOfLines={1}>
                            {selectedMusic ? selectedMusic.title : 'Add Music'}
                        </Text>
                    </TouchableOpacity>

                    <TouchableOpacity onPress={() => setFacing(v => v === 'back' ? 'front' : 'back')} style={styles.iconBtn}>
                        <Ionicons name="camera-reverse" size={30} color="white" />
                    </TouchableOpacity>
                </View>

                {/* Left Sidebar (Instagram Style) */}
                <View style={styles.sidebar}>
                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => {
                            setShowFilters(!showFilters);
                            setShowSpeedSelector(false);
                        }}
                    >
                        <Ionicons name="sparkles-outline" size={26} color={showFilters ? "#db2777" : "white"} />
                        <Text style={styles.sideText}>Effects</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => {
                            setShowSpeedSelector(!showSpeedSelector);
                            setShowFilters(false);
                        }}
                    >
                        <Ionicons name="speedometer-outline" size={26} color={showSpeedSelector ? "#ea580c" : "white"} />
                        <Text style={styles.sideText}>{selectedSpeed}x</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => setFlash((f: string) => f === 'off' ? 'on' : 'off')}
                    >
                        <Ionicons name={flash === 'off' ? "flash-off" : "flash"} size={26} color={flash === 'on' ? "#ea580c" : "white"} />
                        <Text style={styles.sideText}>{flash === 'on' ? 'FLASH ON' : 'FLASH OFF'}</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => setTimer(t => t === 0 ? 3 : t === 3 ? 10 : 0)}
                    >
                        <Ionicons name="stopwatch-outline" size={26} color={timer > 0 ? "#ea580c" : "white"} />
                        <Text style={styles.sideText}>{timer > 0 ? `${timer}s` : 'Off'}</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => setMaxDuration(d => d === 15 ? 30 : d === 30 ? 60 : 15)}
                    >
                        <Ionicons name="time-outline" size={26} color="white" />
                        <Text style={styles.sideText}>{maxDuration}s</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={styles.sideBtn}
                        onPress={() => setShowGrid(!showGrid)}
                    >
                        <Ionicons name="grid-outline" size={26} color={showGrid ? "#ea580c" : "white"} />
                        <Text style={styles.sideText}>Grid</Text>
                    </TouchableOpacity>
                </View>

                {/* Vertical Zoom Control */}
                <View style={styles.zoomContainer}>
                    <TouchableOpacity onPress={() => setZoom(Math.min(zoom + 0.1, 1))} style={styles.zoomOption}>
                        <Ionicons name="add" size={20} color="white" />
                    </TouchableOpacity>
                    <View style={styles.zoomTrack}>
                        <View style={[styles.zoomIndicator, { bottom: `${zoom * 100}%` }]} />
                    </View>
                    <TouchableOpacity onPress={() => setZoom(Math.max(zoom - 0.1, 0))} style={styles.zoomOption}>
                        <Ionicons name="remove" size={20} color="white" />
                    </TouchableOpacity>
                </View>

                {/* Speed Selector Badge */}
                {showSpeedSelector && (
                    <View style={styles.speedPanel}>
                        {[0.5, 1, 1.5, 2].map(s => (
                            <TouchableOpacity
                                key={s}
                                onPress={() => {
                                    setSelectedSpeed(s);
                                    setShowSpeedSelector(false);
                                }}
                                style={[styles.speedBadge, selectedSpeed === s && styles.speedBadgeActive]}
                            >
                                <Text style={[styles.speedBadgeText, selectedSpeed === s && { color: 'white' }]}>{s}x</Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                )}

                {/* Filter & Record UI */}
                <View style={[styles.bottomControls, { paddingBottom: insets.bottom + 20 }]}>
                    {!isRecording && showFilters && (
                        <View style={styles.filterSection}>
                            <Text style={styles.filterSectionTitle}>Filters</Text>
                            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterList}>
                                {FILTERS.map(filter => (
                                    <TouchableOpacity
                                        key={filter.id}
                                        onPress={() => setSelectedFilter(filter)}
                                        style={[styles.filterItem, selectedFilter.id === filter.id && styles.activeFilter]}
                                    >
                                        <View style={[
                                            styles.filterPreview,
                                            { backgroundColor: filter.dualTone ? filter.dualTone[0] : (filter.color === 'transparent' ? '#333' : filter.color) }
                                        ]}>
                                            {filter.vignette && <View style={styles.previewVignette} />}
                                        </View>
                                        <Text style={styles.filterName}>{filter.name}</Text>
                                    </TouchableOpacity>
                                ))}
                            </ScrollView>
                        </View>
                    )}

                    <View style={styles.recordContainer}>
                        <View style={styles.recordRow}>
                            {!isRecording && (
                                <TouchableOpacity onPress={pickFromGallery} style={styles.galleryBtn}>
                                    <Ionicons name="images-outline" size={26} color="white" />
                                    <Text style={styles.galleryText}>Gallery</Text>
                                </TouchableOpacity>
                            )}
                            
                            <TouchableOpacity
                                onLongPress={startRecording}
                                onPressOut={stopRecording}
                                activeOpacity={0.9}
                                style={[
                                    styles.recordBtn,
                                    isRecording && styles.recordingActive,
                                    isRecording && { transform: [{ scale: 1.2 }] }
                                ]}
                            >
                                <View style={styles.innerRecordBtn} />
                            </TouchableOpacity>

                            {!isRecording && <View style={{ width: 60 }} />}
                        </View>
                        <Text style={styles.hintText}>{isRecording ? 'RELEASE TO STOP' : 'HOLD TO RECORD'}</Text>
                    </View>
                </View>

                {/* Music Selection Modal */}
                <Modal visible={musicModalVisible} animationType="slide" transparent>
                    <View style={styles.modalContent}>
                        <SafeAreaView style={{ flex: 1 }}>
                            <View style={styles.modalHeader}>
                                <TouchableOpacity onPress={() => setMusicModalVisible(false)}>
                                    <Ionicons name="chevron-down" size={30} color="white" />
                                </TouchableOpacity>
                                <Text style={styles.modalTitle}>Select Music</Text>
                                <View style={{ width: 30 }} />
                            </View>

                            <View style={styles.searchBar}>
                                <Ionicons name="search" size={20} color="#999" />
                                <TextInput
                                    placeholder="Search music..."
                                    placeholderTextColor="#999"
                                    style={styles.searchInput}
                                    value={searchQuery}
                                    onChangeText={searchMusic}
                                />
                            </View>

                            {searching ? (
                                <ActivityIndicator color="#ea580c" style={{ marginTop: 50 }} />
                            ) : (
                                <ScrollView style={{ flex: 1, padding: 20 }}>
                                    {searchResults.map(item => (
                                        <TouchableOpacity key={item.id} style={styles.musicItem} onPress={() => selectMusic(item)}>
                                            <Image source={require('../../../assets/logo.png')} style={styles.musicThumb} />
                                            <View style={{ flex: 1, marginLeft: 15 }}>
                                                <Text style={styles.musicTitle}>{item.title}</Text>
                                                <Text style={styles.musicArtist}>{item.artist}</Text>
                                            </View>
                                            <Ionicons name="add-circle" size={28} color="#ea580c" />
                                        </TouchableOpacity>
                                    ))}
                                </ScrollView>
                            )}
                        </SafeAreaView>
                    </View>
                </Modal>
            </CameraView>
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: 'black' },
    camera: { flex: 1 },
    loading: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'black' },
    text: { color: 'white', textAlign: 'center', marginBottom: 20 },
    button: { backgroundColor: '#ea580c', paddingHorizontal: 30, paddingVertical: 12, borderRadius: 25 },
    buttonText: { color: 'white', fontWeight: 'bold' },
    topControls: {
        position: 'absolute',
        top: 0, left: 0, right: 0,
        flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
        paddingHorizontal: 15, zIndex: 10,
    },
    iconBtn: { padding: 10 },
    progressBarContainer: {
        position: 'absolute',
        left: 10,
        right: 10,
        height: 4,
        backgroundColor: 'rgba(255,255,255,0.3)',
        borderRadius: 2,
        zIndex: 100,
    },
    progressBar: {
        height: '100%',
        backgroundColor: '#ea580c',
        borderRadius: 2,
    },
    musicSelector: {
        flexDirection: 'row', alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.6)', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20,
        flex: 1, marginHorizontal: 15,
    },
    musicText: { color: 'white', marginLeft: 8, fontWeight: '600', fontSize: 13, flex: 1 },
    sidebar: {
        position: 'absolute',
        left: 15, top: height * 0.25,
        alignItems: 'center', zIndex: 10,
    },
    sideBtn: { alignItems: 'center', marginBottom: 25 },
    sideText: { color: 'white', fontSize: 11, marginTop: 4, fontWeight: '500' },
    bottomControls: {
        position: 'absolute',
        bottom: 0, left: 0, right: 0, zIndex: 10,
    },
    filterList: { paddingLeft: 20, marginBottom: 15 },
    filterItem: { alignItems: 'center', marginRight: 15, opacity: 0.6 },
    activeFilter: { opacity: 1 },
    filterPreview: {
        width: 44, height: 44, borderRadius: 22,
        borderWidth: 2, borderColor: 'white', marginBottom: 4,
    },
    filterName: { color: 'white', fontSize: 10 },
    recordContainer: { alignItems: 'center', marginBottom: 20 },
    recordBtn: {
        width: 76, height: 76, borderRadius: 38,
        backgroundColor: 'rgba(255,255,255,0.2)',
        alignItems: 'center', justifyContent: 'center',
        borderWidth: 4, borderColor: 'white',
    },
    recordRow: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        width: '100%',
        paddingHorizontal: 40,
    },
    galleryBtn: {
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: 40,
        backgroundColor: 'rgba(0,0,0,0.3)',
        width: 60,
        height: 60,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: 'rgba(255,255,255,0.5)',
    },
    galleryText: {
        color: 'white',
        fontSize: 10,
        fontWeight: 'bold',
        marginTop: 2,
    },
    vignettePart: {
        position: 'absolute',
        backgroundColor: 'rgba(0,0,0,0.5)',
        shadowColor: 'black',
        shadowOffset: { width: 0, height: 0 },
        shadowOpacity: 1,
        shadowRadius: 50,
        elevation: 10,
    },
    filterSection: {
        paddingBottom: 10,
    },
    filterSectionTitle: {
        color: '#888',
        fontSize: 10,
        fontWeight: 'bold',
        marginLeft: 20,
        marginBottom: 8,
        letterSpacing: 1,
        textTransform: 'uppercase',
    },
    previewVignette: {
        ...StyleSheet.absoluteFillObject,
        borderWidth: 10,
        borderColor: 'rgba(0,0,0,0.3)',
        borderRadius: 20,
    },
    recordingActive: { transform: [{ scale: 1.25 }] },
    innerRecordBtn: { width: 62, height: 62, borderRadius: 31, backgroundColor: 'white' },
    progressRing: { position: 'absolute', borderWidth: 4, borderColor: '#ea580c', borderRadius: 100 },
    hintText: { color: 'white', fontSize: 12, fontWeight: 'bold', marginTop: 12, letterSpacing: 1 },
    speedPanel: {
        position: 'absolute',
        top: height * 0.35,
        left: 80,
        backgroundColor: 'rgba(0,0,0,0.6)',
        borderRadius: 25,
        padding: 5,
        flexDirection: 'row',
        alignItems: 'center',
    },
    speedBadge: {
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 20,
        marginHorizontal: 2,
    },
    speedBadgeActive: {
        backgroundColor: '#ea580c',
    },
    speedBadgeText: {
        color: '#ccc',
        fontSize: 12,
        fontWeight: 'bold',
    },

    // Modal Styles
    modalContent: { flex: 1, backgroundColor: 'rgba(0,0,0,0.95)' },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20 },
    modalTitle: { color: 'white', fontSize: 18, fontWeight: 'bold' },
    searchBar: {
        flexDirection: 'row', alignItems: 'center', backgroundColor: '#1a1a1a',
        margin: 20, paddingHorizontal: 15, paddingVertical: 10, borderRadius: 12,
    },
    searchInput: { flex: 1, color: 'white', marginLeft: 10, fontSize: 16 },
    musicItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
    musicThumb: { width: 50, height: 50, borderRadius: 8 },
    musicTitle: { color: 'white', fontSize: 15, fontWeight: 'bold' },
    musicArtist: { color: '#888', fontSize: 13, marginTop: 2 },
    countdownOverlay: {
        ...StyleSheet.absoluteFillObject,
        backgroundColor: 'rgba(0,0,0,0.3)',
        justifyContent: 'center',
        alignItems: 'center',
        zIndex: 200,
    },
    countdownText: {
        color: 'white',
        fontSize: 120,
        fontWeight: '900',
    },
    zoomContainer: {
        position: 'absolute',
        right: 15,
        top: height * 0.25,
        alignItems: 'center',
        zIndex: 10,
        backgroundColor: 'rgba(0,0,0,0.4)',
        borderRadius: 20,
        paddingVertical: 10,
    },
    zoomOption: {
        padding: 8,
    },
    zoomTrack: {
        width: 2,
        height: 120,
        backgroundColor: 'rgba(255,255,255,0.2)',
        marginVertical: 10,
        position: 'relative',
        borderRadius: 1,
    },
    zoomIndicator: {
        position: 'absolute',
        left: -4,
        width: 10,
        height: 10,
        borderRadius: 5,
        backgroundColor: 'white',
    }
});

export default CreateReelScreen;
