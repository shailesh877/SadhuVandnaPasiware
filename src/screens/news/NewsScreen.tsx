import React, { useEffect, useState, useRef } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Dimensions, Share, Modal, RefreshControl, Animated, Easing, Alert, Linking, ScrollView, TextInput } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import { WebView } from 'react-native-webview';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

const { width } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const NEWS_URL = `${BASE_URL_ROOT}/uploads/news/`;

const TickerItem = ({ text }: { text: string }) => {
    return (
        <View className="flex-row items-center mr-8">
            <Text className="text-white text-sm font-bold tracking-widest mr-2">📰</Text>
            <Text className="text-white font-semibold text-sm">{text}</Text>
        </View>
    );
};

const NewsTicker = ({ tickerData, onClose }: { tickerData: string[]; onClose: () => void }) => {
    const scrollAnim = useRef(new Animated.Value(0)).current;
    const [contentWidth, setContentWidth] = useState(0);

    useEffect(() => {
        let animation: Animated.CompositeAnimation | null = null;
        if (contentWidth > 0) {
            scrollAnim.setValue(width);
            animation = Animated.loop(
                Animated.timing(scrollAnim, {
                    toValue: -contentWidth,
                    duration: (contentWidth + width) * 15, // smooth speed
                    easing: Easing.linear,
                    useNativeDriver: true,
                })
            );
            animation.start();
        }
        return () => {
            if (animation) {
                animation.stop();
            }
        };
    }, [contentWidth]);

    if (!tickerData || tickerData.length === 0) return null;

    return (
        <View className="bg-red-600 flex-row items-center h-9 overflow-hidden w-full shadow-sm">
            <View className="bg-red-700 h-full justify-center px-3 z-10" style={{ borderRightWidth: 1.5, borderRightColor: 'rgba(255,255,255,0.2)' }}>
                <Text className="text-white font-extrabold text-[10px] uppercase tracking-wider">🚨 Breaking</Text>
            </View>
            <View className="flex-1 overflow-hidden relative justify-center">
                <Animated.View 
                    style={{ 
                        flexDirection: 'row', 
                        alignItems: 'center', 
                        position: 'absolute',
                        left: 0,
                        transform: [{ translateX: scrollAnim }] 
                    }}
                    onLayout={(e) => setContentWidth(e.nativeEvent.layout.width)}
                >
                    {tickerData.map((item, index) => (
                        <TickerItem key={index} text={item} />
                    ))}
                    {/* Duplicate to ensure smooth scrolling space */}
                    {tickerData.map((item, index) => (
                        <TickerItem key={'dup_'+index} text={item} />
                    ))}
                </Animated.View>
            </View>
            <TouchableOpacity onPress={onClose} className="px-3 h-full justify-center items-center z-10 bg-red-700/50">
                <Ionicons name="close" size={14} color="white" />
            </TouchableOpacity>
        </View>
    );
};

const CATEGORY_MAP = [
    { name: 'All', icon: '🌐' },
    { name: 'ताज़ा खबर', icon: '⚡' },
    { name: 'राष्ट्रीय', icon: '🇮🇳' },
    { name: 'राज्य', icon: '🏛️' },
    { name: 'जिला', icon: '📍' },
    { name: 'राजनीति', icon: '🗳️' },
    { name: 'धर्म', icon: '🛕' },
    { name: 'शिक्षा', icon: '🎓' },
    { name: 'खेल', icon: '🏆' },
    { name: 'व्यापार', icon: '💼' },
    { name: 'कृषि', icon: '🌾' },
    { name: 'मनोरंजन', icon: '🎬' },
    { name: 'सामाजिक', icon: '🤝' },
    { name: 'दुर्घटना', icon: '⚠️' },
    { name: 'मृत्यु/श्रद्धांजलि', icon: '🕯️' },
    { name: 'विज्ञापन', icon: '📢' }
];

const NewsScreen = ({ navigation }: any) => {
    const [activeTab, setActiveTab] = useState<'apna' | 'ddnews' | 'aastha'>('apna');
    const [selectedCategory, setSelectedCategory] = useState<string>('All');
    
    // Apna News State
    const [news, setNews] = useState<any[]>([]);
    const [tickerData, setTickerData] = useState<string[]>([]);
    const [loading, setLoading] = useState(true);
    
    // DD News State
    const [ytNews, setYtNews] = useState<any[]>([]);
    const [ytLoading, setYtLoading] = useState(false);
    
    // Aastha State
    const [aasthaNews, setAasthaNews] = useState<any[]>([]);
    const [aasthaLoading, setAasthaLoading] = useState(false);
    
    // Refreshing State
    const [refreshing, setRefreshing] = useState(false);
    const [ytRefreshing, setYtRefreshing] = useState(false);
    const [aasthaRefreshing, setAasthaRefreshing] = useState(false);
    
    // Video Player Modal State
    const [selectedVideoId, setSelectedVideoId] = useState<string | null>(null);

    // Premium UI States
    const [searchQuery, setSearchQuery] = useState('');
    const [bookmarks, setBookmarks] = useState<string[]>([]);
    const [showTicker, setShowTicker] = useState(true);
    const shimmerAnim = useRef(new Animated.Value(0.4)).current;

    useEffect(() => {
        const loadingActive = loading || ytLoading || aasthaLoading;
        if (loadingActive) {
            Animated.loop(
                Animated.sequence([
                    Animated.timing(shimmerAnim, {
                        toValue: 1.0,
                        duration: 850,
                        useNativeDriver: true
                    }),
                    Animated.timing(shimmerAnim, {
                        toValue: 0.4,
                        duration: 850,
                        useNativeDriver: true
                    })
                ])
            ).start();
        } else {
            shimmerAnim.setValue(0.4);
        }
    }, [loading, ytLoading, aasthaLoading]);

    const filteredNews = (selectedCategory === 'All'
        ? news
        : news.filter((item: any) => item.category === selectedCategory)
    ).filter((item: any) => 
        !searchQuery.trim() || 
        item.title?.toLowerCase().includes(searchQuery.toLowerCase()) || 
        item.description?.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const isMounted = useRef(true);

    // Anchor application status
    const [userId, setUserId] = useState<number | null>(null);
    const [anchorStatus, setAnchorStatus] = useState<string | null>(null);
    const [isAnchorPaid, setIsAnchorPaid] = useState<boolean>(false);
    const [anchorFee, setAnchorFee] = useState<number>(100);
    const [anchorPaymentUrl, setAnchorPaymentUrl] = useState<string>('');

    const fetchAnchorStatus = async () => {
        try {
            const uStr = await AsyncStorage.getItem('user');
            const user = uStr ? JSON.parse(uStr) : null;
            if (user && user.id) {
                setUserId(user.id);
                const res = await api.get(`/check_anchor_status.php?user_id=${user.id}`);
                if (res.data && res.data.status === 'success') {
                    setAnchorStatus(res.data.anchor_status); // Pending, Approved, Rejected, null
                    setIsAnchorPaid(!!res.data.paid);
                    if (res.data.fee) setAnchorFee(res.data.fee);
                    if (res.data.payment_url) setAnchorPaymentUrl(res.data.payment_url);
                }
            }
        } catch (e) {
            console.error("Failed to check anchor status", e);
        }
    };

    const loadBookmarks = async () => {
        try {
            const saved = await AsyncStorage.getItem('bookmarked_news');
            if (saved) {
                setBookmarks(JSON.parse(saved));
            }
        } catch (e) {
            console.error(e);
        }
    };

    const toggleBookmark = async (id: string) => {
        try {
            const updated = bookmarks.includes(id)
                ? bookmarks.filter(b => b !== id)
                : [...bookmarks, id];
            setBookmarks(updated);
            await AsyncStorage.setItem('bookmarked_news', JSON.stringify(updated));
            Alert.alert(
                bookmarks.includes(id) ? 'Removed' : 'Bookmarked', 
                bookmarks.includes(id) ? 'Article removed from offline bookmarks.' : 'Article saved to read offline later.'
            );
        } catch (e) {
            Alert.alert('Error', 'Failed to update bookmarks');
        }
    };

    const renderApnaNewsSkeleton = () => {
        return (
            <Animated.View
                style={{
                    backgroundColor: 'white',
                    borderRadius: 16,
                    borderWidth: 1,
                    borderColor: '#f3f4f6',
                    marginHorizontal: 16,
                    marginBottom: 16,
                    padding: 16,
                    opacity: shimmerAnim
                }}
            >
                <View style={{ width: '80%', height: 16, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 8 }} />
                <View style={{ width: '40%', height: 10, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 12 }} />
                <View style={{ flexDirection: 'row', gap: 12 }}>
                    <View style={{ flex: 1 }}>
                        <View style={{ width: '100%', height: 8, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 6 }} />
                        <View style={{ width: '90%', height: 8, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 6 }} />
                        <View style={{ width: '95%', height: 8, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 12 }} />
                        <View style={{ flexDirection: 'row', gap: 16 }}>
                            <View style={{ width: 50, height: 10, backgroundColor: '#e5e7eb', borderRadius: 4 }} />
                            <View style={{ width: 40, height: 10, backgroundColor: '#e5e7eb', borderRadius: 4 }} />
                        </View>
                    </View>
                    <View style={{ width: 112, height: 80, backgroundColor: '#e5e7eb', borderRadius: 12 }} />
                </View>
            </Animated.View>
        );
    };

    const renderVideoNewsSkeleton = () => {
        return (
            <Animated.View
                style={{
                    backgroundColor: 'white',
                    borderRadius: 16,
                    borderWidth: 1,
                    borderColor: '#f3f4f6',
                    marginHorizontal: 16,
                    marginBottom: 16,
                    opacity: shimmerAnim
                }}
            >
                <View style={{ width: '100%', aspectRatio: 16 / 9, backgroundColor: '#e5e7eb', justifyContent: 'center', alignItems: 'center' }}>
                    <View style={{ width: 56, height: 56, borderRadius: 28, backgroundColor: '#d1d5db' }} />
                </View>
                <View style={{ padding: 16 }}>
                    <View style={{ width: '90%', height: 12, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 8 }} />
                    <View style={{ width: '70%', height: 12, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 12 }} />
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                        <View style={{ width: 60, height: 10, backgroundColor: '#e5e7eb', borderRadius: 4 }} />
                        <View style={{ width: 50, height: 24, backgroundColor: '#e5e7eb', borderRadius: 12 }} />
                    </View>
                </View>
            </Animated.View>
        );
    };

    useEffect(() => {
        isMounted.current = true;
        loadBookmarks();
        fetchNews();
        fetchYTNews();
        fetchAasthaNews();
        fetchAnchorStatus();

        const unsubscribe = navigation.addListener('focus', () => {
            loadBookmarks();
            fetchAnchorStatus();
            fetchNews();
        });

        return () => { 
            isMounted.current = false; 
            unsubscribe();
        };
    }, [navigation]);

    const fetchNews = async () => {
        try {
            const res = await api.get('/get_news.php');
            if (isMounted.current && res.data.status === 'success') {
                const newsList = res.data.data;
                setNews(newsList);
                if (res.data.ticker) {
                    setTickerData(res.data.ticker);
                } else if (newsList && newsList.length > 0) {
                    setTickerData(newsList.slice(0, 5).map((n: any) => n.title));
                }
            }
        } catch (error) {
            console.error("Failed to fetch news", error);
        } finally {
            if (isMounted.current) setLoading(false);
        }
    };

    const onRefreshApna = async () => {
        setRefreshing(true);
        await Promise.all([fetchNews(), fetchAnchorStatus()]);
        setRefreshing(false);
    };

    const fetchYTNews = async (isRefresh = false) => {
        if (!isRefresh) setYtLoading(true);
        try {
            const res = await api.get('/get_video_news.php');
            if (isMounted.current && res.data.status === 'success') {
                setYtNews(res.data.data);
            }
        } catch (error) {
            console.error("Failed to fetch DD news", error);
        } finally {
            if (isMounted.current) setYtLoading(false);
        }
    };

    const onRefreshYT = async () => {
        setYtRefreshing(true);
        await fetchYTNews(true);
        setYtRefreshing(false);
    };

    const fetchAasthaNews = async (isRefresh = false) => {
        if (!isRefresh) setAasthaLoading(true);
        try {
            const res = await api.get('/get_aastha.php');
            if (isMounted.current && res.data.status === 'success') {
                setAasthaNews(res.data.data);
            }
        } catch (error) {
            console.error("Failed to fetch Aastha news", error);
        } finally {
            if (isMounted.current) setAasthaLoading(false);
        }
    };

    const onRefreshAastha = async () => {
        setAasthaRefreshing(true);
        await fetchAasthaNews(true);
        setAasthaRefreshing(false);
    };

    const extractVideoId = (url: string) => {
        if (!url) return null;
        const match = url.match(/[?&]v=([^&]+)/);
        if (match) return match[1];
        
        // Sometimes short forms are used
        const shortMatch = url.match(/youtu\.be\/([^?&]+)/);
        if (shortMatch) return shortMatch[1];
        
        return null;
    };

    const renderNewsItem = ({ item }: { item: any }) => {
        if (!item) return null;
        const images = item.images || [];
        const hasMultiple = images.length > 1;
        const readTime = Math.max(1, Math.ceil((item.description || '').split(' ').length / 150));
        const viewsCount = 50 + ((item.id || 0) * 17) % 850;
        const isBookmarked = bookmarks.includes(item.id?.toString());

        const handleShare = async () => {
            try {
                const newsUrl = `${WEBSITE_URL}/view_news.php?id=${item.id}`;
                const message = `${item.title}\n\n${item.description?.substring(0, 100)}...\n\nOpen in App: ${newsUrl}`;
                await Share.share({ message, url: newsUrl });
            } catch (error: any) {}
        };

        const handleImageDownload = async (imageUri: string) => {
            Alert.alert(
                'Download Image',
                'Do you want to download this news image?',
                [
                    { text: 'Cancel', style: 'cancel' },
                    {
                        text: 'Download',
                        onPress: async () => {
                            try {
                                const filename = imageUri.split('/').pop() || 'news_image.jpg';
                                const fileUri = FileSystem.documentDirectory + filename;
                                const res = await FileSystem.downloadAsync(imageUri, fileUri);
                                if (res.status === 200) {
                                    if (await Sharing.isAvailableAsync()) {
                                        await Sharing.shareAsync(res.uri, { dialogTitle: 'Save News Image' });
                                    } else {
                                        Alert.alert('Success', 'Image downloaded successfully!');
                                    }
                                }
                            } catch (e) {
                                Alert.alert('Error', 'Failed to download image.');
                            }
                        }
                    }
                ]
            );
        };

        return (
            <View className="bg-white rounded-2xl shadow-sm border border-gray-100 mx-4 mb-4 overflow-hidden">
                <View className="p-4">
                    <Text className="text-base font-bold text-gray-900 leading-snug mb-1.5">{item.title}</Text>
                    <View className="flex-row items-center mb-3">
                        <Text className="text-xs text-orange-500">{item.created_at}</Text>
                        {item.category && (
                            <View className="bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100 ml-2">
                                <Text className="text-[9px] text-orange-600 font-extrabold">{item.category}</Text>
                            </View>
                        )}
                        <Text style={{ fontSize: 10, color: '#9ca3af', marginLeft: 8 }}>⏱️ {readTime} min read</Text>
                        <Text style={{ fontSize: 10, color: '#9ca3af', marginLeft: 8 }}>👁️ {viewsCount} views</Text>
                    </View>

                    <View className="flex-row">
                        <View className="flex-1 pr-2">
                            <Text className="text-gray-500 text-xs leading-relaxed" numberOfLines={5}>
                                {item.description}
                            </Text>
                            <View className="flex-row mt-3 items-center gap-4">
                                <TouchableOpacity onPress={() => navigation.navigate('NewsDetail', { news: item })} activeOpacity={0.7}>
                                    <Text className="text-orange-600 font-bold text-xs tracking-wide">Read More</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={handleShare} activeOpacity={0.7}>
                                    <View className="flex-row items-center">
                                        <Ionicons name="share-social-outline" size={14} color="#6b7280" />
                                        <Text className="text-gray-500 font-bold text-xs ml-1">Share</Text>
                                    </View>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={() => toggleBookmark(item.id?.toString())} activeOpacity={0.7}>
                                    <View className="flex-row items-center">
                                        <Ionicons name={isBookmarked ? "bookmark" : "bookmark-outline"} size={14} color={isBookmarked ? "#ea580c" : "#6b7280"} />
                                        <Text className={`${isBookmarked ? 'text-orange-600' : 'text-gray-500'} font-bold text-xs ml-1`}>
                                            {isBookmarked ? 'Saved' : 'Save'}
                                        </Text>
                                    </View>
                                </TouchableOpacity>
                            </View>
                        </View>

                        {images.length > 0 && (
                            <View className="w-28 flex-col gap-1">
                                <TouchableOpacity onPress={() => handleImageDownload(`${NEWS_URL}${images[0]}`) } activeOpacity={0.8}>
                                    <Image
                                        source={{ uri: `${NEWS_URL}${images[0]}` }}
                                        className={`w-full bg-gray-100 rounded-xl ${hasMultiple ? 'h-20' : 'h-28'}`}
                                        resizeMode="cover"
                                    />
                                    <View style={{ position: 'absolute', bottom: 6, right: 6, backgroundColor: 'rgba(0,0,0,0.55)', borderRadius: 12, padding: 4 }}>
                                        <Ionicons name="download-outline" size={14} color="white" />
                                    </View>
                                </TouchableOpacity>
                                {hasMultiple && (
                                    <View className="flex-1 relative">
                                        <TouchableOpacity onPress={() => handleImageDownload(`${NEWS_URL}${images[1]}`)} activeOpacity={0.8}>
                                            <Image
                                                source={{ uri: `${NEWS_URL}${images[1]}` }}
                                                className="w-full h-10 rounded-xl bg-gray-100 opacity-80"
                                                resizeMode="cover"
                                            />
                                        </TouchableOpacity>
                                        {images.length > 2 && (
                                            <View className="absolute inset-0 bg-black/40 rounded-xl items-center justify-center">
                                                <Text className="text-white font-bold text-xs">+{images.length - 2}</Text>
                                            </View>
                                        )}
                                    </View>
                                )}
                            </View>
                        )}
                    </View>
                </View>
            </View>
        );
    };

    const renderYTNewsItem = ({ item }: { item: any }) => {
        if (!item) return null;
        const videoId = item.videoId || extractVideoId(item.link);
        const thumbnailUrl = videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : 'https://via.placeholder.com/300x200?text=Video';
        
        const handleShareYT = async () => {
            try {
                const message = `Watch this Video News update:\n${item.title}\n${item.link}`;
                await Share.share({ message });
            } catch (error: any) {}
        };

        return (
            <View style={{ marginHorizontal: 16, marginBottom: 16, borderRadius: 16, backgroundColor: 'white', overflow: 'hidden', borderWidth: 1, borderColor: '#f3f4f6', shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 8, elevation: 2 }}>
                <TouchableOpacity 
                    activeOpacity={0.9} 
                    onPress={() => setSelectedVideoId(videoId)}
                    style={{ width: '100%', aspectRatio: 16 / 9, backgroundColor: '#e5e7eb', position: 'relative' }}
                >
                    <Image
                        source={{ uri: thumbnailUrl }}
                        style={{ width: '100%', height: '100%' }}
                        resizeMode="cover"
                    />
                    <View style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.15)', justifyContent: 'center', alignItems: 'center' }}>
                        <View style={{ backgroundColor: 'rgba(255,255,255,0.95)', width: 56, height: 56, borderRadius: 28, justifyContent: 'center', alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 10, elevation: 4 }}>
                            <Ionicons name="play" size={24} color="#ea580c" style={{ marginLeft: 4 }} />
                        </View>
                    </View>
                </TouchableOpacity>

                <View style={{ padding: 16 }}>
                    <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#111827', lineHeight: 20, marginBottom: 8 }} numberOfLines={2}>
                        {item.title}
                    </Text>
                    <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                            {item.isLive && (
                                <View style={{ backgroundColor: '#ef4444', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, marginRight: 8 }}>
                                    <Text style={{ color: 'white', fontSize: 9, fontWeight: 'bold' }}>LIVE</Text>
                                </View>
                            )}
                            <Text style={{ color: '#9ca3af', fontSize: 11, fontWeight: '500' }}>{item.pubDate}</Text>
                        </View>
                        <TouchableOpacity onPress={handleShareYT} style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#f9fafb', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, borderWidth: 1, borderColor: '#e5e7eb' }}>
                            <Ionicons name="share-social" size={14} color="#4b5563" />
                            <Text style={{ color: '#4b5563', fontSize: 12, fontWeight: 'bold', marginLeft: 6 }}>Share</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </View>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-[#f9fafb]" edges={['top']}>
            {/* Header & Tabs */}
            <View className="bg-white pt-4 border-b border-gray-100 shadow-sm z-10">
                <View className="flex-row justify-between items-center px-5 mb-4">
                    <Text className="text-xl font-extrabold text-gray-900">News & Updates</Text>
                    {anchorStatus === 'Approved' ? (
                        <TouchableOpacity 
                            onPress={() => navigation.navigate('CreateNews')}
                            className="bg-orange-600 px-3.5 py-1.5 rounded-full flex-row items-center shadow-sm"
                            activeOpacity={0.7}
                        >
                            <Ionicons name="create-outline" size={14} color="white" />
                            <Text className="text-white font-bold text-xs ml-1">Post News</Text>
                        </TouchableOpacity>
                    ) : anchorStatus === 'Pending' ? (
                        <TouchableOpacity 
                            onPress={() => Alert.alert("Pending Application", "Aapki news anchor application pending hai, please approval ka wait karein.")}
                            className="bg-gray-400 px-3.5 py-1.5 rounded-full flex-row items-center shadow-sm"
                            activeOpacity={0.7}
                        >
                            <Ionicons name="time-outline" size={14} color="white" />
                            <Text className="text-white font-bold text-xs ml-1">Pending Approval</Text>
                        </TouchableOpacity>
                    ) : (
                        <TouchableOpacity 
                            onPress={() => {
                                if (isAnchorPaid) {
                                    navigation.navigate('ApplyJob', { jobId: 'anchor', jobTitle: 'News Anchor', isAnchorApplication: true });
                                } else {
                                    Alert.alert(
                                        "Payment Required",
                                        `Applying for News Anchor requires a fee of ₹${anchorFee}. Pay now to proceed.`,
                                        [
                                            { text: "Cancel", style: "cancel" },
                                            { 
                                                text: "Pay Now", 
                                                onPress: () => {
                                                    const path = anchorPaymentUrl || `payment_anchor.php?user_id=${userId}`;
                                                    const fullPaymentUrl = `${API_BASE_URL}${path}`;
                                                    Linking.openURL(fullPaymentUrl).catch(err => 
                                                        console.error("Couldn't open URL", err)
                                                    );
                                                }
                                            }
                                        ]
                                    );
                                }
                            }}
                            className="bg-orange-600 px-3.5 py-1.5 rounded-full flex-row items-center shadow-sm"
                            activeOpacity={0.7}
                        >
                            <Ionicons name="mic-outline" size={14} color="white" />
                            <Text className="text-white font-bold text-xs ml-1">{anchorStatus === 'Rejected' ? 'Reapply Anchor' : 'Apply for News Anchor'}</Text>
                        </TouchableOpacity>
                    )}
                </View>
                
                <View className="flex-row bg-gray-100 p-1 rounded-2xl mx-5 mb-4 border border-gray-200">
                    <TouchableOpacity 
                        onPress={() => setActiveTab('apna')}
                        className="flex-1 flex-row items-center justify-center py-2.5 rounded-xl"
                        style={activeTab === 'apna' ? { backgroundColor: 'white', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.1, shadowRadius: 2, elevation: 1 } : null}
                        activeOpacity={0.7}
                    >
                        <Ionicons name="newspaper-outline" size={15} color={activeTab === 'apna' ? '#ea580c' : '#6b7280'} />
                        <Text className={`ml-1.5 font-bold text-xs ${activeTab === 'apna' ? 'text-gray-900' : 'text-gray-500'}`}>Apna News</Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        onPress={() => setActiveTab('ddnews')}
                        className="flex-1 flex-row items-center justify-center py-2.5 mx-1 rounded-xl"
                        style={activeTab === 'ddnews' ? { backgroundColor: 'white', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.1, shadowRadius: 2, elevation: 1 } : null}
                        activeOpacity={0.7}
                    >
                        <Ionicons name="logo-youtube" size={15} color={activeTab === 'ddnews' ? '#dc2626' : '#6b7280'} />
                        <Text className={`ml-1.5 font-bold text-xs ${activeTab === 'ddnews' ? 'text-gray-900' : 'text-gray-500'}`}>DD News</Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        onPress={() => setActiveTab('aastha')}
                        className="flex-1 flex-row items-center justify-center py-2.5 rounded-xl"
                        style={activeTab === 'aastha' ? { backgroundColor: 'white', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.1, shadowRadius: 2, elevation: 1 } : null}
                        activeOpacity={0.7}
                    >
                        <Ionicons name="flower-outline" size={15} color={activeTab === 'aastha' ? '#d97706' : '#6b7280'} />
                        <Text className={`ml-1.5 font-bold text-xs ${activeTab === 'aastha' ? 'text-gray-900' : 'text-gray-500'}`}>Aastha</Text>
                    </TouchableOpacity>
                </View>

                {activeTab === 'apna' && (
                    <ScrollView 
                        horizontal 
                        showsHorizontalScrollIndicator={false} 
                        contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 14, paddingTop: 4 }}
                        className="bg-white"
                    >
                        {CATEGORY_MAP.map((cat) => (
                            <TouchableOpacity
                                key={cat.name}
                                onPress={() => setSelectedCategory(cat.name)}
                                className="flex-row items-center rounded-full px-3.5 py-1.5 mr-2 border"
                                activeOpacity={0.8}
                                style={{
                                    backgroundColor: selectedCategory === cat.name ? '#ea580c' : '#f9fafb',
                                    borderColor: selectedCategory === cat.name ? '#ea580c' : '#f3f4f6',
                                    shadowColor: selectedCategory === cat.name ? '#f97316' : '#000',
                                    shadowOffset: { width: 0, height: 4 },
                                    shadowOpacity: selectedCategory === cat.name ? 0.2 : 0.02,
                                    shadowRadius: 5,
                                    elevation: selectedCategory === cat.name ? 3 : 1
                                }}
                            >
                                <Text className="mr-1.5 text-sm">{cat.icon}</Text>
                                <Text
                                    className={`text-xs font-bold tracking-wide ${
                                        selectedCategory === cat.name ? 'text-white' : 'text-gray-700'
                                    }`}
                                >
                                    {cat.name}
                                </Text>
                            </TouchableOpacity>
                        ))}
                    </ScrollView>
                )}

                {/* Search Bar */}
                <View style={{ paddingHorizontal: 20, paddingBottom: 12 }}>
                    <View style={{ flexDirection: 'row', alignItems: 'center', backgroundColor: '#f3f4f6', borderRadius: 20, paddingHorizontal: 12, paddingVertical: 8, borderWidth: 1, borderColor: '#e5e7eb' }}>
                        <Ionicons name="search" size={18} color="#9ca3af" style={{ marginRight: 6 }} />
                        <TextInput
                            style={{ flex: 1, fontSize: 13, color: '#1f2937', padding: 0 }}
                            placeholder="Search news updates..."
                            placeholderTextColor="#9ca3af"
                            value={searchQuery}
                            onChangeText={setSearchQuery}
                        />
                        {searchQuery.length > 0 && (
                            <TouchableOpacity onPress={() => setSearchQuery('')}>
                                <Ionicons name="close-circle" size={18} color="#9ca3af" />
                            </TouchableOpacity>
                        )}
                    </View>
                </View>

                {activeTab === 'apna' && showTicker && (
                    <NewsTicker tickerData={tickerData} onClose={() => setShowTicker(false)} />
                )}
            </View>

            {/* List Content */}
            {activeTab === 'apna' && (
                loading ? (
                    <FlatList
                        data={[1, 2, 3, 4]}
                        keyExtractor={(item) => item.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        renderItem={renderApnaNewsSkeleton}
                    />
                ) : (
                    <FlatList
                        data={Array.isArray(filteredNews) ? filteredNews : []}
                        keyExtractor={(item, index) => item?.id?.toString() || index.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefreshApna} colors={['#ea580c']} tintColor="#ea580c" />}
                        renderItem={renderNewsItem}
                        ListEmptyComponent={<Text className="text-center text-gray-500 mt-10">No news available</Text>}
                    />
                )
            )}
            
            {activeTab === 'ddnews' && (
                ytLoading ? (
                    <FlatList
                        data={[1, 2]}
                        keyExtractor={(item) => item.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        renderItem={renderVideoNewsSkeleton}
                    />
                ) : (
                    <FlatList
                        data={Array.isArray(ytNews) ? ytNews.filter((item: any) => !searchQuery.trim() || item.title?.toLowerCase().includes(searchQuery.toLowerCase())) : []}
                        keyExtractor={(item, index) => item?.videoId || item?.link || index.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        refreshControl={<RefreshControl refreshing={ytRefreshing} onRefresh={onRefreshYT} colors={['#dc2626']} tintColor="#dc2626" />}
                        renderItem={renderYTNewsItem}
                        ListEmptyComponent={<Text className="text-center text-gray-500 mt-10">No videos available right now</Text>}
                    />
                )
            )}

            {activeTab === 'aastha' && (
                aasthaLoading ? (
                    <FlatList
                        data={[1, 2]}
                        keyExtractor={(item) => item.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        renderItem={renderVideoNewsSkeleton}
                    />
                ) : (
                    <FlatList
                        data={Array.isArray(aasthaNews) ? aasthaNews.filter((item: any) => !searchQuery.trim() || item.title?.toLowerCase().includes(searchQuery.toLowerCase())) : []}
                        keyExtractor={(item, index) => item?.videoId || item?.link || index.toString()}
                        contentContainerStyle={{ paddingVertical: 12 }}
                        refreshControl={<RefreshControl refreshing={aasthaRefreshing} onRefresh={onRefreshAastha} colors={['#d97706']} tintColor="#d97706" />}
                        renderItem={renderYTNewsItem}
                        ListEmptyComponent={<Text className="text-center text-gray-500 mt-10">No streams available right now</Text>}
                    />
                )
            )}

            {/* In-App Video Player Modal using WebView */}
            <Modal
                visible={!!selectedVideoId}
                animationType="slide"
                onRequestClose={() => setSelectedVideoId(null)}
                // In iOS pageSheet makes it easily swipeable, on Android it opens fully
                presentationStyle="pageSheet"
            >
                <SafeAreaView className="flex-1 bg-black">
                    <View className="flex-row items-center justify-between p-4 bg-black">
                        <Text className="text-white font-bold text-lg">Video News Player</Text>
                        <TouchableOpacity onPress={() => setSelectedVideoId(null)} className="p-1.5 rounded-full bg-white/20">
                            <Ionicons name="close" size={24} color="white" />
                        </TouchableOpacity>
                    </View>
                    <View className="flex-1 justify-center bg-black">
                        {selectedVideoId && (
                            <View style={{ width: '100%', aspectRatio: 16/9 }}>
                                <WebView
                                    source={{ 
                                        html: `
                                        <!DOCTYPE html>
                                        <html>
                                        <head>
                                            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
                                            <style>
                                                body { margin: 0; padding: 0; background-color: #000; display: flex; justify-content: center; align-items: center; height: 100vh; width: 100vw; overflow: hidden; }
                                                iframe { width: 100%; height: 100%; border: none; }
                                            </style>
                                        </head>
                                        <body>
                                            <iframe 
                                                src="https://www.youtube.com/embed/${selectedVideoId}?autoplay=1&playsinline=1&rel=0&origin=https://sadhuvandna.com" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                            </iframe>
                                        </body>
                                        </html>
                                        `,
                                        baseUrl: 'https://sadhuvandna.com/'
                                     }}
                                    allowsFullscreenVideo={true}
                                    javaScriptEnabled={true}
                                    domStorageEnabled={true}
                                    mediaPlaybackRequiresUserAction={false}
                                    style={{ flex: 1, backgroundColor: 'black' }}
                                    scrollEnabled={false}
                                    bounces={false}
                                />
                            </View>
                        )}
                        <Text className="text-gray-400 text-center mt-6 px-6 leading-5 text-sm">
                            You are watching this video securely without leaving the app. Wait for the video to buffer.
                        </Text>
                    </View>
                </SafeAreaView>
            </Modal>

        </SafeAreaView>
    );
};

export default NewsScreen;
