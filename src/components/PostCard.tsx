import React, { useState, useRef, useEffect } from 'react';
import { View, Text, Image, TouchableOpacity, StyleSheet, Dimensions, ScrollView, Alert, Share, Linking, Animated, Easing, ActivityIndicator } from 'react-native';
import { useNavigation, useIsFocused } from '@react-navigation/native';
import { navigationRef } from '../navigation/navigationRef';
import { Ionicons } from '@expo/vector-icons';
import PostImage from './PostImage';
import { WEBSITE_URL, API_BASE_URL } from '../services/api';
import { Video, ResizeMode, Audio } from 'expo-av';
import api from '../services/api';
import { WebView } from 'react-native-webview';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

const FILTERS: any = {
    'none': { color: 'transparent' },
    'cinematic': { color: 'rgba(50, 20, 0, 0.15)', vignette: true },
    'bw_grain': { color: 'rgba(0,0,0,0.4)', grain: true },
    'cyberpunk': { dualTone: ['rgba(255,0,255,0.15)', 'rgba(0,255,255,0.1)'] },
    'sunset': { color: 'rgba(255, 69, 0, 0.15)', vignette: true },
    'cool_night': { color: 'rgba(0, 0, 139, 0.1)', dualTone: ['rgba(0,0,50,0.2)', 'rgba(0,100,255,0.05)'] },
    'retro_sepia': { color: 'rgba(112, 66, 20, 0.25)', grain: true, vignette: true },
};

const VignetteOverlay = () => (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
        <View style={[styles.vignettePart, { top: -20, left: -20, right: -20, height: 100, borderBottomLeftRadius: 100, borderBottomRightRadius: 100 }]} />
        <View style={[styles.vignettePart, { bottom: -20, left: -20, right: -20, height: 100, borderTopLeftRadius: 100, borderTopRightRadius: 100 }]} />
        <View style={[styles.vignettePart, { left: -20, top: 0, bottom: 0, width: 60, borderTopRightRadius: 50, borderBottomRightRadius: 50 }]} />
        <View style={[styles.vignettePart, { right: -20, top: 0, bottom: 0, width: 60, borderTopLeftRadius: 50, borderBottomLeftRadius: 50 }]} />
    </View>
);

const FilmGrain = () => (
    <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(255,255,255,0.03)' }]} pointerEvents="none">
        <View style={[StyleSheet.absoluteFill, { opacity: 0.1, backgroundColor: '#000' }]} />
    </View>
);

const FilterOverlay = ({ filterId }: { filterId: string }) => {
    const filter = FILTERS[filterId] || FILTERS['none'];
    return (
        <View style={StyleSheet.absoluteFill} pointerEvents="none">
            <View style={[StyleSheet.absoluteFill, { backgroundColor: filter.color || 'transparent' }]} />
            {filter.dualTone && (
                <>
                    <View style={[StyleSheet.absoluteFill, { backgroundColor: filter.dualTone[0], opacity: 0.5 }]} />
                    <View style={[StyleSheet.absoluteFill, { backgroundColor: filter.dualTone[1], opacity: 0.3 }]} />
                </>
            )}
            {filter.vignette && <VignetteOverlay />}
            {filter.grain && <FilmGrain />}
        </View>
    );
};

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
    link?: string;
}

const { width } = Dimensions.get('window');
const cardWidth = width - 24;
// Clean the base URL by stripping trailing slash and /Api
const BASE_URL_ROOT = API_BASE_URL.replace(/\/Api\/?$/, '').replace(/\/$/, '');
const POST_MEDIA_PATH = `${BASE_URL_ROOT}/uploads/posts/`;

const VideoItem = ({ uri, shouldPlay, forwardedRef, onReady, isMuted, onBuffering, poster }: any) => {
    const [duration, setDuration] = useState(0);
    const [error, setError] = useState<string | null>(null);

    const formatTime = (millis: number) => {
        const totalSeconds = Math.round(millis / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    };

    const isMounted = useRef(true);

    useEffect(() => {
        isMounted.current = true;
        // Playback Nudge: If shouldPlay becomes true, ensure the video starts
        if (shouldPlay && forwardedRef?.current) {
            forwardedRef.current.playAsync().catch(() => { });
        }
        return () => {
            isMounted.current = false;
        };
    }, [shouldPlay]);

    useEffect(() => {
        return () => {
            isMounted.current = false;
            // Immediate synchronous flag set
            if (forwardedRef?.current) {
                const ref = forwardedRef.current;
                // Direct unload call is safer during unmount than .then() chains
                ref.unloadAsync().catch(() => { });
            }
        };
    }, []);

    return (
        <View style={{ width: '100%', aspectRatio: 9 / 16, backgroundColor: 'black' }}>
            <Video
                ref={forwardedRef}
                source={{ uri }}
                style={{ width: '100%', height: '100%' }}
                resizeMode={ResizeMode.COVER}
                isLooping
                useNativeControls={false}
                shouldPlay={shouldPlay}
                isMuted={isMuted}
                usePoster={!!poster}
                posterSource={poster ? { uri: poster } : undefined}
                posterStyle={{ resizeMode: ResizeMode.COVER }}
                onPlaybackStatusUpdate={(status: any) => {
                    if (status.isLoaded) {
                        if (onBuffering) onBuffering(status.isBuffering);
                    }
                }}
                progressUpdateIntervalMillis={2000}
                onLoadStart={() => {
                    if (onBuffering) onBuffering(true);
                    setError(null);
                }}
                onLoad={(status: any) => {
                    if (status.isLoaded) {
                        if (status.durationMillis) setDuration(status.durationMillis);
                        if (onReady) onReady();
                        if (onBuffering) onBuffering(false);
                    }
                }}
                onError={(e) => {
                    console.error("[PostCard Video] Error:", e, "URI:", uri);
                    setError("Failed to play video");
                    if (onBuffering) onBuffering(false);
                }}
            />
            {error && (
                <View style={[StyleSheet.absoluteFill, { justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.5)' }]}>
                    <Ionicons name="alert-circle" size={40} color="white" />
                    <Text style={{ color: 'white', marginTop: 8, fontSize: 12 }}>{error}</Text>
                </View>
            )}
            {duration > 0 && (
                <View style={styles.videoDuration}>
                    <Text style={styles.videoDurationText}>{formatTime(duration)}</Text>
                </View>
            )}
        </View>
    );
};

const NativeAudioItem = ({ url, startTime, duration, shouldPlay, postId, isMuted, onReady }: any) => {
    const soundRef = useRef<Audio.Sound | null>(null);
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        if (soundRef.current) {
            soundRef.current.setStatusAsync({ volume: isMuted ? 0 : 1.0 });
        }
    }, [isMuted]);

    useEffect(() => {
        const loadSound = async () => {
            try {
                if (soundRef.current) {
                    await soundRef.current.unloadAsync();
                }
                const { sound } = await Audio.Sound.createAsync(
                    { uri: url },
                    {
                        shouldPlay: false, // Don't play until loaded
                        positionMillis: (startTime || 0) * 1000,
                        isLooping: false,
                        volume: isMuted ? 0 : 1.0
                    }
                );
                soundRef.current = sound;
                setIsLoaded(true);
                if (onReady) onReady(true);

                sound.setOnPlaybackStatusUpdate((status: any) => {
                    if (status.isLoaded && status.isPlaying) {
                        const current = status.positionMillis / 1000;
                        const end = (startTime || 0) + (duration || 30);
                        if (current >= end - 0.2) {
                            sound.setPositionAsync((startTime || 0) * 1000);
                        }
                    }
                });
            } catch (e) {
                console.error(`[NativeAudio] Load Error for post ${postId}:`, e);
                // Don't block video if audio fails
                setIsLoaded(true);
                if (onReady) onReady(true);
            }
        };

        if (url) {
            if (onReady) onReady(false);
            loadSound();
        }

        return () => {
            if (soundRef.current) {
                const s = soundRef.current;
                soundRef.current = null;
                // Avoid async chain on unmount to prevent 'wrong thread' errors
                if (s) {
                    s.unloadAsync().catch(() => { });
                }
            }
        };
    }, [url, startTime, duration]);

    useEffect(() => {
        if (isLoaded && soundRef.current) {
            if (shouldPlay) {
                soundRef.current.playAsync().catch(() => { });
            } else {
                soundRef.current.pauseAsync().catch(() => { });
            }
        }
    }, [shouldPlay, isLoaded]);

    return null;
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
    const isFocused = useIsFocused();
    const [liked, setLiked] = useState(post.isLiked);
    const [likeCount, setLikeCount] = useState(post.likes);
    const [videoReady, setVideoReady] = useState(false);
    const [musicMuted, setMusicMuted] = useState(false);
    const [paused, setPaused] = useState(false);
    const [soundReady, setSoundReady] = useState(false);
    const [isBuffering, setIsBuffering] = useState(false);
    const [activeMediaIndex, setActiveMediaIndex] = useState(0);
    const likeScale = useRef(new Animated.Value(1)).current;
    const videoRef = useRef<Video>(null);
    const webViewRef = useRef<WebView>(null);
    const musicData = React.useMemo(() => {
        try {
            if (post.link) {
                if (typeof post.link === 'object') return post.link;
                if (typeof post.link === 'string' && post.link.startsWith('{')) return JSON.parse(post.link);
            }
        } catch (e) { }
        return null;
    }, [post.link]);

    const hasMusic = !!(musicData && (musicData.type === 'youtube_music' || musicData.type === 'custom_music'));

    // Combine local shouldPlay with screen focus
    // Fix: Only wait for soundReady if the post actually has music
    const actualShouldPlay = !!(shouldPlay && isFocused && !paused && (!hasMusic || soundReady));
    const audioShouldPlay = !!(!paused); // Allow music even if video jitters slightly

    const shouldMountAudio = !!(shouldPlay && isFocused);

    const rotation = useRef(new Animated.Value(0)).current;
    const rotateAnim = useRef<Animated.CompositeAnimation | null>(null);

    useEffect(() => {
        if (actualShouldPlay && !musicMuted) {
            rotateAnim.current = Animated.loop(
                Animated.timing(rotation, {
                    toValue: 1,
                    duration: 3000,
                    easing: Easing.linear,
                    useNativeDriver: true,
                })
            );
            rotateAnim.current.start();
        } else {
            if (rotateAnim.current) {
                rotateAnim.current.stop();
            }
            rotation.setValue(0);
        }
        return () => rotateAnim.current?.stop();
    }, [actualShouldPlay, musicMuted]);

    const rotateData = rotation.interpolate({
        inputRange: [0, 1],
        outputRange: ['0deg', '360deg'],
    });

    useEffect(() => {
        if (post.link && webViewRef.current) {
            const cmd = `if(window.setPlaybackStatus) window.setPlaybackStatus(${audioShouldPlay});`;
            webViewRef.current.injectJavaScript(cmd);
        }
    }, [audioShouldPlay]);

    const toggleLike = async () => {
        const newStatus = !liked;
        setLiked(newStatus);
        setLikeCount(prev => newStatus ? prev + 1 : prev - 1);

        if (newStatus) {
            likeScale.setValue(0.7);
            Animated.spring(likeScale, {
                toValue: 1.25,
                friction: 3,
                tension: 45,
                useNativeDriver: true,
            }).start(() => {
                Animated.spring(likeScale, {
                    toValue: 1,
                    friction: 4,
                    tension: 30,
                    useNativeDriver: true,
                }).start();
            });
        } else {
            likeScale.setValue(1.15);
            Animated.spring(likeScale, {
                toValue: 1,
                friction: 4,
                tension: 30,
                useNativeDriver: true,
            }).start();
        }

        try {
            const payload = {
                id: post.id,
                user_id: currentUserId || '',
                action: 'like'
            };
            await api.post('like_comment_action.php', payload);
        } catch (e) {
            console.error("Like failed", e);
            setLiked(!newStatus);
            setLikeCount(prev => !newStatus ? prev + 1 : prev - 1);
        }
    };

    const handleShare = async () => {
        try {
            const postUrl = `${WEBSITE_URL}/view_post.php?id=${post.id}`;
            const message = `Check out this post by ${post.user.name} on Sadhu Vandana:\n\n"${post.content?.substring(0, 150)}${post.content && post.content.length > 150 ? '...' : ''}"\n\nOpen in App: ${postUrl}`;
            await Share.share({ message, url: postUrl });
        } catch (error: any) {
            Alert.alert(error.message);
        }
    };

    const handlePostPress = () => {
        // @ts-ignore
        navigationRef.navigate('PostDetail', { post });
    };

    // Prepare media list
    let mediaList: string[] = [];
    if (post.media && post.media.length > 0) {
        mediaList = post.media.map(file => file.startsWith('http') ? file : `${POST_MEDIA_PATH}${file}`);
    } else if (post.image) {
        mediaList = [post.image.startsWith('http') ? post.image : `${POST_MEDIA_PATH}${post.image}`];
    }

    const isVideo = (uri: string) => {
        if (!uri) return false;
        const lower = uri.toLowerCase();
        return lower.endsWith('.mp4') || lower.endsWith('.mov') || lower.endsWith('.m4v') || lower.endsWith('.3gp') || lower.endsWith('.mkv') || lower.endsWith('.webm');
    };

    const handleDownloadPost = async () => {
        try {
            if (!mediaList || mediaList.length === 0) {
                Alert.alert("No Media", "This post doesn't have an image or video to download.");
                return;
            }
            
            const uri = mediaList[0];
            const filename = uri.split('/').pop() || 'download.jpg';
            const fileUri = FileSystem.documentDirectory + filename;

            // Simple loading toast
            Alert.alert("Downloading...", "Please wait...");

            const downloadedFile = await FileSystem.downloadAsync(uri, fileUri);

            if (downloadedFile.status === 200) {
                if (await Sharing.isAvailableAsync()) {
                    await Sharing.shareAsync(downloadedFile.uri, {
                        dialogTitle: 'Save or Share Post Media',
                    });
                } else {
                    Alert.alert("Success", "File downloaded, but sharing/saving is not available on this device.");
                }
            } else {
                Alert.alert("Error", "Failed to download media.");
            }
        } catch (error) {
            console.error("Download Error", error);
            Alert.alert("Error", "An error occurred while downloading.");
        }
    };

    const handleOptions = () => {
        if (!currentUserId) return;

        const downloadOption = {
            text: "Download/Share Media",
            onPress: handleDownloadPost
        };

        if (String(post.user.id) === String(currentUserId)) {
            Alert.alert("Manage Post", "What would you like to do?", [
                { text: "Cancel", style: "cancel" },
                downloadOption,
                {
                    text: "Delete Post",
                    style: "destructive",
                    onPress: onDeletePress
                }
            ]);
        } else {
            Alert.alert("Post Options", "What would you like to do?", [
                { text: "Cancel", style: "cancel" },
                downloadOption,
                { text: "Report Post", onPress: () => Alert.alert("Reported", "Thanks for letting us know.") }
            ]);
        }
    };

    const renderContent = (text: string) => {
        if (!text) return null;
        const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)|([a-zA-Z0-9][a-zA-Z0-9-]+\.[a-zA-Z]{2,}[^\s]*)/g;
        const parts = [];
        let lastIndex = 0;
        let match;
        while ((match = urlRegex.exec(text)) !== null) {
            const url = match[0];
            const index = match.index;
            if (index > lastIndex) {
                parts.push(
                    <Text key={`text-${lastIndex}`} style={{ color: '#1f2937' }}>
                        {text.substring(lastIndex, index)}
                    </Text>
                );
            }
            const fullUrl = url.startsWith('http') ? url : `https://${url}`;
            parts.push(
                <Text
                    key={`link-${index}`}
                    style={{ color: '#2563eb', textDecorationLine: 'underline' }}
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
        if (lastIndex < text.length) {
            parts.push(
                <Text key={`text-${lastIndex}`} style={{ color: '#1f2937' }}>
                    {text.substring(lastIndex)}
                </Text>
            );
        }
        return (
            <Text style={{ fontSize: 16, lineHeight: 20, marginBottom: 8 }}>
                {parts.length > 0 ? parts : text}
            </Text>
        );
    };

    return (
        <View style={styles.cardContainer}>
            {/* Header */}
            <TouchableOpacity style={styles.header} onPress={() => onUserPress && onUserPress()} activeOpacity={0.7}>
                <Image
                    source={{ uri: post.user.avatar || 'https://via.placeholder.com/50' }}
                    style={styles.avatar}
                    resizeMethod="resize"
                />
                <View style={styles.headerInfo}>
                    <Text style={styles.userName}>{post.user.name}</Text>
                    <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 2 }}>
                        {post.user.location ? (
                            <>
                                <Text style={styles.timeAgo} numberOfLines={1}>{post.user.location}</Text>
                                <Text style={styles.dotSeparator}>•</Text>
                            </>
                        ) : null}
                        <Text style={styles.timeAgo}>{post.timeAgo}</Text>
                    </View>
                </View>
                <TouchableOpacity style={styles.optionsButton} onPress={handleOptions}>
                    <Ionicons name="ellipsis-horizontal" size={18} color="#6b7280" />
                </TouchableOpacity>
            </TouchableOpacity>

            {/* Content Body */}
            <View style={styles.contentBody}>
                {post.content ? (
                    <View>{renderContent(post.content)}</View>
                ) : null}

                {/* Music Info Overlay & Audio Engine */}
                {(() => {
                    const music = musicData;
                    if (music && (music.type === 'youtube_music' || music.type === 'custom_music')) {
                        return (
                            <>
                                <View style={styles.musicOverlay}>
                                    <Animated.View style={[styles.musicIconContainer, { transform: [{ rotate: rotateData }] }]}>
                                        <Ionicons name="disc-outline" size={20} color="white" />
                                    </Animated.View>
                                    <View style={styles.musicTextContainer}>
                                        <Text style={styles.playingFromText}>{music.type === 'custom_music' ? 'Audio Original' : 'Official Audio'}</Text>
                                        <Text style={styles.musicTitleText} numberOfLines={1}>
                                            {music.title} • {music.artist}
                                        </Text>
                                    </View>
                                    <View style={styles.liveBadge}>
                                        <View style={styles.liveDot} />
                                        <Text style={styles.liveText}>PLAYING</Text>
                                    </View>
                                </View>

                                {shouldMountAudio && music.type === 'custom_music' && (
                                    <NativeAudioItem
                                        url={music.music_url}
                                        startTime={music.startTime}
                                        duration={music.duration}
                                        shouldPlay={audioShouldPlay}
                                        postId={post.id}
                                        isMuted={musicMuted}
                                        onReady={setSoundReady}
                                    />
                                )}
                                {shouldMountAudio && music.type === 'youtube_music' && (
                                    <View key={`yt-${post.id}-${isFocused}`} style={styles.hiddenAudio} pointerEvents="none">
                                        <WebView
                                            style={{ flex: 1 }}
                                            ref={webViewRef}
                                            source={{ uri: `https://www.youtube.com/embed/${music.youtubeId}?autoplay=1&controls=0&modestbranding=1&rel=0&showinfo=0&mute=0&iv_load_policy=3&disablekb=1` }}
                                            onMessage={(event) => {
                                                if (event.nativeEvent.data === 'yt-ready') setSoundReady(true);
                                            }}
                                            injectedJavaScript={`
                                                (function() {
                                                    const css = '#header-bar, #footer-bar, .player-control-container, #related, #comments, .video-player-control-overlay, .ad-container, .ad-display, .ytp-ad-overlay-container { display: none !important; }';
                                                    const style = document.createElement('style');
                                                    style.innerHTML = css;
                                                    document.head.appendChild(style);
                                                                                                                let jumped = false;
                                                    let sentReady = false;

                                                    window.setPlaybackStatus = (status) => {
                                                        window.remoteShouldPlay = status;
                                                    };

                                                    const loopLogic = () => {
                                                        const video = document.querySelector("video");
                                                        if (!video) return;

                                                        if (!sentReady && video.readyState >= 3) {
                                                            window.ReactNativeWebView.postMessage('yt-ready');
                                                            sentReady = true;
                                                        }

                                                        const sTime = Number(${music.startTime || 0});
                                                        const duration = Number(${music.duration || 30});
                                                        const eTime = sTime + duration;

                                                        const adShowing = document.querySelector(".ad-showing, .ad-interrupting, .ytp-ad-player-overlay, .ytp-ad-overlay-container");
                                                        if (adShowing) {
                                                            video.muted = true;
                                                            if (video.duration > 0) video.currentTime = video.duration - 0.1;
                                                            const skipBtn = document.querySelector(".ytp-ad-skip-button, .ytp-ad-skip-button-modern, .ytp-ad-skip-button-slot, .ytp-ad-skip-button-text, .ytp-ad-preview-container");
                                                            if (skipBtn) skipBtn.click();
                                                         } else {
                                                            const shouldPlay = window.remoteShouldPlay !== undefined ? window.remoteShouldPlay : ${audioShouldPlay};
                                                            video.muted = ${musicMuted ? 'true' : 'false'};
                                                            video.volume = 1.0;
                                                            
                                                            if(shouldPlay) {
                                                                if(video.paused) video.play().catch(e => {});
                                                            } else {
                                                                if(!video.paused) video.pause();
                                                            }

                                                             // Handle Start Time Jump & Loop
                                                             if (!jumped && sTime > 0 && video.currentTime < sTime) {
                                                                 video.currentTime = sTime;
                                                                 jumped = true;
                                                             }
                                                             
                                                             // Loop Logic (40ms precision)
                                                             if (video.ended || video.currentTime >= eTime || (video.currentTime < sTime - 1.5 && !video.seeking)) {
                                                                 video.currentTime = sTime;
                                                                 video.play().catch(e => {});
                                                             }

                                                            const unmuteSelectors = [".ytp-unmute", ".ytp-mute-button", ".ytp-unmute-button", "button[aria-label*='unmute']"];
                                                            unmuteSelectors.forEach(sel => {
                                                                const btn = document.querySelector(sel);
                                                                if (btn) btn.click();
                                                            });
                                                        }
                                                    };
                                                    setInterval(loopLogic, 40);
                                                })();
                                            `}
                                            allowsInlineMediaPlayback={true}
                                            mediaPlaybackRequiresUserAction={false}
                                            javaScriptEnabled={true}
                                            domStorageEnabled={true}
                                            sharedCookiesEnabled={true}
                                            androidLayerType="hardware"
                                            originWhitelist={['*']}
                                            mixedContentMode="always"
                                            userAgent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"
                                        />
                                    </View>
                                )}
                            </>
                        );
                    }
                    return null;
                })()}
            </View>

            <TouchableOpacity
                activeOpacity={0.95}
                onPress={() => setPaused(!paused)}
                style={{ alignSelf: 'center' }}
            >
                {/* Media Carousel */}
                {mediaList.length > 0 && (
                    <View style={{ width: cardWidth, borderRadius: 12, overflow: 'hidden' }}>
                        <ScrollView
                            horizontal
                            pagingEnabled
                            showsHorizontalScrollIndicator={false}
                            contentContainerStyle={{ alignItems: 'center' }}
                            onScroll={(event) => {
                                const slide = Math.round(event.nativeEvent.contentOffset.x / cardWidth);
                                if (slide !== activeMediaIndex) {
                                    setActiveMediaIndex(slide);
                                }
                            }}
                            scrollEventThrottle={16}
                        >
                            {mediaList.map((uri, index) => (
                                <View key={index} style={{ width: cardWidth, aspectRatio: isVideo(uri) ? 9 / 16 : undefined, backgroundColor: '#f9fafb', alignItems: 'center', justifyContent: 'center' }}>
                                    {isVideo(uri) ? (
                                        <View style={{ width: '100%', height: '100%' }}>
                                            <VideoItem
                                                uri={uri}
                                                poster={mediaList.find(m => !isVideo(m)) || (mediaList[0]?.toLowerCase().endsWith('.mp4') ? undefined : mediaList[0])}
                                                shouldPlay={index === 0 && actualShouldPlay}
                                                forwardedRef={index === 0 ? videoRef : null}
                                                onReady={() => setVideoReady(true)}
                                                onBuffering={setIsBuffering}
                                                isMuted={musicMuted}
                                            />
                                            {isBuffering && (
                                                <View style={[StyleSheet.absoluteFill, { justifyContent: 'center', alignItems: 'center' }]}>
                                                    <ActivityIndicator color="white" size="large" />
                                                </View>
                                            )}
                                        </View>
                                    ) : (
                                        <PostImage uri={uri} />
                                    )}
                                </View>
                            ))}
                        </ScrollView>
                        {mediaList.length > 1 && (
                            <View style={styles.mediaBadge}>
                                <Text style={styles.mediaBadgeText}>{activeMediaIndex + 1}/{mediaList.length}</Text>
                            </View>
                        )}
                        {/* Apply Filter Overlay if meta contains filterId */}
                        {(() => {
                            try {
                                if (post.link) {
                                    const meta = typeof post.link === 'string' ? JSON.parse(post.link) : post.link;
                                    if (meta.filterId && meta.filterId !== 'none') {
                                        return <FilterOverlay filterId={meta.filterId} />;
                                    }
                                }
                            } catch (e) { }
                            return null;
                        })()}
                    </View>
                )}
            </TouchableOpacity>

            {/* Actions */}
            <View style={styles.actions}>
                <View style={styles.leftActions}>
                    <TouchableOpacity style={styles.actionButton} onPress={toggleLike} activeOpacity={0.7}>
                        <Animated.View style={{ transform: [{ scale: likeScale }] }}>
                            <Ionicons
                                name={liked ? "heart" : "heart-outline"}
                                size={24}
                                color={liked ? "#ea580c" : "#374151"}
                            />
                        </Animated.View>
                        <Text style={[styles.actionText, liked && { color: '#ea580c', fontWeight: '600' }]}>
                            {likeCount}
                        </Text>
                    </TouchableOpacity>

                    <TouchableOpacity style={styles.actionButton} onPress={handlePostPress} activeOpacity={0.7}>
                        <Ionicons name="chatbubble-outline" size={23} color="#374151" />
                        <Text style={styles.actionText}>{post.comments}</Text>
                    </TouchableOpacity>

                    <TouchableOpacity style={styles.actionButton} onPress={handleShare} activeOpacity={0.7}>
                        <Ionicons name="paper-plane-outline" size={23} color="#374151" />
                    </TouchableOpacity>

                    {post.link && (
                        <TouchableOpacity
                            style={[styles.actionButton, { marginLeft: 4 }]}
                            onPress={() => setMusicMuted(!musicMuted)}
                            activeOpacity={0.7}
                        >
                            <Ionicons
                                name={musicMuted ? "volume-mute" : "volume-high"}
                                size={22}
                                color={musicMuted ? "#6b7280" : "#ea580c"}
                            />
                        </TouchableOpacity>
                    )}
                </View>

                <TouchableOpacity activeOpacity={0.7}>
                    <Ionicons name="bookmark-outline" size={22} color="#374151" />
                </TouchableOpacity>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    cardContainer: {
        backgroundColor: 'white',
        marginHorizontal: 12,
        marginVertical: 8,
        borderRadius: 16,
        paddingBottom: 8,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.04,
        shadowRadius: 8,
        elevation: 3,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 12,
        paddingHorizontal: 16,
    },
    avatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: '#e5e7eb',
        borderWidth: 1.5,
        borderColor: '#fed7aa',
    },
    headerInfo: {
        marginLeft: 12,
        flex: 1,
    },
    userName: {
        fontWeight: 'bold',
        color: '#111827',
        fontSize: 15,
    },
    timeAgo: {
        color: '#6b7280',
        fontSize: 11,
    },
    dotSeparator: {
        color: '#9ca3af',
        fontSize: 11,
        marginHorizontal: 5,
    },
    optionsButton: {
        padding: 8,
    },
    contentBody: {
        paddingHorizontal: 16,
        paddingBottom: 8,
    },
    musicOverlay: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(255, 247, 237, 0.75)',
        paddingVertical: 10,
        paddingHorizontal: 14,
        borderRadius: 20,
        marginTop: 8,
        marginBottom: 10,
        borderWidth: 1,
        borderColor: 'rgba(255, 237, 213, 0.6)',
    },
    musicIconContainer: {
        backgroundColor: '#f97316',
        padding: 6,
        borderRadius: 8,
    },
    musicTextContainer: {
        marginLeft: 12,
        flex: 1,
    },
    playingFromText: {
        fontSize: 10,
        fontWeight: 'bold',
        color: '#c2410c',
        textTransform: 'uppercase',
        letterSpacing: 0.5,
        marginBottom: 2,
    },
    musicTitleText: {
        fontSize: 14,
        fontWeight: 'bold',
        color: '#1f2937',
    },
    liveBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 8,
        paddingVertical: 4,
        backgroundColor: 'white',
        borderRadius: 20,
        borderWidth: 1,
        borderColor: '#ffedd5',
    },
    liveDot: {
        width: 6,
        height: 6,
        borderRadius: 3,
        backgroundColor: '#f97316',
    },
    liveText: {
        fontSize: 9,
        fontWeight: '900',
        color: '#f97316',
        marginLeft: 4,
        letterSpacing: 1,
    },
    hiddenAudio: {
        height: 1,
        width: 1,
        position: 'absolute',
        bottom: -10,
        right: -10,
        opacity: 0.01,
    },
    videoDuration: {
        position: 'absolute',
        bottom: 8,
        right: 8,
        backgroundColor: 'rgba(0,0,0,0.6)',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 4,
    },
    videoDurationText: {
        color: 'white',
        fontSize: 12,
        fontWeight: 'bold',
    },
    mediaBadge: {
        position: 'absolute',
        bottom: 8,
        right: 8,
        backgroundColor: 'rgba(0,0,0,0.5)',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 20,
    },
    mediaBadgeText: {
        color: 'white',
        fontSize: 12,
        fontWeight: 'bold',
    },
    actions: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 14,
        borderTopWidth: 1,
        borderTopColor: '#f3f4f6',
        marginTop: 8,
    },
    leftActions: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    actionButton: {
        flexDirection: 'row',
        alignItems: 'center',
        marginRight: 24,
        padding: 4,
    },
    actionText: {
        color: '#374151',
        marginLeft: 6,
        fontSize: 13,
        fontWeight: '500',
    },
    muteButton: {
        padding: 5,
        marginLeft: 10,
        backgroundColor: 'rgba(255,255,255,0.8)',
        borderRadius: 20,
        width: 32,
        height: 32,
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: 1,
        borderColor: '#ffedd5',
    },
    vignettePart: {
        position: 'absolute',
        backgroundColor: 'rgba(0,0,0,0.25)',
    }
});

export default PostCard;
