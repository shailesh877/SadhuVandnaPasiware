import React, { useEffect, useState, useRef, useCallback } from 'react';
import { View, Text, TouchableOpacity, Image, Dimensions, ActivityIndicator, StyleSheet, Share, Alert, Animated } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Video, ResizeMode, Audio } from 'expo-av';
import { FlatList, ViewToken } from 'react-native';
import { useIsFocused } from '@react-navigation/native';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useLanguage } from '../../context/LanguageContext';
import { WebView } from 'react-native-webview';

const { width, height } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
const POST_MEDIA_PATH = `${BASE_URL_ROOT}/uploads/posts/`;

const isVideo = (uri: string) => {
    if (!uri) return false;
    const lower = uri.toLowerCase();
    return lower.endsWith('.mp4') || lower.endsWith('.mov') || lower.endsWith('.m4v') || lower.endsWith('.3gp') || lower.endsWith('.mkv');
};

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

const NativeAudioItem = ({ url, startTime, duration, shouldPlay, onReady }: any) => {
    const soundRef = useRef<Audio.Sound | null>(null);
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        const loadSound = async () => {
            try {
                if (soundRef.current) {
                    await soundRef.current.unloadAsync();
                }
                const { sound } = await Audio.Sound.createAsync(
                    { uri: url },
                    {
                        shouldPlay: false,
                        positionMillis: (startTime || 0) * 1000,
                        isLooping: false,
                        volume: 1.0
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
                console.error("Native Audio Load Error:", e);
                if (onReady) onReady(true); // Don't block video if audio fails
            }
        };

        if (url) {
            setIsLoaded(false);
            if (onReady) onReady(false);
            loadSound();
        }

        return () => {
            if (soundRef.current) {
                const s = soundRef.current;
                soundRef.current = null;
                s.stopAsync()
                    .then(() => s.unloadAsync())
                    .catch(() => {});
            }
        };
    }, [url]);

    useEffect(() => {
        if (isLoaded && soundRef.current) {
            if (shouldPlay) {
                soundRef.current.playAsync().catch(() => {});
            } else {
                soundRef.current.pauseAsync().catch(() => {});
            }
        }
    }, [shouldPlay, isLoaded]);

    return null;
};

const ReelsItem = ({ item, index, activeIndex, focused, currentUserId, layoutHeight, onUserPress, onCommentPress }: any) => {
    const active = index === activeIndex;
    const isNearby = active; // Aggressive optimization: Only load the active video to prevent crashes
    const videoRef = useRef<Video>(null);
    useEffect(() => {
        return () => {
            if (videoRef.current) {
                videoRef.current.unloadAsync();
            }
        };
    }, []);
    const [liked, setLiked] = useState(item.user_liked);
    const [likeCount, setLikeCount] = useState(parseInt(item.likes) || 0);
    const [paused, setPaused] = useState(false);
    const [soundReady, setSoundReady] = useState(false);
    const [isBuffering, setIsBuffering] = useState(false);
    const [followStatus, setFollowStatus] = useState<string>('loading'); // loading, none, pending, accepted
    const tapCount = useRef<number>(0);
    const timer = useRef<any>(null);
    const heartScale = useRef(new Animated.Value(0)).current;

    // Sync state when item changes (e.g. on refresh)
    useEffect(() => {
        setLiked(item.user_liked);
        setLikeCount(parseInt(item.likes) || 0);
    }, [item.user_liked, item.likes]);

    useEffect(() => {
        const hasMusic = !!item.link;
        if (active && focused && !paused) {
            if (!hasMusic || soundReady) {
                // Video plays logic is handled by its 'shouldPlay' prop directly for better sync
                // but we might need to nudge it if it's stuck
            }
            fetchFollowStatus();
        } else {
            videoRef.current?.pauseAsync();
            if (!focused) {
                videoRef.current?.unloadAsync(); // Force unload when leaving screen
            }
        }
    }, [active, focused, paused, soundReady]);

    const fetchFollowStatus = async () => {
        if (!currentUserId || !item.user_id || item.user_id === currentUserId) {
            setFollowStatus('none');
            return;
        }
        try {
            const res = await api.post('api_follow.php?action=get_counts', {
                current_user_id: currentUserId,
                user_id: item.user_id
            });
            if (res.data.ok) {
                if (res.data.is_following) setFollowStatus('accepted');
                else if (res.data.is_requested) setFollowStatus('pending');
                else setFollowStatus('none');
            }
        } catch (e) {
            console.error("Follow status fetch failed", e);
        }
    };

    const handleToggleFollow = async () => {
        if (!currentUserId || !item.user_id) return;

        try {
            const formData = new FormData();
            formData.append('current_user_id', currentUserId);
            formData.append('user_id', item.user_id);

            const res = await api.post('api_follow.php?action=follow', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.ok) {
                if (res.data.status === 'requested') setFollowStatus('pending');
                else if (res.data.status === 'connected') setFollowStatus('accepted');
                else setFollowStatus('none');
            }
        } catch (error) {
            Alert.alert("Error", "Failed to update follow status");
        }
    };

    useEffect(() => {
        return () => {
            if (timer.current) clearTimeout(timer.current);
        };
    }, []);

    useEffect(() => {
        if (!active || !focused) {
            setPaused(false);
            tapCount.current = 0;
            if (timer.current) clearTimeout(timer.current);
            if (!focused) {
                videoRef.current?.unloadAsync();
            }
        }
    }, [active, focused]);

    const toggleLike = async (manual = true) => {
        const newStatus = manual ? !liked : true;
        if (newStatus === liked && !manual) return;

        setLiked(newStatus);
        setLikeCount(prev => newStatus ? prev + 1 : prev - 1);

        try {
            const payload = {
                id: item.id,
                user_id: currentUserId || '',
                action: 'like'
            };
            await api.post('like_comment_action.php', payload);
        } catch (e) {
            setLiked(!newStatus);
            setLikeCount(prev => !newStatus ? prev + 1 : prev - 1);
        }
    };

    const handlePress = () => {
        const now = Date.now();
        const DOUBLE_PRESS_DELAY = 300;
        tapCount.current += 1;

        if (tapCount.current === 2) {
            if (timer.current) clearTimeout(timer.current);
            tapCount.current = 0;
            if (!liked) toggleLike(false);
            showHeartAnimation();
        } else {
            if (timer.current) clearTimeout(timer.current);
            timer.current = setTimeout(() => {
                setPaused(!paused);
                tapCount.current = 0;
            }, DOUBLE_PRESS_DELAY);
        }
    };

    const showHeartAnimation = () => {
        Animated.sequence([
            Animated.spring(heartScale, {
                toValue: 1,
                useNativeDriver: true,
                friction: 3,
            }),
            Animated.timing(heartScale, {
                toValue: 0,
                duration: 100,
                useNativeDriver: true,
                delay: 400,
            }),
        ]).start();
    };

    const handleShare = async () => {
        try {
            const postUrl = `${WEBSITE_URL}/view_post.php?id=${item.id}`;
            const message = `Check out this reel by ${item.name} on Sadhu Vandana:\n\nOpen in App: ${postUrl}`;
            await Share.share({ message, url: postUrl });
        } catch (error: any) {
            Alert.alert(error.message);
        }
    };

    const videoUri = item.media.find((m: string) => isVideo(m));
    const fullVideoUri = videoUri ? (videoUri.startsWith('http') ? videoUri : `${POST_MEDIA_PATH}${videoUri}`) : null;

    if (!fullVideoUri) return null;

    return (
        <View style={[styles.reelContainer, { height: layoutHeight }]}>
            <TouchableOpacity
                activeOpacity={1}
                onPress={handlePress}
                style={[styles.backgroundVideo, { height: layoutHeight }]}
            >
                {isNearby ? (
                        <Video
                            ref={videoRef}
                            source={{ uri: fullVideoUri }}
                            style={styles.backgroundVideo}
                            resizeMode={ResizeMode.COVER}
                            isLooping
                            shouldPlay={active && focused && !paused}
                            usePoster={true}
                            posterSource={{ uri: item.media.find((m: string) => !isVideo(m)) ? (item.media.find((m: string) => !isVideo(m)).startsWith('http') ? item.media.find((m: string) => !isVideo(m)) : `${POST_MEDIA_PATH}${item.media.find((m: string) => !isVideo(m))}`) : (fullVideoUri || '') }}
                            posterStyle={{ resizeMode: ResizeMode.COVER }}
                            isMuted={false} // Allow original audio to mix with music
                            onPlaybackStatusUpdate={(status: any) => {
                            if (status.isLoaded) {
                                if (status.isBuffering !== isBuffering) {
                                    setIsBuffering(status.isBuffering);
                                }
                            }
                        }}
                        onError={(e) => {
                            console.error("[Reels Video] Error:", e, "URI:", fullVideoUri);
                        }}
                    />
                ) : (
                    <View style={[styles.backgroundVideo, { backgroundColor: '#111', justifyContent: 'center', alignItems: 'center' }]}>
                        <ActivityIndicator color="white" />
                    </View>
                )}

                <Animated.View
                    style={[styles.pauseOverlay, {
                        transform: [{ scale: heartScale }],
                        opacity: heartScale.interpolate({ inputRange: [0, 0.1, 1], outputRange: [0, 1, 1] })
                    }]}
                    pointerEvents="none"
                >
                    <Ionicons name="heart" size={120} color="white" />
                </Animated.View>

                {paused && (
                    <View style={styles.pauseOverlay} pointerEvents="none">
                        <Ionicons name="play" size={80} color="rgba(255,255,255,0.5)" />
                    </View>
                )}
            </TouchableOpacity>

            <View style={styles.bottomOverlay}>
                <TouchableOpacity style={styles.userInfo} onPress={() => onUserPress(item.user_id)}>
                    <Image
                        source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/50' }}
                        style={styles.avatar}
                    />
                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                        <Text style={styles.userName}>{item.name}</Text>
                        {item.user_id !== currentUserId && followStatus !== 'accepted' && (
                            <TouchableOpacity
                                style={[
                                    styles.followButton,
                                    followStatus === 'pending' && { backgroundColor: 'rgba(255,255,255,0.2)' }
                                ]}
                                onPress={handleToggleFollow}
                            >
                                <Text style={styles.followText}>
                                    {followStatus === 'loading' ? '...' :
                                        followStatus === 'pending' ? 'Requested' : 'Follow'}
                                </Text>
                            </TouchableOpacity>
                        )}
                    </View>
                </TouchableOpacity>

                <Text style={styles.description} numberOfLines={2}>
                    {item.description}
                </Text>

                {/* Music Info Overlay & Audio Engine */}
                {(() => {
                    try {
                        if (item.link && item.link.startsWith('{')) {
                            const music = JSON.parse(item.link);
                            if (music.type === 'youtube_music' || music.type === 'custom_music') {
                                return (
                                    <>
                                         <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 10 }}>
                                            <Ionicons name="musical-notes" size={14} color="white" />
                                            <Text style={{ color: 'white', fontSize: 13, fontWeight: '600', marginLeft: 6 }} numberOfLines={1}>
                                                {music.title} • {music.artist}
                                            </Text>
                                        </View>

                                        {/* Audio Engine */}
                                        {active && focused && music.type === 'custom_music' && (
                                            <NativeAudioItem
                                                url={music.music_url}
                                                startTime={music.startTime}
                                                duration={music.duration}
                                                shouldPlay={!paused && !isBuffering}
                                                onReady={setSoundReady}
                                            />
                                        )}

                                         {active && focused && music.type === 'youtube_music' && (
                                             <View key={`reel-yt-${item.id}-${focused}`} style={{ height: 1, width: 1, position: 'absolute', bottom: -10, right: -10, opacity: 0.01 }} pointerEvents="none">
                                                 <WebView
                                                     source={{
                                                         uri: `https://m.youtube.com/watch?v=${music.id}${music.startTime > 0 ? '&t=' + music.startTime : ''}`
                                                     }}
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

                                                            const loopLogic = () => {
                                                                const video = document.querySelector("video");
                                                                if (!video) return;

                                                                if (!sentReady && video.readyState >= 3) {
                                                                    window.ReactNativeWebView.postMessage('yt-ready');
                                                                    sentReady = true;
                                                                }

                                                                const sTime = Number(${music.startTime || 0});
                                                                const duration = Number(${music.duration || 30});
                                                                const eTime = sTime + duration;                                                                 const adShowing = document.querySelector(".ad-showing, .ad-interrupting, .ytp-ad-player-overlay, .ytp-ad-overlay-container");
                                                                if (adShowing) {
                                                                    video.muted = true;
                                                                    if (video.duration > 0) video.currentTime = video.duration - 0.1;
                                                                    const skipBtn = document.querySelector(".ytp-ad-skip-button, .ytp-ad-skip-button-modern, .ytp-ad-skip-button-slot, .ytp-ad-skip-button-text, .ytp-ad-preview-container");
                                                                    if (skipBtn) skipBtn.click();
                                                                } else {
                                                                    const shouldPlay = ${!paused && !isBuffering};
                                                                    video.muted = false;
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
                                                                     if (video.currentTime >= eTime || (video.currentTime < sTime - 1.5 && !video.seeking)) {
                                                                         video.currentTime = sTime;
                                                                         video.play().catch(e => {});
                                                                     }

                                                                    const unmuteSelectors = [".ytp-unmute", ".ytp-mute-button", ".ytp-unmute-button", "button[aria-label*='unmute']"];
                                                                    unmuteSelectors.forEach(sel => {
                                                                        const btn = document.querySelector(sel);
                                                                        if (btn) btn.click();
                                                                    });
                                                                }
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
                        }
                    } catch (e) { }
                    return null;
                })()}
            </View>
                        
                        {/* Apply Filter Overlay if meta contains filterId */}
                        {(() => {
                            try {
                                if (item.link) {
                                    const meta = typeof item.link === 'string' ? JSON.parse(item.link) : item.link;
                                    if (meta.filterId && meta.filterId !== 'none') {
                                        return <FilterOverlay filterId={meta.filterId} />;
                                    }
                                }
                            } catch(e) {}
                            return null;
                        })()}

                        {/* Overlay Controls */}
            <View style={styles.rightOverlay}>
                <TouchableOpacity style={styles.iconButton} onPress={() => toggleLike(true)}>
                    <Ionicons name={liked ? "heart" : "heart-outline"} size={32} color={liked ? "#ff3b30" : "white"} />
                    <Text style={styles.iconText}>{likeCount}</Text>
                </TouchableOpacity>

                <TouchableOpacity style={styles.iconButton} onPress={() => onCommentPress(item.id)}>
                    <Ionicons name="chatbubble-outline" size={28} color="white" />
                    <Text style={styles.iconText}>{item.comments?.length || 0}</Text>
                </TouchableOpacity>

                <TouchableOpacity style={styles.iconButton} onPress={handleShare}>
                    <Ionicons name="paper-plane-outline" size={28} color="white" />
                </TouchableOpacity>

                <TouchableOpacity style={styles.iconButton}>
                    <Ionicons name="ellipsis-vertical" size={24} color="white" />
                </TouchableOpacity>
            </View>
        </View>
    );
};

const ReelsScreen = ({ navigation, route }: any) => {
    const { t } = useLanguage();
    const isFocused = useIsFocused();
    const initialReelId = route.params?.initialReelId || route.params?.startPostId;
    const filterUserId = route.params?.filter_user_id;
    const showBack = route.params?.showBack || !!filterUserId;
    const [reels, setReels] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [isFetchingMore, setIsFetchingMore] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const [currentUserId, setCurrentUserId] = useState<string | null>(null);
    const [initialPage, setInitialPage] = useState(0);
    const [layoutHeight, setLayoutHeight] = useState(height);
    const flatListRef = useRef<FlatList>(null);
    const viewabilityConfig = useRef({
        itemVisiblePercentThreshold: 80,
    }).current;

    const onViewableItemsChanged = useRef(({ viewableItems }: { viewableItems: ViewToken[] }) => {
        if (viewableItems.length > 0) {
            const index = viewableItems[0].index;
            if (index !== null) {
                setActiveIndex(index);
                if (index >= reels.length - 3) {
                    loadReels(true);
                }
            }
        }
    }).current;

    useEffect(() => {
        loadReels();
        AsyncStorage.getItem('user').then(u => {
            if (u) setCurrentUserId(JSON.parse(u).id);
        });
    }, []);

    useEffect(() => {
        if (initialReelId && reels.length > 0) {
            const idx = reels.findIndex((r: any) => r.id.toString() === initialReelId.toString());
            if (idx !== -1) {
                setInitialPage(idx);
                setActiveIndex(idx);
                // Programmatically jump if already mounted
                setTimeout(() => {
                    flatListRef.current?.scrollToIndex({ index: idx, animated: false });
                }, 100);
            }
        }
    }, [initialReelId, reels.length]);

    useEffect(() => {
        const unsubscribe = navigation.addListener('tabPress', (e: any) => {
            if (isFocused) {
                loadReels();
                setActiveIndex(0);
                setInitialPage(0);
            }
        });
        const blurUnsubscribe = navigation.addListener('blur', () => {
            // Aggressively unload all videos when leaving the screen
            // The active video ref will handle its own cleanup in ReelsItem
        });
        return () => {
            unsubscribe();
            blurUnsubscribe();
        };
    }, [navigation, isFocused]);

    const loadReels = async (isLoadMore = false) => {
        if (isLoadMore) {
            if (isFetchingMore) return;
            setIsFetchingMore(true);
        } else {
            setLoading(true);
        }
        try {
            const uStr = await AsyncStorage.getItem('user');
            let uid = '';
            if (uStr) {
                const u = JSON.parse(uStr);
                uid = u.id;
                setCurrentUserId(uid);
            }

            const offset = isLoadMore ? (activeIndex + 20) : 0; // Better offset for large batch
            let url = `get_posts.php?user_id=${uid}&limit=150&offset=${offset}`;
            if (filterUserId) {
                url += `&filter_user_id=${filterUserId}`;
            }
            const res = await api.get(url);

            if (res.data.status === 'success' && Array.isArray(res.data.data)) {
                // Filter only videos on frontend as requested
                const videoPosts = res.data.data.filter((post: any) =>
                    post.media && post.media.some((m: string) => isVideo(m))
                );

                if (isLoadMore) {
                    setReels((prev: any[]) => {
                        const existingIds = new Set(prev.map((p: any) => p.id));
                        const uniqueNew = videoPosts.filter((p: any) => !existingIds.has(p.id));
                        return [...prev, ...uniqueNew];
                    });
                } else {
                    setReels(videoPosts);
                    // If initialReelId is provided, find its index
                    if (initialReelId) {
                        const idx = videoPosts.findIndex((r: any) => r.id.toString() === initialReelId.toString());
                        if (idx !== -1) {
                            setInitialPage(idx);
                            setActiveIndex(idx);
                        }
                    }
                }
            }
        } catch (error) {
            console.error(error);
        } finally {
            if (isLoadMore) setIsFetchingMore(false);
            else setLoading(false);
        }
    };

    const renderItem = useCallback(({ item, index }: any) => (
        <ReelsItem
            item={item}
            index={index}
            activeIndex={activeIndex}
            focused={isFocused}
            currentUserId={currentUserId}
            layoutHeight={layoutHeight}
            onUserPress={(uid: string) => navigation.navigate('PublicProfile', { userId: uid })}
            onCommentPress={(pid: string) => navigation.navigate('Comments', { postId: pid })}
        />
    ), [activeIndex, isFocused, currentUserId]);

    if (loading) {
        return (
            <View style={styles.loadingContainer}>
                <ActivityIndicator size="large" color="#ea580c" />
            </View>
        );
    }

    if (reels.length === 0) {
        return (
            <View style={styles.emptyContainer}>
                <Ionicons name="videocam-outline" size={64} color="#ccc" />
                <Text style={styles.emptyText}>No reels found</Text>
            </View>
        );
    }

    return (
        <View style={[styles.container, { height: layoutHeight }]} onLayout={(e) => setLayoutHeight(e.nativeEvent.layout.height)}>
            <SafeAreaView style={styles.headerOverlay} edges={['top']}>
                <View style={styles.headerContent}>
                    {showBack && (
                        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
                            <Ionicons name="arrow-back" size={28} color="white" />
                        </TouchableOpacity>
                    )}
                    <Text style={styles.headerTitle}>{t('reels')}</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('CreateReel')} style={styles.cameraButton}>
                        <Ionicons name="camera-outline" size={28} color="white" />
                    </TouchableOpacity>
                </View>
            </SafeAreaView>

            <FlatList
                ref={flatListRef}
                data={reels}
                renderItem={renderItem}
                keyExtractor={(item) => item.id.toString()}
                pagingEnabled
                showsVerticalScrollIndicator={false}
                onViewableItemsChanged={onViewableItemsChanged}
                viewabilityConfig={viewabilityConfig}
                initialScrollIndex={initialPage > 0 ? initialPage : undefined}
                getItemLayout={(_, index) => ({
                    length: layoutHeight,
                    offset: layoutHeight * index,
                    index,
                })}
                onEndReachedThreshold={0.5}
                removeClippedSubviews={true}
                maxToRenderPerBatch={2}
                windowSize={3}
                decelerationRate="fast"
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: 'black',
    },
    pagerView: {
        flex: 1,
    },
    reelContainer: {
        width: width,
        height: height,
        position: 'relative',
    },
    backgroundVideo: {
        width: width,
        height: height,
        position: 'absolute',
    },
    pauseOverlay: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: 'rgba(0,0,0,0.1)',
    },
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: 'black',
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: 'black',
    },
    emptyText: {
        color: '#888',
        marginTop: 10,
        fontSize: 16,
    },
    headerOverlay: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        zIndex: 10,
    },
    headerContent: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 15,
        paddingVertical: 10,
    },
    backButton: {
        marginRight: 10,
    },
    headerTitle: {
        color: 'white',
        fontSize: 20,
        fontWeight: 'bold',
        flex: 1,
    },
    cameraButton: {
        padding: 10,
        marginRight: 5,
    },
    bottomOverlay: {
        position: 'absolute',
        bottom: 20,
        left: 0,
        right: 80,
        padding: 20,
    },
    userInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 10,
    },
    avatar: {
        width: 36,
        height: 36,
        borderRadius: 18,
        borderWidth: 1,
        borderColor: 'white',
    },
    userName: {
        color: 'white',
        fontWeight: 'bold',
        fontSize: 14,
        marginLeft: 10,
    },
    followButton: {
        marginLeft: 10,
        paddingHorizontal: 8,
        paddingVertical: 2,
        borderRadius: 4,
        borderWidth: 1,
        borderColor: 'white',
    },
    followText: {
        color: 'white',
        fontSize: 12,
        fontWeight: 'bold',
    },
    description: {
        color: 'white',
        fontSize: 13,
        lineHeight: 18,
    },
    rightOverlay: {
        position: 'absolute',
        bottom: 20,
        right: 0,
        width: 70,
        alignItems: 'center',
        paddingVertical: 20,
    },
    iconButton: {
        alignItems: 'center',
        marginBottom: 20,
    },
    iconText: {
        color: 'white',
        fontSize: 12,
        marginTop: 4,
        fontWeight: '600',
    },
    vignettePart: {
        position: 'absolute',
        backgroundColor: 'rgba(0,0,0,0.25)',
    }
});

export default ReelsScreen;
